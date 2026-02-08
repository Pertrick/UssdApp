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
                ? $sanitizationService->sanitizeInput(str_replace("\0", "", $requestData['text']), 'ussd_selection')
                : '';

            Log::info('AfricasTalking USSD Request', [
                'sessionId' => $sessionId,
                'serviceCode' => $serviceCode,
                'phoneNumber' => $phoneNumber,
                'text' => $text
            ]);

            // AfricasTalking sends: serviceCode = short code (e.g. *347*412#); text = user input
            // When user dials *347*412*1# directly, AT sends serviceCode=*347*412#, text=1
            $resolved = $this->resolveUssdFromRequest($serviceCode, $text);
            $ussd = $resolved['ussd'] ?? null;
            $isDirectDial = $resolved['direct_dial'] ?? false;

            // Only clear text on first request (new session) for direct dial; store choice for stripping on subsequent requests
            $directDialChoice = null;
            $existingSession = $sessionId ? USSDSession::where('session_id', $sessionId)->first() : null;
            if ($isDirectDial && !$existingSession) {
                $directDialChoice = $this->getFirstSegment($text);
                $text = '';
            }

            if (!$ussd) {
                Log::warning('USSD not found for service code - potential unauthorized access attempt', [
                    'serviceCode' => $serviceCode,
                    'text' => $text,
                    'phoneNumber' => $phoneNumber,
                    'sessionId' => $sessionId,
                    'ip_address' => request()->ip(),
                ]);
                return $this->formatResponse('END', 'Invalid service code.');
            }

            $isGateway = $ussd->is_shared_gateway && $ussd->sharedCodeAllocations->isNotEmpty();
            $isFirstRequest = empty($text);
            

            // Get or create session (skip billing for gateway – we bill when tenant is chosen)
            $session = $this->getOrCreateSession(
                $ussd,
                $sessionId,
                $phoneNumber,
                $isFirstRequest,
                $isGateway
            );

            // Store tenant_choice for direct dial so we can strip prefix on subsequent requests
            if ($directDialChoice !== null) {
                $sessionData = $session->session_data ?? [];
                $sessionData['tenant_choice'] = $directDialChoice;
                $session->update(['session_data' => $sessionData]);
                $session->refresh();
            }

            // Check if session is already completed/ended
            if ($session->status === 'completed') {
                return $this->formatResponse('END', 'This session has ended. Please start a new session.');
            }

            // Check if billing failed (insufficient balance, etc.)
            if ($session->billing_status === 'failed') {
                $errorMessage = $session->error_message ?? 'Insufficient balance. Please top up your account.';
                return $this->formatResponse('END', $errorMessage);
            }

            $ussdSessionService = app(USSDSessionService::class);

            // ----- Shared gateway: first request → show tenant menu, do not bill -----
            if ($isGateway && $isFirstRequest) {
                $sessionData = $session->session_data ?? [];
                $sessionData['awaiting_tenant_choice'] = true;
                $session->update(['session_data' => $sessionData]);
                $menu = $this->buildGatewayMenu($ussd);
                return $this->formatResponse('CON', $menu);
            }

            // ----- Shared gateway: route to tenant when user selects (menu or direct dial *347*412*1#) -----
            $sessionData = $session->session_data ?? [];
            $hasTenantChoice = !empty($sessionData['tenant_choice']);
            $needsTenantRouting = $isGateway && (
                !empty($sessionData['awaiting_tenant_choice']) ||
                (!$hasTenantChoice && !empty($text))
            );
            if ($needsTenantRouting) {
                $choice = $this->getFirstSegment($text);
                $allocation = $ussd->sharedCodeAllocations->firstWhere('option_value', $choice);
                if (!$allocation) {
                    $menu = $this->buildGatewayMenu($ussd);
                    return $this->formatResponse('CON', "Invalid option.\n\n" . $menu);
                }
                $targetUssd = $allocation->targetUssd;
                $rootFlow = $targetUssd->rootFlow();
                if (!$rootFlow) {
                    return $this->formatResponse('END', 'Service not configured. Please try again later.');
                }
                $newSessionData = ['tenant_choice' => $choice];
                $session->update([
                    'ussd_id' => $targetUssd->id,
                    'current_flow_id' => $rootFlow->id,
                    'session_data' => $newSessionData,
                ]);
                $session->refresh();
                // Bill the tenant (first request to their flow)
                try {
                    $billingService = app(\App\Services\BillingService::class);
                    $gatewayCostService = app(\App\Services\GatewayCostService::class);
                    $networkProvider = $gatewayCostService->detectNetworkProvider($phoneNumber);
                    $gatewayCostService->recordGatewayCost($session, $networkProvider);
                    $billingService->billSession($session);
                } catch (\Throwable $e) {
                    Log::error('Failed to bill USSD session after tenant choice', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                    $session->update(['status' => 'error']);
                    return $this->formatResponse('END', 'Unable to start service. Please try again.');
                }
                $display = $ussdSessionService->getCurrentFlowDisplay($session);
                return $this->formatAfricasTalkingResponse($display);
            }

            // ----- Strip tenant prefix when session was routed from gateway or direct dial -----
            $session->refresh();
            $sessionData = $session->session_data ?? [];
            $tenantChoice = $sessionData['tenant_choice'] ?? null;
            if ($tenantChoice !== null && $tenantChoice !== '') {
                $prefix = $tenantChoice . '*';
                if (str_starts_with($text, $prefix)) {
                    $text = substr($text, strlen($prefix));
                } elseif ($text === $tenantChoice) {
                    $text = '';
                }
            }

            // Process the input (normal flow or tenant flow with stripped text)
            $response = $ussdSessionService->processInput($session, $text);

            // If the input was processed successfully and we're not ending the session,
            // refresh the session and get the current flow display for the updated flow
            // This ensures template variables are resolved and silent flows work correctly
            // BUT: If the response already has a complete message from handleInputCollection
            // (especially when next_flow_id is null and there's an error), preserve it
            if ($response['success'] && !($response['session_ended'] ?? false)) {
                // Check if this response is from handleInputCollection with no next_flow_id
                // In this case, the message is already complete and should not be overwritten
                // We can detect this by checking if the response has a message and requires_input
                // AND if the current flow is in input collection mode
                $session->refresh();
                $sessionData = $session->session_data ?? [];
                $isCollectingInput = $sessionData['collecting_input'] ?? false;
                $hasApiError = isset($sessionData['last_api_error']) && isset($sessionData['api_error_message']);
                
                // If we're collecting input and there's an API error, the response from handleInputCollection
                // already has the correct error message, so don't overwrite it
                $shouldPreserveResponse = $isCollectingInput && 
                                         $hasApiError && 
                                         isset($response['message']) && 
                                         !empty($response['message']);
                
                \Log::info('AfricasTalkingService - Response preservation check', [
                    'session_id' => $session->id,
                    'isCollectingInput' => $isCollectingInput,
                    'hasApiError' => $hasApiError,
                    'response_has_message' => isset($response['message']) && !empty($response['message']),
                    'shouldPreserveResponse' => $shouldPreserveResponse,
                    'response_message_preview' => isset($response['message']) ? substr($response['message'], 0, 100) : 'no_message',
                ]);
                
                // If response should be preserved (input collection with error), use it directly
                // Otherwise, get the current flow display
                if (!$shouldPreserveResponse) {
                    $display = $ussdSessionService->getCurrentFlowDisplay($session);
                    
                    // Use the display from getCurrentFlowDisplay() which properly handles:
                    // - Template variable resolution with fresh session data
                    // - Silent dynamic flows (continue_without_display)
                    // - Proper flow display after navigation
                    $response = $display;
                } else {
                    \Log::info('AfricasTalkingService - Preserving response from handleInputCollection', [
                        'session_id' => $session->id,
                        'message_length' => strlen($response['message'] ?? ''),
                    ]);
                }
            }

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
     * Resolve USSD from AfricasTalking request.
     * AfricasTalking sends: serviceCode = short code (e.g. *347*412#); text = user input.
     * When user dials *347*412*1# directly, AT sends serviceCode=*347*412#, text=1.
     *
     * Returns ['ussd' => USSD|null, 'direct_dial' => bool]. When direct_dial=true,
     * the text was used to resolve; caller should show root without passing text.
     */
    protected function resolveUssdFromRequest(?string $serviceCode, string $text): array
    {
        if (!$serviceCode) {
            return ['ussd' => null, 'direct_dial' => false];
        }

        $query = fn () => USSD::with('sharedCodeAllocations')
            ->where('is_active', true)
            ->whereHas('environment', fn ($q) => $q->where('name', EnvironmentType::PRODUCTION->value));

        // 1) Exact match (gateway or normal USSD)
        $ussd = $query()->where('pattern', $serviceCode)->first();
        if ($ussd) {
            return ['ussd' => $ussd, 'direct_dial' => false];
        }

        // 2) Direct dial: user dialed *347*412*1# → AT sends serviceCode=*347*412#, text=1
        if ($text !== '') {
            $choice = $this->getFirstSegment($text);
            if ($choice !== '') {
                $directPattern = rtrim($serviceCode, '#') . '*' . $choice . '#';
                $ussd = $query()->where('pattern', $directPattern)->first();
                if ($ussd) {
                    return ['ussd' => $ussd, 'direct_dial' => true];
                }
            }
        }

        return ['ussd' => null, 'direct_dial' => false];
    }

    /**
     * Get or create USSD session.
     * Bills on first request (when session is created), unless $skipBillingForGatewayFirst is true
     * (shared gateway: we bill when the user chooses a tenant).
     */
    protected function getOrCreateSession(USSD $ussd, string $sessionId, string $phoneNumber, bool $isFirstRequest = false, bool $skipBillingForGatewayFirst = false): USSDSession
    {
        // CRITICAL: Use lockForUpdate() to prevent race conditions with concurrent requests
        $session = USSDSession::where('session_id', $sessionId)
            ->lockForUpdate()
            ->first();

        if ($session) {
            $sessionData = $session->session_data ?? [];
            $hasTenantChoice = !empty($sessionData['tenant_choice']);
            // Session may belong to a tenant when request still uses gateway pattern (shared code)
            if ($session->ussd_id !== $ussd->id) {
                if ($ussd->is_shared_gateway && $hasTenantChoice) {
                    // Valid: continuing a tenant session reached via this gateway
                    return $session;
                }
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
            $ussdSessionService = app(USSDSessionService::class);
            $session = $ussdSessionService->startSession($ussd, $phoneNumber, 'AfricasTalking', null, EnvironmentType::PRODUCTION->value);
            $session->update(['session_id' => $sessionId]);

            if ($isFirstRequest && !$session->is_billed && !$skipBillingForGatewayFirst) {
                try {
                    $billingService = app(\App\Services\BillingService::class);
                    $gatewayCostService = app(\App\Services\GatewayCostService::class);
                    $networkProvider = $gatewayCostService->detectNetworkProvider($phoneNumber);
                    $gatewayCostService->recordGatewayCost($session, $networkProvider);
                    $billingResult = $billingService->billSession($session);
                    if (!$billingResult) {
                        $session->update(['status' => 'error']);
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to bill USSD session on start', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                    $session->update(['status' => 'error']);
                }
            }
        }

        return $session;
    }

    /**
     * Build CON menu for shared gateway (e.g. "1. MCD\n2. PlanetF").
     */
    protected function buildGatewayMenu(USSD $gatewayUssd): string
    {
        $lines = ['Welcome'];
        foreach ($gatewayUssd->sharedCodeAllocations as $a) {
            $lines[] = $a->option_value . '. ' . $a->label;
        }
        return implode("\n", $lines);
    }

    /**
     * First segment of USSD text (e.g. "1*2*3" → "1").
     */
    protected function getFirstSegment(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $parts = explode('*', $text);
        return trim($parts[0]);
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
                stripos($messageText, $title) !== false || 
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