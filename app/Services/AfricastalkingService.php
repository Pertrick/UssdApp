<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\USSD;
use App\Models\USSDSession;

class AfricastalkingService
{
    protected $apiKey;
    protected $username;
    protected $baseUrl;
    protected $environment;

    public function __construct($environment = 'sandbox')
    {
        $this->environment = $environment;
        $this->baseUrl = $environment === 'live' 
            ? 'https://api.africastalking.com/version1'
            : 'https://api.sandbox.africastalking.com/version1';
        
        $this->apiKey = $environment === 'live' 
            ? config('services.africastalking.live_api_key')
            : config('services.africastalking.sandbox_api_key');
        
        $this->username = $environment === 'live'
            ? config('services.africastalking.live_username')
            : config('services.africastalking.sandbox_username');
    }

    /**
     * Create a new USSD application
     */
    public function createUssdApplication($ussdCode, $callbackUrl)
    {
        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post($this->baseUrl . '/application', [
                'username' => $this->username,
                'ussdCode' => $ussdCode,
                'callbackUrl' => $callbackUrl,
            ]);

            if ($response->successful()) {
                Log::info('Africastalking USSD application created', [
                    'ussd_code' => $ussdCode,
                    'environment' => $this->environment,
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            Log::error('Failed to create Africastalking USSD application', [
                'ussd_code' => $ussdCode,
                'response' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Exception creating Africastalking USSD application', [
                'error' => $e->getMessage(),
                'ussd_code' => $ussdCode
            ]);
            return false;
        }
    }

    /**
     * Send SMS notification (for USSD confirmations, etc.)
     */
    public function sendSms($phoneNumber, $message)
    {
        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post($this->baseUrl . '/messaging', [
                'username' => $this->username,
                'to' => $phoneNumber,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'phone' => $phoneNumber,
                    'environment' => $this->environment
                ]);
                return $response->json();
            }

            Log::error('Failed to send SMS', [
                'phone' => $phoneNumber,
                'response' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Exception sending SMS', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
            return false;
        }
    }

    /**
     * Process incoming USSD request
     */
    public function processUssdRequest($request)
    {
        $sessionId = $request->input('sessionId');
        $phoneNumber = $request->input('phoneNumber');
        $text = $request->input('text', '');
        $ussdCode = $request->input('serviceCode');

        Log::info('Incoming USSD request', [
            'session_id' => $sessionId,
            'phone' => $phoneNumber,
            'text' => $text,
            'ussd_code' => $ussdCode,
            'environment' => $this->environment
        ]);

        // Find the USSD service
        $ussd = USSD::where('live_ussd_code', $ussdCode)
                   ->orWhere('testing_ussd_code', $ussdCode)
                   ->where('is_active', true)
                   ->first();

        if (!$ussd) {
            return $this->formatResponse('Invalid USSD code', true);
        }

        // Create or get session
        $session = USSDSession::firstOrCreate([
            'session_id' => $sessionId,
            'ussd_id' => $ussd->id,
        ], [
            'phone_number' => $phoneNumber,
            'status' => 'active',
            'start_time' => now(),
        ]);

        // Process the USSD flow
        return $this->processUssdFlow($ussd, $session, $text);
    }

    /**
     * Process USSD flow based on user input
     */
    protected function processUssdFlow($ussd, $session, $text)
    {
        $inputs = $text ? explode('*', $text) : [];
        $currentLevel = count($inputs);

        // Get the current flow based on level
        $currentFlow = $ussd->flows()
                           ->where('level', $currentLevel)
                           ->first();

        if (!$currentFlow) {
            return $this->formatResponse('Invalid option. Please try again.', true);
        }

        // Log the interaction
        $this->logInteraction($session, $currentFlow, $inputs);

        // Get response based on flow type
        switch ($currentFlow->flow_type) {
            case 'menu':
                return $this->handleMenuFlow($currentFlow);
            
            case 'input':
                return $this->handleInputFlow($currentFlow, $inputs);
            
            case 'payment':
                return $this->handlePaymentFlow($currentFlow, $session, $inputs);
            
            case 'confirmation':
                return $this->handleConfirmationFlow($currentFlow, $session, $inputs);
            
            default:
                return $this->formatResponse('Service temporarily unavailable.', true);
        }
    }

    /**
     * Handle menu-type flows
     */
    protected function handleMenuFlow($flow)
    {
        $options = $flow->options()->orderBy('sort_order')->get();
        $menuText = $flow->title . "\n\n";

        foreach ($options as $option) {
            $menuText .= $option->sort_order . ". " . $option->title . "\n";
        }

        return $this->formatResponse($menuText, false);
    }

    /**
     * Handle input-type flows
     */
    protected function handleInputFlow($flow, $inputs)
    {
        $response = $flow->title . "\n\n";
        $response .= "Enter " . $flow->input_label . ":";
        
        return $this->formatResponse($response, false);
    }

    /**
     * Handle payment flows
     */
    protected function handlePaymentFlow($flow, $session, $inputs)
    {
        if (!$flow->ussd->monetization_enabled) {
            return $this->formatResponse('Payment feature not enabled for this service.', true);
        }

        // Here you would integrate with payment providers like M-Pesa, Airtel Money, etc.
        $response = $flow->title . "\n\n";
        $response .= "Amount: " . $flow->payment_amount . "\n";
        $response .= "1. Confirm Payment\n";
        $response .= "2. Cancel";

        return $this->formatResponse($response, false);
    }

    /**
     * Handle confirmation flows
     */
    protected function handleConfirmationFlow($flow, $session, $inputs)
    {
        $response = $flow->title . "\n\n";
        $response .= "Thank you for using our service!\n";
        $response .= "Transaction ID: " . $session->id;

        // End the session
        $session->update([
            'status' => 'completed',
            'end_time' => now(),
        ]);

        return $this->formatResponse($response, true);
    }

    /**
     * Format USSD response
     */
    protected function formatResponse($message, $endSession = false)
    {
        $response = "CON " . $message;
        
        if ($endSession) {
            $response = "END " . $message;
        }

        return response($response, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Log USSD interaction
     */
    protected function logInteraction($session, $flow, $inputs)
    {
        $session->logs()->create([
            'ussd_id' => $session->ussd_id,
            'flow_id' => $flow->id,
            'action_type' => 'user_input',
            'input_data' => json_encode($inputs),
            'output_data' => $flow->title,
            'status' => 'success',
            'action_timestamp' => now(),
        ]);
    }

    /**
     * Get USSD application status
     */
    public function getApplicationStatus($ussdCode)
    {
        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
            ])->get($this->baseUrl . '/application', [
                'username' => $this->username,
            ]);

            if ($response->successful()) {
                $applications = $response->json();
                foreach ($applications as $app) {
                    if ($app['ussdCode'] === $ussdCode) {
                        return $app;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception getting application status', [
                'error' => $e->getMessage(),
                'ussd_code' => $ussdCode
            ]);
            return null;
        }
    }
} 