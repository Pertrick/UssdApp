<?php

namespace App\Services;

use App\Models\USSD;
use App\Models\USSDSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AfricasTalkingService
{
    protected $apiKey;
    protected $username;
    protected $baseUrl = 'https://api.africastalking.com/version1';

    public function __construct()
    {
        $this->apiKey = config('services.africastalking.api_key');
        $this->username = config('services.africastalking.username');
    }

    /**
     * Process incoming USSD request from AfricasTalking
     */
    public function processUSSDRequest(array $requestData): array
    {
        try {
            $sessionId = $requestData['sessionId'] ?? null;
            $serviceCode = $requestData['serviceCode'] ?? null;
            $phoneNumber = $requestData['phoneNumber'] ?? null;
            $text = $requestData['text'] ?? '';

            Log::info('AfricasTalking USSD Request', [
                'sessionId' => $sessionId,
                'serviceCode' => $serviceCode,
                'phoneNumber' => $phoneNumber,
                'text' => $text
            ]);

            // Find USSD by service code
            // AfricasTalking sends the actual USSD code (e.g., *384*123#)
            // We need to check pattern, live_ussd_code, and testing_ussd_code
            $ussd = USSD::where(function($query) use ($serviceCode) {
                    $query->where('pattern', $serviceCode)
                          ->orWhere('live_ussd_code', $serviceCode);
                })
                ->where('is_active', true)
                ->first();

            if (!$ussd) {
                Log::warning('USSD not found for service code', [
                    'serviceCode' => $serviceCode,
                    'available_codes' => USSD::where('is_active', true)
                        ->select('pattern', 'live_ussd_code')
                        ->get()
                        ->map(fn($u) => [
                            'pattern' => $u->pattern,
                            'live' => $u->live_ussd_code
                        ])
                ]);
                return $this->formatResponse('END', 'Invalid service code.');
            }

            // Get or create session
            // Empty text indicates first request (session start)
            $isFirstRequest = empty($text);
            $session = $this->getOrCreateSession($ussd, $sessionId, $phoneNumber, $isFirstRequest);

            // Check if billing failed (insufficient balance, etc.)
            if ($session->billing_status === 'failed') {
                $errorMessage = $session->error_message ?? 'Insufficient balance. Please top up your account.';
                return $this->formatResponse('END', $errorMessage);
            }

            // Process the input
            $ussdSessionService = app(USSDSessionService::class);
            $response = $ussdSessionService->processInput($session, $text);

            // Format response for AfricasTalking
            return $this->formatAfricasTalkingResponse($response);

        } catch (\Exception $e) {
            Log::error('AfricasTalking USSD Error', [
                'error' => $e->getMessage(),
                'request' => $requestData
            ]);

            return $this->formatResponse('END', 'An error occurred. Please try again.');
        }
    }

    /**
     * Get or create USSD session
     * Bills on first request (when session is created)
     */
    protected function getOrCreateSession(USSD $ussd, string $sessionId, string $phoneNumber, bool $isFirstRequest = false): USSDSession
    {
        // Try to find existing session (prevent duplicate billing)
        $session = USSDSession::where('session_id', $sessionId)->first();

        if (!$session) {
            // Create new session
            $ussdSessionService = app(USSDSessionService::class);
            $session = $ussdSessionService->startSession($ussd, $phoneNumber, 'AfricasTalking', null, 'production');
            
            // Update with AfricasTalking session ID
            $session->update(['session_id' => $sessionId]);
            

            if ($isFirstRequest && !$session->is_billed) {
                try {
                    $billingService = app(\App\Services\BillingService::class);
                    $gatewayCostService = app(\App\Services\GatewayCostService::class);
                    
                    // Record gateway cost first (what AfricasTalking charges)
                    $networkProvider = $gatewayCostService->detectNetworkProvider($phoneNumber);
                    $gatewayCostService->recordGatewayCost($session, $networkProvider);
                    
                    // Then bill the customer
                    $billingResult = $billingService->billSession($session);
                    
                    // If billing failed (e.g., insufficient balance), mark session
                    if (!$billingResult) {
                        $session->update(['status' => 'error']);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to bill USSD session on start', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Mark session as error if billing fails
                    $session->update(['status' => 'error']);
                }
            }
        }

        return $session;
    }

    /**
     * Format response for AfricasTalking
     */
    protected function formatAfricasTalkingResponse(array $response): array
    {
        $message = $response['message'] ?? 'Thank you for using our service.';
        
        if ($response['session_ended'] ?? false) {
            return $this->formatResponse('END', $message);
        } else {
            return $this->formatResponse('CON', $message);
        }
    }

    /**
     * Format basic response
     */
    protected function formatResponse(string $responseType, string $message): array
    {
        return [
            'response' => $responseType,
            'message' => $message,
            'freeFlow' => 'FC'
        ];
    }

    /**
     * Send SMS (for notifications, etc.)
     */
    public function sendSMS(string $phoneNumber, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/messaging', [
                'username' => $this->username,
                'to' => $phoneNumber,
                'message' => $message
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('AfricasTalking SMS Error', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get account balance
     */
    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/user', [
                'username' => $this->username
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('AfricasTalking Balance Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 