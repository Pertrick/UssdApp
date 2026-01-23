<?php

namespace App\Services;

use App\Models\USSD;
use App\Models\USSDSession;
use App\Enums\EnvironmentType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
            // SECURITY: Sanitize all inputs using SanitizationService
            $sanitizationService = app(SanitizationService::class);
            
            $sessionId = isset($requestData['sessionId']) 
                ? $sanitizationService->sanitizeInput($requestData['sessionId'], 'session_id')
                : null;
            $serviceCode = isset($requestData['serviceCode'])
                ? $sanitizationService->sanitizeServiceCode($requestData['serviceCode'])
                : null;
            $phoneNumber = isset($requestData['phoneNumber'])
                ? $sanitizationService->sanitizeInput($requestData['phoneNumber'], 'input_phone')
                : null;
            $text = isset($requestData['text']) && $requestData['text'] !== ''
                ? $sanitizationService->sanitizeInput($requestData['text'], 'ussd_selection')
                : '';

            Log::info('AfricasTalking USSD Request', [
                'sessionId' => $sessionId,
                'serviceCode' => $serviceCode,
                'phoneNumber' => $phoneNumber,
                'text' => $text
            ]);

            // AfricasTalking sends the actual USSD code (e.g., *384*123#)
            // We match against the pattern field which is updated when moving to production
            $ussd = USSD::where('pattern', $serviceCode)
                ->where('is_active', true)
                ->whereHas('environment', function($query) {
                    $query->where('name', EnvironmentType::PRODUCTION->value);
                })
                ->first();

            if (!$ussd) {
                // SECURITY: Log failed service code attempts (potential attack)
                Log::warning('USSD not found for service code - potential unauthorized access attempt', [
                    'serviceCode' => $serviceCode,
                    'phoneNumber' => $phoneNumber,
                    'sessionId' => $sessionId,
                    'ip_address' => request()->ip(),
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
        // SECURITY: Validate session belongs to correct USSD if it exists
        $session = USSDSession::where('session_id', $sessionId)->first();
        
        if ($session) {
            // Verify session belongs to the correct USSD
            if ($session->ussd_id !== $ussd->id) {
                Log::warning('Session ID mismatch - potential session hijacking attempt', [
                    'session_id' => $sessionId,
                    'expected_ussd_id' => $ussd->id,
                    'actual_ussd_id' => $session->ussd_id,
                    'phone_number' => $phoneNumber,
                ]);
                throw new \Exception('Invalid session');
            }
        }

        if (!$session) {
            // Create new session
            $ussdSessionService = app(USSDSessionService::class);
            $session = $ussdSessionService->startSession($ussd, $phoneNumber, 'AfricasTalking', null, EnvironmentType::PRODUCTION->value);
            
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
                    Log::error('Failed to bill USSD session on start', [
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
        
        if (isset($response['flow_title']) && !empty($response['flow_title'])) {
            $title = trim($response['flow_title']);
            $messageText = trim($message);
            
            // Only prepend if title is not already in the message
            // Check both the processed title and a substring match (in case of formatting differences)
            $titleInMessage = !empty($title) && (
                str_contains($messageText, $title) || 
                str_starts_with($messageText, substr($title, 0, min(50, strlen($title))))
            );
            
            if (!empty($title) && !$titleInMessage) {
                $message = $title . "\n" . $messageText;
            }
        }

        $sanitizationService = app(SanitizationService::class);
        $message = $sanitizationService->sanitizeOutput($message);
        
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