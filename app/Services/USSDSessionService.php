<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\USSD;
use App\Models\USSDFlow;
use App\Models\Environment;
use App\Models\USSDSession;
use Illuminate\Support\Str;
use App\Models\USSDFlowOption;
use App\Models\USSDSessionLog;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\GatewayCostService;
use App\Services\DynamicFlowProcessor;
use App\Services\APITestLoggingService;
use App\Models\ExternalAPIConfiguration;
use App\Enums\EnvironmentType;

class USSDSessionService
{
    protected $loggingService;
    protected $billingService;
    protected $gatewayCostService;

    public function __construct(APITestLoggingService $loggingService, BillingService $billingService, GatewayCostService $gatewayCostService)
    {
        $this->loggingService = $loggingService;
        $this->billingService = $billingService;
        $this->gatewayCostService = $gatewayCostService;
    }

    /**
     * Start a new USSD session
     * 
     * @param USSD $ussd The USSD service
     * @param string|null $phoneNumber Phone number for the session
     * @param string|null $userAgent User agent string
     * @param string|null $ipAddress IP address
     * @param string|null $environment Environment override (testing/production/live)
     * @param string|null $sessionId Optional session_id to reuse existing session
     * @return USSDSession
     */
    public function startSession(USSD $ussd, ?string $phoneNumber = null, ?string $userAgent = null, ?string $ipAddress = null, ?string $environment = null, ?string $sessionId = null): USSDSession
    {
        $rateLimitService = app(SessionRateLimitService::class);
        
        if ($phoneNumber && $rateLimitService->isPhoneBlocked($phoneNumber)) {
            throw new \Exception('Access temporarily blocked. Please try again later.');
        }
        
        if ($phoneNumber && !$rateLimitService->checkNewSessionRateLimit($phoneNumber)) {
            $rateLimitService->blockPhone($phoneNumber, 3600); // Block for 1 hour
            throw new \Exception('Too many session attempts. Please try again later.');
        }
        
        // Get the root flow for this USSD
        $rootFlow = USSDFlow::where('ussd_id', $ussd->id)
            ->where('is_root', true)
            ->where('is_active', true)
            ->first();

        if (!$rootFlow) {
            throw new \Exception('No root flow found for this USSD service');
        }

        $sessionEnvironment = $environment ?? $ussd->environment?->name ?? EnvironmentType::TESTING->value;
        
        // Get environment ID
        $environmentModel = Environment::where('name', $sessionEnvironment)->first();
        $environmentId = $environmentModel?->id;

        if ($sessionId) {
            // SECURITY: Validate session belongs to correct USSD
            $existingSession = USSDSession::where('session_id', $sessionId)
                ->where('ussd_id', $ussd->id) // SECURITY: Ensure session belongs to this USSD
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();

            if ($existingSession) {
                // SECURITY: Additional validation - verify phone number matches if provided
                if ($phoneNumber && $existingSession->phone_number !== $phoneNumber) {
                    Log::warning('Session phone number mismatch', [
                        'session_id' => $sessionId,
                        'expected_phone' => substr($existingSession->phone_number ?? '', 0, 4) . '****',
                        'provided_phone' => substr($phoneNumber, 0, 4) . '****',
                    ]);
                    // Don't reuse session if phone doesn't match
                } else {
                    // Reuse existing session - update activity, don't bill again
                    $existingSession->update([
                        'last_activity' => now(),
                    ]);
                    
                    return $existingSession;
                }
            }
        }

        // Create new session
        $session = USSDSession::create([
            'ussd_id' => $ussd->id,
            'environment_id' => $environmentId,
            'session_id' => Str::uuid(),
            'phone_number' => $phoneNumber,
            'current_flow_id' => $rootFlow->id,
            'status' => 'active',
            'step_count' => 0,
            'last_activity' => now(),
            'expires_at' => now()->addMinutes((int) config('ussd.session_timeout', 30)), // Configurable timeout
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'session_data' => [
                'session_environment' => $sessionEnvironment, // Store for reference
                'environment_override' => $environment !== null, // Track if environment was overridden
            ],
        ]);

        // Load the current flow to ensure it's available
        $session->load('currentFlow');

        // Log the session start
        $this->logSessionAction($session, 'session_start', null, $rootFlow->menu_text);
        
        // Handle billing based on environment
        if ($sessionEnvironment === EnvironmentType::TESTING->value) {
            // Simulate billing for testing sessions (no real charges)
            $this->simulateBilling($session);
        } elseif ($sessionEnvironment === EnvironmentType::PRODUCTION->value || $sessionEnvironment === 'live') {
            // Bill production/live sessions (real charges)
            // Only bill if session hasn't been billed yet (duplicate check)
            if (!$session->is_billed) {
                try {
                    DB::beginTransaction();
                    
                    // Record gateway cost first (what AfricasTalking charges)
                    $networkProvider = $this->gatewayCostService->detectNetworkProvider($phoneNumber);
                    $this->gatewayCostService->recordGatewayCost($session, $networkProvider);
                    
                    // Then bill the customer (what you charge them)
                    $billingResult = $this->billingService->billSession($session);
                    
                    // If billing failed (e.g., insufficient balance), mark session
                    if (!$billingResult) {
                        $session->update(['status' => 'error']);
                        DB::rollBack();
                    } else {
                        DB::commit();
                    }
                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::error('Failed to bill USSD session on start (simulator)', [
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
     * Simulate billing for testing sessions
     */
    private function simulateBilling(USSDSession $session): void
    {
        try {
            $ussd = $session->ussd;
            $business = $ussd->business;
            
            if (!$business || !$business->billing_enabled) {
                return;
            }

            // Calculate session cost (same as live billing)
            $sessionCost = $business->session_price ?? 0.02; // Default $0.02 per session
            
            // Update session with billing information
            $session->update([
                'is_billed' => true,
                'billing_amount' => $sessionCost,
                'billing_currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
                'billing_status' => 'testing', // Special status for testing
                'billed_at' => now(),
                'invoice_id' => 'TEST-' . Str::random(8)
            ]);

            // Get currency symbol
            $currency = $business->billing_currency ?? config('app.currency', 'NGN');
            $currencySymbol = $this->getCurrencySymbol($currency);
            
            // Log the simulated billing
            $this->logSessionAction($session, 'billing_simulated', null, "Session cost: {$currencySymbol}{$sessionCost} (simulated)");
            
        } catch (\Exception $e) {
            // Log error but don't fail the session
            Log::error('Billing simulation failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process user input and navigate to next flow
     */
    public function processInput(USSDSession $session, string $input): array
    {
        $startTime = microtime(true);
        
        try {
            // SECURITY: Validate session belongs to correct USSD
            if (!$session->ussd || !$session->ussd->is_active) {
                throw new \Exception('Invalid or inactive USSD service');
            }
            
            // SECURITY: Rate limiting - check phone number rate limit
            $rateLimitService = app(SessionRateLimitService::class);
            if ($session->phone_number && !$rateLimitService->checkPhoneRateLimit($session->phone_number)) {
                throw new \Exception('Rate limit exceeded. Please try again later.');
            }
            
            // SECURITY: Rate limiting - check session rate limit
            if ($session->session_id && !$rateLimitService->checkSessionRateLimit($session->session_id)) {
                throw new \Exception('Session rate limit exceeded. Please start a new session.');
            }
            
            // Update session activity
            $session->update([
                'last_activity' => now(),
                'step_count' => $session->step_count + 1,
            ]);

            // Simulate billing for testing sessions (per step)
            if ($session->ussd->environment && $session->ussd->environment->name === EnvironmentType::TESTING->value && !$session->is_billed) {
                $this->simulateBilling($session);
            }

            $currentFlow = $session->currentFlow;
            
            if (!$currentFlow) {
                throw new \Exception('No current flow found');
            }

            // Check if we're in an input collection state
            $sessionData = $session->session_data ?? [];
            $isCollectingInput = $sessionData['collecting_input'] ?? false;
            $inputType = $sessionData['input_type'] ?? null;
            $inputPrompt = $sessionData['input_prompt'] ?? null;

            if ($isCollectingInput && $inputType) {
        
                return $this->handleInputCollection($session, $input, $inputType, $inputPrompt);
            }
            
            $lastApiError = $sessionData['last_api_error'] ?? null;
            $inErrorFlow = $sessionData['in_error_flow'] ?? false;
            $lastInputType = $sessionData['last_input_type_before_api'] ?? null;
            $lastInputFlowId = $sessionData['last_input_flow_id'] ?? null;
            
            
            if ($inErrorFlow && $lastApiError && $lastInputType && $lastInputFlowId && !empty($input) && empty($lastSelection)) {
                // Clear the old input value so new input can be collected
                $inputFieldNames = [$lastInputType, str_replace('input_', '', $lastInputType)];
                foreach ($inputFieldNames as $fieldName) {
                    unset($sessionData[$fieldName]);
                    unset($sessionData['collected_inputs'][$fieldName]);
                    unset($sessionData['selected_item_data'][$fieldName]);
                }
                
                // Find the input option that collected this field before
                $inputFlow = USSDFlow::find($lastInputFlowId);
                if ($inputFlow) {
                    $inputOption = USSDFlowOption::where('flow_id', $inputFlow->id)
                        ->where('action_type', $lastInputType)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($inputOption) {
                        $session->update(['current_flow_id' => $inputFlow->id]);
                        $sessionData['collecting_input'] = true;
                        $sessionData['input_type'] = $lastInputType;
                        $sessionData['input_prompt'] = $inputOption->action_data['prompt'] ?? $this->getDefaultPrompt($lastInputType, $inputOption->option_text);
                        $sessionData['input_action_data'] = $inputOption->action_data ?? [];
                        $sessionData['in_error_flow'] = false;
                        unset($sessionData['error_flow_id']);
                        $session->update(['session_data' => $sessionData]);
                        
                        $this->logSessionAction($session, 'input_retry_after_api_error', $input, "Re-prompting for {$lastInputType} to retry failed API call", 'success');
                        
                        return $this->handleInputCollection($session, $input, $lastInputType, $sessionData['input_prompt']);
                    }
                }
            }

            // If input is empty (first request), just show the current flow menu
            if (empty($input) || trim($input) === '') {
                // IMPORTANT: If we're retrying input after API error and collecting_input is set,
                // show the input prompt again instead of processing the flow menu
                if ($isCollectingInput && isset($sessionData['retrying_input_after_api_error']) && $sessionData['retrying_input_after_api_error']) {
                    return [
                        'success' => true,
                        'message' => $inputPrompt ?? 'Please enter your input:',
                        'flow_title' => $currentFlow->getProcessedTitle($session),
                        'flow_description' => $currentFlow->description,
                        'requires_input' => true,
                        'current_flow' => $currentFlow,
                        'input_type' => $inputType,
                    ];
                }
                
                // For dynamic flows, process the flow to get the menu
                if ($currentFlow->flow_type === 'dynamic') {
                    return $this->processDynamicFlow($session, $currentFlow);
                }
                
                // For static flows, display the menu directly
                // Check if this flow has ONLY input actions - if so, auto-trigger the first one
                $options = $currentFlow->options()->where('is_active', true)->orderBy('sort_order')->get();
                
                // Only auto-trigger if ALL options are input actions
                // An action is an input action if it starts with 'input_' or is 'input_collection'
                if ($options->count() > 0) {
                    $allAreInputActions = $options->every(function ($option) {
                        return str_starts_with($option->action_type, 'input_') || $option->action_type === 'input_collection';
                    });
                    
                    if ($allAreInputActions) {
                        $firstOption = $options->first();
                        return $this->handleInputRequest($session, $firstOption);
                    }
                }
                
                // Otherwise, show the menu
                return [
                    'success' => true,
                    'message' => $currentFlow->getFullDisplayText($session),
                    'flow_title' => $currentFlow->getProcessedTitle($session),
                    'flow_description' => $currentFlow->description,
                    'requires_input' => true,
                    'current_flow' => $currentFlow,
                ];
            }

            // Extract the last selection from cumulative input (e.g., "1*1*1*2" -> "2")
            // AfricasTalking sends cumulative input, but we only need the last selection
            $lastSelection = $this->extractLastSelection($input);
            
            // Get session data and check retry limits
            $sessionData = $session->session_data ?? [];
            $maxRetries = config('ussd.max_input_retries', 3);
            
            // Track retry attempts per input (keyed by flow_id and input value)
            $retryKey = $currentFlow->id . '_' . $lastSelection;
            $retryCount = $sessionData['input_retries'][$retryKey] ?? 0;
            
            // Check if retry limit exceeded
            if ($maxRetries > 0 && $retryCount >= $maxRetries) {
                $this->logSessionAction($session, 'max_retries_exceeded', $lastSelection, 'Maximum retry attempts exceeded', 'error');
                
                // End session gracefully with friendly message (like exit option)
                return $this->handleEndSession($session, null, 'Thank you for using our service.');
            }
            
            // Process input - only log errors if needed

            // Handle dynamic flow selection
            if ($currentFlow->flow_type === 'dynamic') {
                return $this->handleDynamicFlowSelection($session, $lastSelection, $currentFlow);
            }
            
            // Check if we're in an error flow state and user wants to retry
            $lastApiError = $sessionData['last_api_error'] ?? null;
            $inErrorFlow = $sessionData['in_error_flow'] ?? false;
            
            // If in error flow and user selects the same option that failed, retry it
            if ($inErrorFlow && $lastApiError && $lastApiError['option_value'] === $lastSelection) {
                // Increment retry count
                if (!isset($sessionData['input_retries'])) {
                    $sessionData['input_retries'] = [];
                }
                $sessionData['input_retries'][$retryKey] = ($sessionData['input_retries'][$retryKey] ?? 0) + 1;
                
                // If there was an input collection before this API call, allow retry by re-prompting for that input
                // This handles cases where the API error might be due to incorrect input (PIN, password, account number, etc.)
                $lastInputType = $sessionData['last_input_type_before_api'] ?? null;
                $lastInputFlowId = $sessionData['last_input_flow_id'] ?? $sessionData['previous_flow_id'] ?? null;
                
                if ($lastInputType && $lastInputFlowId) {
                    // Clear the old input value (common field names)
                    $inputFieldNames = [$lastInputType, str_replace('input_', '', $lastInputType)];
                    foreach ($inputFieldNames as $fieldName) {
                        unset($sessionData[$fieldName]);
                        unset($sessionData['collected_inputs'][$fieldName]);
                        unset($sessionData['collected_inputs'][$lastInputType]);
                        unset($sessionData['selected_item_data'][$fieldName]);
                        unset($sessionData['selected_item_data'][$lastInputType]);
                    }
                    
                    // Try to find the input option that collected this field
                    $inputFlow = USSDFlow::find($lastInputFlowId);
                    
                    if ($inputFlow) {
                        // Find input action option by the stored input type
                        $inputOption = USSDFlowOption::where('flow_id', $inputFlow->id)
                            ->where('action_type', $lastInputType)
                            ->where('is_active', true)
                            ->first();
                        
                        if ($inputOption) {
                $session->update(['session_data' => $sessionData]);
                            $this->logSessionAction($session, 'api_retry_with_new_input', $lastSelection, "Re-prompting for {$lastInputType} to retry failed API call", 'success');
                            
                            // Navigate back to input collection flow to re-prompt for input
                            $session->update(['current_flow_id' => $inputFlow->id]);
                            return $this->handleInputRequest($session, $inputOption);
                        }
                    }
                }
                
                $session->update(['session_data' => $sessionData]);
                
                // Get original flow and option to retry
                $originalFlowId = $lastApiError['flow_id'] ?? $session->current_flow_id; // Fallback to current flow if not set
                $originalOptionId = $lastApiError['option_id'] ?? null;
                
                // If we have option ID, use it; otherwise try to find option by value in current/original flow
                if ($originalOptionId) {
                    $originalOption = USSDFlowOption::find($originalOptionId);
                    $originalFlow = $originalOption ? USSDFlow::find($originalOption->flow_id) : null;
                } else {
                    // Fallback: find option by value in the original/current flow
                    $originalFlow = USSDFlow::find($originalFlowId);
                    if ($originalFlow) {
                        $originalOption = USSDFlowOption::where('flow_id', $originalFlow->id)
                            ->where('option_value', $lastSelection)
                            ->where('is_active', true)
                            ->first();
                    } else {
                        $originalOption = null;
                    }
                }
                    
                    if ($originalFlow && $originalOption && $originalFlow->is_active && $originalOption->is_active) {
                        // Clear error state
                        unset($sessionData['in_error_flow']);
                        unset($sessionData['error_flow_id']);
                        $session->update([
                        'current_flow_id' => $originalFlow->id,
                            'session_data' => $sessionData
                        ]);
                        
                    // Retry the API call with existing input (for non-credential errors or if we couldn't find input flow)
                        $this->logSessionAction($session, 'api_retry', $lastSelection, "Retrying failed API call (attempt " . ($retryCount + 1) . ")", 'success');
                        return $this->handleAction($session, $originalOption);
                }
            }
            
            // Find the option that matches user input (static flows)
            $selectedOption = USSDFlowOption::where('flow_id', $currentFlow->id)
                ->where('option_value', $lastSelection)
                ->where('is_active', true)
                ->first();

            if (!$selectedOption) {
                // Increment retry count for invalid input
                if (!isset($sessionData['input_retries'])) {
                    $sessionData['input_retries'] = [];
                }
                $sessionData['input_retries'][$retryKey] = ($sessionData['input_retries'][$retryKey] ?? 0) + 1;
                $session->update(['session_data' => $sessionData]);
                
                // Check retry limit for invalid input
                $currentRetryCount = $sessionData['input_retries'][$retryKey] ?? 0;
                if ($maxRetries > 0 && $currentRetryCount >= $maxRetries) {
                    $this->logSessionAction($session, 'max_retries_exceeded', $lastSelection, 'Maximum retry attempts exceeded', 'error');
                    
                    // End session gracefully with friendly message (like exit option)
                    return $this->handleEndSession($session, null, 'Thank you for using our service.');
                }
                
                // SECURITY: Invalid input - log for security monitoring
                // This could indicate an attack attempt (trying invalid options)
                Log::warning('Invalid USSD option selected', [
                    'session_id' => $session->id,
                    'ussd_id' => $session->ussd_id,
                    'invalid_input' => $lastSelection,
                    'flow_id' => $currentFlow->id,
                    'phone_number' => $session->phone_number,
                    'ip_address' => $session->ip_address,
                    'retry_count' => $currentRetryCount,
                ]);
                
                // Invalid input - show error message with menu (use processed display text)
                $menuText = $currentFlow->getFullDisplayText($session);
                $errorMessage = "Invalid option. Please try again.\n\n" . $menuText;
                $this->logSessionAction($session, 'invalid_input', $lastSelection, $errorMessage, 'error');
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'flow_title' => null, // Don't prepend title since message already contains full menu
                    'requires_input' => true,
                    'current_flow' => $currentFlow,
                ];
            }

            // Reset retry count on successful input selection
            if (isset($sessionData['input_retries'][$retryKey])) {
                unset($sessionData['input_retries'][$retryKey]);
                $session->update(['session_data' => $sessionData]);
            }

            // Log the user input
            $this->logSessionAction($session, 'user_input', $lastSelection, $selectedOption->option_text);

            // Handle different action types
            $response = $this->handleAction($session, $selectedOption);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log the response
            $this->logSessionAction(
                $session, 
                'menu_display', 
                null, 
                $response['message'], 
                'success',
                $responseTime
            );

            return $response;

        } catch (\Exception $e) {
            $errorMessage = "An error occurred. Please try again.";
            $lastSelection = $this->extractLastSelection($input ?? '');
            $this->logSessionAction($session, 'error', $lastSelection, $errorMessage, 'error', null, $e->getMessage());
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'flow_title' => $currentFlow->title ?? 'Menu',
                'requires_input' => true,
                'current_flow' => $currentFlow ?? null,
            ];
        }
    }

    /**
     * Extract the last selection from cumulative input
     * AfricasTalking sends cumulative input like "1*1*1*2"
     * We need to extract just the last part "2"
     */
    private function extractLastSelection(string $input): string
    {
        // SECURITY: Sanitize and validate input
        // Check for actual empty string, not using empty() which treats "0" as empty
        if ($input === '' || $input === null) {
            return '';
        }

        // SECURITY: Sanitize USSD selection input
        $sanitizationService = app(SanitizationService::class);
        $input = $sanitizationService->sanitizeInput($input, 'ussd_selection');

        // If input contains "*", extract the last part
        if (strpos($input, '*') !== false) {
            $parts = explode('*', $input);
            $lastPart = end($parts);
            return trim($lastPart);
        }

        // If no "*", return the sanitized input
        return trim($input);
    }

    /**
     * Get the current flow display (handles both static and dynamic flows)
     */
    public function getCurrentFlowDisplay(USSDSession $session): array
    {
        $currentFlow = $session->currentFlow;
        
        if (!$currentFlow) {
            Log::error('No current flow found', [
                'session_id' => $session->id,
                'current_flow_id' => $session->current_flow_id
            ]);
            return [
                'success' => false,
                'message' => 'No current flow found',
                'requires_input' => false
            ];
        }
        
        // Check if this is a dynamic flow
        if ($currentFlow->flow_type === 'dynamic') {
            // Check if we already have cached API data for pagination
            $sessionData = $session->session_data ?? [];
            
            // Only use cached data if it belongs to the current flow
            $cachedFlowId = $sessionData['cached_flow_id'] ?? null;
            if (isset($sessionData['cached_api_data']) && 
                isset($sessionData['dynamic_options']) && 
                $cachedFlowId === $currentFlow->id) {
                // Use cached data for pagination - no need to make another API call
                return $this->regenerateDynamicFlowFromCache($session, $currentFlow);
            }
            
            // First time, no cache, or cache belongs to different flow - make API call
            return $this->processDynamicFlow($session, $currentFlow);
        }
        
        // Handle static flow (existing logic)
        
        // Check if this flow has ONLY input actions - if so, auto-trigger the first one
        // This provides better UX: if all options are inputs, skip the menu and go straight to input
        $options = $currentFlow->options()->where('is_active', true)->orderBy('sort_order')->get();
        
        // Only auto-trigger if ALL options are input actions (not just one)
        // This ensures we show menu when there's a real choice (mixed actions)
        // An action is an input action if it starts with 'input_' or is 'input_collection'
        if ($options->count() > 0) {
            $allAreInputActions = $options->every(function ($option) {
                return str_starts_with($option->action_type, 'input_') || $option->action_type === 'input_collection';
            });
            
            if ($allAreInputActions) {
                $firstOption = $options->first();
                return $this->handleInputRequest($session, $firstOption);
            }
        }
        
        return [
            'success' => true,
            'message' => $currentFlow->getFullDisplayText($session),
            'flow_title' => $currentFlow->getProcessedTitle($session),
            'flow_description' => $currentFlow->description,
            'requires_input' => true,
            'current_flow' => $currentFlow,
        ];
    }
    
    /**
     * Process dynamic flow and generate menu from API response
     */
    private function processDynamicFlow(USSDSession $session, USSDFlow $flow): array
    {
        $dynamicProcessor = app(DynamicFlowProcessor::class);
        $result = $dynamicProcessor->processDynamicFlow($flow, $session);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Dynamic flow processing failed',
                'flow_title' => $result['title'] ?? $flow->title,
                'flow_description' => $flow->description,
                'requires_input' => false,
                'current_flow' => $flow,
            ];
        }
        
        // Store the dynamic options and cache the API data in session for pagination
        // Key the cache by flow_id to prevent cross-flow cache contamination
        $sessionData = $session->session_data ?? [];
        $continuationType = $result['continuation_type'] ?? 'continue';
        $nextFlowId = $result['next_flow_id'] ?? null;
        
        // Log what we received from DynamicFlowProcessor
        Log::info('USSDSessionService: received from DynamicFlowProcessor', [
            'flow_id' => $flow->id,
            'continuation_type' => $continuationType,
            'next_flow_id_from_result' => $result['next_flow_id'] ?? 'not_set',
            'next_flow_id_type' => gettype($result['next_flow_id'] ?? null),
            'result_keys' => array_keys($result)
        ]);
        
        // Normalize next_flow_id - convert empty string to null, ensure it's a valid ID
        if ($nextFlowId === '' || $nextFlowId === '0') {
            $nextFlowId = null;
        } else if ($nextFlowId !== null && $nextFlowId !== '') {
            $nextFlowId = (int) $nextFlowId; // Ensure it's an integer for database queries
        }
        
        // Handle continue without display - skip display and navigate directly
        if ($continuationType === 'continue_without_display') {
            // Validate that next_flow_id is configured
            if (!$nextFlowId) {
                Log::warning('continue_without_display requires next_flow_id', [
                    'session_id' => $session->id,
                    'flow_id' => $flow->id,
                    'flow_name' => $flow->name,
                    'next_flow_id_from_result' => $result['next_flow_id'] ?? 'not_set',
                    'next_flow_id_after_normalization' => $nextFlowId,
                    'dynamic_config' => $flow->dynamic_config ?? []
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Configuration error: Next flow must be specified for "Continue without display" option.',
                    'flow_title' => $flow->title ?? $flow->name ?? 'Menu',
                    'flow_description' => $flow->description,
                    'requires_input' => false,
                    'current_flow' => $flow,
                ];
            }
            
            // Store API response data (similar to handleApiCallSuccess)
            $cachedApiData = $result['cached_api_data'] ?? null;
            
            if ($cachedApiData) {
                // Ensure cachedApiData is an array for consistent handling
                if (!is_array($cachedApiData)) {
                    // If it's a scalar value (string, number, etc.), wrap it
                    $cachedApiData = ['value' => $cachedApiData, 'data' => $cachedApiData];
                }
                
                // Store API response in structured way for easy access
                $sessionData['api_response'] = $cachedApiData;
                $sessionData['last_api_result'] = [
                    'success' => true,
                    'data' => $cachedApiData
                ];
                
                // Extract scalar fields from API response to top-level session_data
                foreach ($cachedApiData as $key => $value) {
                    if (is_scalar($value) || is_null($value)) {
                        if (!isset($sessionData[$key])) {
                            $sessionData[$key] = $value;
                        }
                        $sessionData['api_' . $key] = $value;
                    }
                }
                
                // Special handling: if 'data' is a string (like validation responses), 
                // make it easily accessible as 'data' in session_data
                if (isset($cachedApiData['data']) && is_string($cachedApiData['data'])) {
                    $sessionData['data'] = $cachedApiData['data'];
                    // Also store it with a more descriptive name if available
                    if (isset($cachedApiData['validation_result'])) {
                        $sessionData['validation_result'] = $cachedApiData['validation_result'];
                    } else if (isset($cachedApiData['value'])) {
                        $sessionData['value'] = $cachedApiData['value'];
                    }
                }
            }
            
            // Navigate to next flow
            $nextFlow = USSDFlow::find($nextFlowId);
            if (!$nextFlow || !$nextFlow->is_active) {
                Log::warning('Next flow not found or inactive for continue_without_display', [
                    'session_id' => $session->id,
                    'flow_id' => $flow->id,
                    'next_flow_id' => $nextFlowId,
                    'flow_exists' => $nextFlow !== null,
                    'flow_active' => $nextFlow ? $nextFlow->is_active : false
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Configuration error: The specified next flow is not available.',
                    'flow_title' => $flow->title ?? $flow->name ?? 'Menu',
                    'flow_description' => $flow->description,
                    'requires_input' => false,
                    'current_flow' => $flow,
                ];
            }
            
            // Update session with stored API data and navigate to next flow
            $session->update(['current_flow_id' => $nextFlow->id, 'session_data' => $sessionData]);
            $session->refresh();
            $sessionData = $session->session_data ?? [];
            
            // Log the silent navigation
            $this->logSessionAction($session, 'dynamic_flow_silent_continue', null, "Navigated from flow {$flow->id} to {$nextFlowId} without displaying options", 'success');
            
            // Replace placeholders in menu text with API response and session data
            $menuText = $this->replacePlaceholdersWithApiAndSessionData(
                $nextFlow->menu_text,
                $cachedApiData ?? [],
                $sessionData
            );
            
            // Process title with placeholders too
            $processedTitle = !empty($nextFlow->title) 
                ? $this->replacePlaceholdersWithApiAndSessionData($nextFlow->title, $cachedApiData ?? [], $sessionData)
                : '';
            
            // Combine title and menu text if title has content
            $fullMessage = !empty($processedTitle) 
                ? $processedTitle . "\n\n" . $menuText
                : $menuText;
            
            return [
                'success' => true,
                'message' => $fullMessage,
                // Don't set flow_title when title is already included in message
                'flow_title' => null,
                'requires_input' => true,
                'current_flow' => $nextFlow,
                'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
            ];
        }
        
        // Normal dynamic flow display (existing logic)
        $sessionData['dynamic_options'] = $result['options'] ?? [];
        $sessionData['dynamic_continuation_type'] = $continuationType;
        $sessionData['dynamic_next_flow_id'] = $nextFlowId;
        $sessionData['cached_api_data'] = $result['cached_api_data'] ?? null; // Cache the raw API data
        $sessionData['cached_flow_id'] = $flow->id; // Store which flow this cache belongs to
        $session->update(['session_data' => $sessionData]);
        
        // Format the message with dynamic options
        $flowTitle = $result['title'] ?? $flow->title ?? $flow->name ?? 'Menu';
        $message = $flowTitle;
        if (!empty($result['options'])) {
            $message .= "\n";
            foreach ($result['options'] as $index => $option) {
                $message .= ($index + 1) . ". " . $option['label'] . "\n";
            }
        } else {
            $message .= "\n" . ($result['message'] ?? 'No options available');
        }
        
        return [
            'success' => true,
            'message' => $message,
            'flow_title' => $flowTitle,
            'flow_description' => $flow->description,
            'requires_input' => !empty($result['options']),
            'current_flow' => $flow,
            'dynamic_options' => $result['options'] ?? [],
        ];
    }
    
    /**
     * Regenerate dynamic flow display from cached API data (for pagination)
     */
    private function regenerateDynamicFlowFromCache(USSDSession $session, USSDFlow $flow): array
    {
        $sessionData = $session->session_data ?? [];
        $cachedApiData = $sessionData['cached_api_data'] ?? null;
        $dynamicConfig = $flow->dynamic_config ?? [];
        
        if (!$cachedApiData) {
            Log::warning('No cached API data found, falling back to API call', [
                'session_id' => $session->id,
                'flow_id' => $flow->id
            ]);
            // Fallback to making a new API call if cache is missing
            return $this->processDynamicFlow($session, $flow);
        }
        
        // Use cached data to regenerate options with current page
        $dynamicProcessor = app(DynamicFlowProcessor::class);
        $options = $dynamicProcessor->formatApiResponseToOptions($cachedApiData, $dynamicConfig, $session);
        
        // Update the dynamic options in session
        $sessionData['dynamic_options'] = $options;
        $session->update(['session_data' => $sessionData]);
        
        // Format the message with dynamic options
        $sanitizationService = app(SanitizationService::class);
        $flowTitle = $flow->title ?? $flow->name ?? 'Menu';
        $flowTitle = $sanitizationService->sanitizeOutput($flowTitle, 200);
        
        $message = $flowTitle;
        if (!empty($options)) {
            $message .= "\n";
            foreach ($options as $index => $option) {
                $label = $sanitizationService->sanitizeOutput($option['label'] ?? '', 100);
                $message .= ($index + 1) . ". " . $label . "\n";
            }
        } else {
            $emptyMsg = $sanitizationService->sanitizeOutput($dynamicConfig['empty_message'] ?? 'No options available', 200);
            $message .= "\n" . $emptyMsg;
        }
        
        return [
            'success' => true,
            'message' => $message,
            'flow_title' => $flowTitle,
            'flow_description' => $flow->description,
            'requires_input' => !empty($options),
            'current_flow' => $flow,
            'dynamic_options' => $options,
        ];
    }
    
    /**
     * Handle dynamic flow selection
     */
    private function handleDynamicFlowSelection(USSDSession $session, string $input, USSDFlow $flow): array
    {
        $sessionData = $session->session_data ?? [];
        $dynamicOptions = $sessionData['dynamic_options'] ?? [];
        
        // Track retry attempts for dynamic flow selections
        $maxRetries = config('ussd.max_input_retries', 3);
        $retryKey = $flow->id . '_' . $input;
        $retryCount = $sessionData['input_retries'][$retryKey] ?? 0;
        
        // Check if we're retrying a failed dynamic flow selection with API call
        $lastApiError = $sessionData['last_api_error'] ?? null;
        $inErrorFlow = $sessionData['in_error_flow'] ?? false;
        $lastDynamicError = $sessionData['last_dynamic_error'] ?? null;
        
        // If we're in an error state and user selects the same option that failed, retry it
        if (($inErrorFlow || $lastDynamicError) && $lastDynamicError && $lastDynamicError['flow_id'] == $flow->id && $lastDynamicError['option_index'] == $input) {
            // Increment retry count
            if (!isset($sessionData['input_retries'])) {
                $sessionData['input_retries'] = [];
            }
            $sessionData['input_retries'][$retryKey] = ($sessionData['input_retries'][$retryKey] ?? 0) + 1;
            $currentRetryCount = $sessionData['input_retries'][$retryKey] ?? 0;
            
            // Check if retry limit exceeded
            if ($maxRetries > 0 && $currentRetryCount >= $maxRetries) {
                $this->logSessionAction($session, 'max_retries_exceeded', $input, 'Maximum retry attempts exceeded', 'error');
                return $this->handleEndSession($session, null, 'Thank you for using our service.');
            }
            
            $session->update(['session_data' => $sessionData]);
            
            // Retry: Re-select the same dynamic option and process it again
            $inputNumber = (int) $input;
            if ($inputNumber > 0 && $inputNumber <= count($dynamicOptions)) {
                $selectedOption = $dynamicOptions[$inputNumber - 1];
                
                // Clear error state before retrying
                unset($sessionData['in_error_flow']);
                unset($sessionData['error_flow_id']);
                unset($sessionData['last_dynamic_error']);
                $session->update(['session_data' => $sessionData]);
                
                // Log retry
                $this->logSessionAction($session, 'dynamic_retry', $input, "Retrying dynamic flow selection (attempt {$currentRetryCount})", 'success');
                
                // Continue with normal processing of the selected option
                // (will fall through to the rest of the function)
                
            }
        }
        
        // Find the selected option from dynamic options
        $selectedOption = null;
        $inputNumber = (int) $input;
        
        if ($inputNumber > 0 && $inputNumber <= count($dynamicOptions)) {
            $selectedOption = $dynamicOptions[$inputNumber - 1];
        }

        
        // Handle pagination navigation
        if ($selectedOption && in_array($selectedOption['value'], ['PAGINATION_NEXT', 'PAGINATION_BACK'])) {
            $newPage = $selectedOption['data']['page'] ?? 1;
            
            // Update session data with new page
            $sessionData['current_page'] = $newPage;
            $session->update(['session_data' => $sessionData]);
            
            // Log pagination action
            $this->logSessionAction($session, 'pagination', $input, "Page {$newPage}");
            
            // Regenerate the dynamic flow display with new page
            try {
                $flowDisplay = $this->getCurrentFlowDisplay($session);
                
                return [
                    'success' => true,
                    'message' => $flowDisplay['message'],
                    'flow_title' => $flowDisplay['flow_title'] ?? $flow->title ?? $flow->name ?? 'Menu',
                    'requires_input' => true,
                    'current_flow' => $flow,
                ];
            } catch (\Exception $e) {
                Log::error('Pagination error in getCurrentFlowDisplay', [
                    'session_id' => $session->id,
                    'flow_id' => $flow->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Pagination error occurred. Please try again.',
                    'flow_title' => $flow->title ?? $flow->name ?? 'Menu',
                    'requires_input' => true,
                    'current_flow' => $flow,
                ];
            }
        }
        
        if (!$selectedOption) {
            // Increment retry count for invalid dynamic flow selection
            if (!isset($sessionData['input_retries'])) {
                $sessionData['input_retries'] = [];
            }
            $sessionData['input_retries'][$retryKey] = ($sessionData['input_retries'][$retryKey] ?? 0) + 1;
            $currentRetryCount = $sessionData['input_retries'][$retryKey] ?? 0;
            
            // Check if retry limit exceeded
            if ($maxRetries > 0 && $currentRetryCount >= $maxRetries) {
                $this->logSessionAction($session, 'max_retries_exceeded', $input, 'Maximum retry attempts exceeded', 'error');
                
                // End session gracefully with friendly message (like exit option)
                return $this->handleEndSession($session, null, 'Thank you for using our service.');
            }
            
            $session->update(['session_data' => $sessionData]);
            
            $dynamicOptions = $sessionData['dynamic_options'] ?? [];
            
            // Format options only (no title)
            $optionsText = '';
            if (!empty($dynamicOptions)) {
                $sanitizationService = app(SanitizationService::class);
                foreach ($dynamicOptions as $index => $option) {
                    if ($index > 0) $optionsText .= "\n";
                    $label = $sanitizationService->sanitizeOutput($option['label'] ?? '', 100);
                    $optionsText .= ($index + 1) . ". " . $label;
                }
            }
            
            $errorMessage = "Invalid option. Please try again." . (!empty($optionsText) ? "\n\n" . $optionsText : '');
            $this->logSessionAction($session, 'invalid_input', $input, $errorMessage, 'error');
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'flow_title' => null, // Standard practice: no title on error messages
                'requires_input' => true,
                'current_flow' => $flow,
            ];
        }
        
        // Reset retry count on successful dynamic flow selection
        if (isset($sessionData['input_retries'][$retryKey])) {
            unset($sessionData['input_retries'][$retryKey]);
            $session->update(['session_data' => $sessionData]);
        }
        
        // Log the user input
        $this->logSessionAction($session, 'user_input', $input, $selectedOption['label']);
        
        // Store the selected option data in session
        $sessionData['selected_dynamic_option'] = $selectedOption;
        $sessionData['last_dynamic_selection_index'] = $input; // Store the option index for retry detection
        $sessionData['previous_flow_id'] = $flow->id; // Store current flow ID before navigation
        
        // Store the full item data for use in subsequent flows
        if (isset($selectedOption['data'])) {
            $itemData = $selectedOption['data'];
            
            // Store the full item data
            $sessionData['selected_item_data'] = $itemData;
            $sessionData['selected_item_value'] = $selectedOption['value'];
            $sessionData['selected_item_label'] = $selectedOption['label'];
            
            // Dynamically extract all fields from selected_item_data to top level for easier template access
            // This allows using {session.data.coded} instead of {session.data.selected_item_data.coded}
            // Only extract scalar values (strings, numbers, booleans) to avoid overwriting with complex structures
            foreach ($itemData as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    // Only extract if key doesn't already exist at top level (to avoid overwriting important session data)
                    if (!isset($sessionData[$key])) {
                        $sessionData[$key] = $value;
                    }
                }
            }
            
            // Store phone number if not already set
            if (!isset($sessionData['phone_number'])) {
                $sessionData['phone_number'] = $session->phone_number ?? 'Not provided';
            }
        }
        
        $session->update(['session_data' => $sessionData]);
        
        // Determine next step based on continuation type
        $dynamicProcessor = app(DynamicFlowProcessor::class);
        $nextStep = $dynamicProcessor->determineNextStep($flow, $selectedOption['value'], $session);
        
        switch ($nextStep['action']) {
            case 'end_session':
                return $this->handleEndSession($session, null, $nextStep['message'] ?? 'Thank you for using our service!');
                
            case 'navigate':
                $nextFlow = USSDFlow::find($nextStep['next_flow_id']);
                if ($nextFlow) {
                    return $this->handleNavigation($session, null, $nextFlow);
                }
                return $this->handleEndSession($session, null, 'Session completed');
                
            default:
                return $this->handleEndSession($session, null, 'Session completed');
        }
    }

    /**
     * Handle different action types
     */
    private function handleAction(USSDSession $session, USSDFlowOption $option): array
    {
        $actionData = $option->action_data ?? [];
        if (is_object($actionData)) {
            $actionData = (array) $actionData;
        }
        
        $sessionData = $session->session_data ?? [];
        
        if (isset($actionData['store_data']) && is_array($actionData['store_data'])) {
            foreach ($actionData['store_data'] as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    $finalValue = $value;
                    if (empty($value) || (is_string($value) && trim($value) === '')) {
                        $finalValue = $option->option_text ?? '';
                    }
                    
                    if (!isset($sessionData[$key])) {
                        $sessionData[$key] = $finalValue;
                    }
                }
            }
        }
        
        // Automatically extract scalar fields from action_data (fallback)
        $excludedFields = [
            'store_data', 'use_registered_phone', 'message', 'prompt', 'error_message', 
            'success_message', 'api_configuration_id', 'success_flow_id', 'error_flow_id',
            'end_session_after_api', 'after_input_action', 'process_type', 'next_flow_id'
        ];
        
        foreach ($actionData as $key => $value) {
            if (in_array($key, $excludedFields) || !is_scalar($value)) {
                continue;
            }
            
            if (!isset($sessionData[$key])) {
                $sessionData[$key] = $value;
            }
        }
        
        if (!empty($actionData['use_registered_phone'])) {
            $sessionData['recipient_phone'] = $session->phone_number;
            $sessionData['recipient_type'] = 'self';
        }
        
        $sessionData['selected_static_option'] = [
            'option_text' => $option->option_text,
            'option_value' => $option->option_value,
            'action_type' => $option->action_type,
            'action_data' => $actionData,
            'next_flow_id' => $option->next_flow_id,
        ];
        
        $itemData = [];
        
        // Add data from store_data (explicit configuration takes priority)
        if (isset($actionData['store_data']) && is_array($actionData['store_data'])) {
            foreach ($actionData['store_data'] as $key => $value) {
                // If value is empty, use option_text as fallback
                $finalValue = $value;
                if (empty($value) || (is_string($value) && trim($value) === '')) {
                    $finalValue = $option->option_text ?? '';
                }
                $itemData[$key] = $finalValue;
            }
        }
        
        // Add any other scalar fields from action_data (excluding config fields)
        $excludedFieldsForItemData = [
            'store_data', 'use_registered_phone', 'message', 'prompt', 'error_message', 
            'success_message', 'api_configuration_id', 'success_flow_id', 'error_flow_id',
            'end_session_after_api', 'after_input_action', 'process_type', 'next_flow_id'
        ];
        
        foreach ($actionData as $key => $value) {
            if (!in_array($key, $excludedFieldsForItemData) && (is_scalar($value) || is_null($value))) {
                $itemData[$key] = $value;
            }
        }
        
        // Always store selected option in selected_item_data (like dynamic flows)
        // This makes it available for template variables without explicit configuration
        // Store option_text as 'selected_value' for consistent access pattern
        if (empty($itemData)) {
            // If no explicit store_data, at least store the option_text
            $itemData['selected_value'] = trim($option->option_text);
            $itemData['option_text'] = trim($option->option_text);
            $itemData['option_value'] = $option->option_value;
        }
        
        // Always set selected_item_data (consistent with dynamic flows)
        $sessionData['selected_item_data'] = $itemData;
        $sessionData['selected_item_value'] = $option->option_value;
        $sessionData['selected_item_label'] = $option->option_text;
        
        // Extract scalar fields to top level for easier template access
        // This allows using {session.data.field} instead of {session.data.selected_item_data.field}
        foreach ($itemData as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                if (!isset($sessionData[$key])) {
                    $sessionData[$key] = $value;
                }
            }
        }
        
        $session->update(['session_data' => $sessionData]);
        
        switch ($option->action_type) {
            case 'navigate':
                return $this->handleNavigation($session, $option);
                
            case 'message':
                return $this->handleMessage($session, $option);
                
            case 'end_session':
                return $this->handleEndSession($session, $option);
                
            case 'api_call':
            case 'external_api_call':
                return $this->handleApiCall($session, $option);
                
            case 'input_text':
            case 'input_number':
            case 'input_phone':
            case 'input_account':
            case 'input_pin':
            case 'input_amount':
            case 'input_selection':
                return $this->handleInputRequest($session, $option);
                
            case 'process_registration':
                return $this->handleProcessRegistration($session, $option);
                
            case 'process_feedback':
                return $this->handleProcessFeedback($session, $option);
                
            case 'process_contact':
                return $this->handleProcessContact($session, $option);
                
            default:
                return $this->handleNavigation($session, $option);
        }
    }

    /**
     * Handle navigation to next flow
     */
private function handleNavigation(USSDSession $session, ?USSDFlowOption $option = null, ?USSDFlow $nextFlow = null): array
    {
        // If nextFlow is provided directly (for dynamic flows), use it
        if ($nextFlow) {
            $targetFlow = $nextFlow;
        } else {
            // Handle static flow navigation
            if (!$option || !$option->next_flow_id) {
            // No next flow - end session
            return $this->handleEndSession($session, $option);
        }

            $targetFlow = USSDFlow::find($option->next_flow_id);
            if (!$targetFlow) {
                return $this->handleEndSession($session, $option);
            }
        }

        if (!$targetFlow->is_active) {
            throw new \Exception('Target flow is inactive');
        }

        // Check if we're navigating back to the original flow after an API error
        // If so, and the user selects the same option that failed, automatically retry
        $sessionData = $session->session_data ?? [];
        $lastApiError = $sessionData['last_api_error'] ?? null;
        $inErrorFlow = $sessionData['in_error_flow'] ?? false;
        
        if ($inErrorFlow && $lastApiError && $targetFlow->id == $lastApiError['flow_id']) {
            // User navigated back to the original flow from error flow
            // Clear error state
            unset($sessionData['in_error_flow']);
            unset($sessionData['error_flow_id']);
            $session->update(['session_data' => $sessionData]);
        }

        // Update session to new flow
        $session->update([
            'current_flow_id' => $targetFlow->id,
        ]);

        // Refresh the session model to clear cached relationships
        $session->refresh();
        $session->load('currentFlow');

        // Get the flow display (handles both static and dynamic flows)
        return $this->getCurrentFlowDisplay($session);
    }

    /**
     * Handle message response
     */
    private function handleMessage(USSDSession $session, USSDFlowOption $option): array
    {
        $actionData = $option->action_data ?? [];
        if (is_object($actionData)) {
            $actionData = (array) $actionData;
        }
        $message = $actionData['message'] ?? 'Thank you for using our service.';
        
        // End session after showing message
        $this->completeSession($session);
        
        return [
            'success' => true,
            'message' => $message,
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Handle session end
     */
    private function handleEndSession(USSDSession $session, ?USSDFlowOption $option = null, ?string $customMessage = null): array
    {
        $actionData = $option?->action_data ?? [];
        if (is_object($actionData)) {
            $actionData = (array) $actionData;
        }
        $message = $customMessage ?? $actionData['message'] ?? 'Thank you for using our service.';
        
        // Append "Session Ended" if not already in the message
        if (stripos($message, 'Session Ended') === false) {
            $message .= "\n\nSession Ended";
        }
        
        $this->completeSession($session);
        
        return [
            'success' => true,
            'message' => $message,
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Handle API call (external API integration)
     */
    private function handleApiCall(USSDSession $session, USSDFlowOption $option): array
    {
        $apiData = $option->action_data ?? [];
        if (is_object($apiData)) {
            $apiData = (array) $apiData;
        }
        $apiConfigId = $apiData['api_configuration_id'] ?? null;
        
        // Resolve template variables in API configuration ID
        $apiConfigId = $this->resolveTemplateVariables($apiConfigId, $session);
        
        if (!$apiConfigId) {
            // Fallback to simulated API call for backward compatibility
            return $this->handleSimulatedApiCall($session, $option);
        }
        
        try {
            // Get API configuration
            $apiConfig = ExternalAPIConfiguration::find($apiConfigId);
            
            if (!$apiConfig || !$apiConfig->isValid()) {
                // If no specific API config found, try to find a marketplace API based on service
                $apiConfig = $this->findMarketplaceApiByService($session);
                
                if (!$apiConfig) {
                    throw new \Exception('API configuration not found or invalid');
                }
            }
            
            // Get user input from session data
            $sessionData = $session->session_data ?? [];
            $userInput = $sessionData['collected_input'] ?? [];
            
            // Execute external API call
            $externalApiService = new \App\Services\ExternalAPIService($this->loggingService);
            $result = $externalApiService->executeApiCall($apiConfig, $session, $userInput);
            
            if (!$result['success']) {

                return $this->handleApiCallError($session, $option, null, $result);
            }
            
            // Handle success response
            return $this->handleApiCallSuccess($session, $option, $result);
            
        } catch (\Exception $e) {
            // Log the error
            $this->logSessionAction($session, 'api_call_error', null, $e->getMessage(), 'error');
            
            // Handle error response (legacy exception-based error handling)
            return $this->handleApiCallError($session, $option, $e);
        }
    }

    /**
     * Handle simulated API call (for backward compatibility)
     */
    private function handleSimulatedApiCall(USSDSession $session, USSDFlowOption $option): array
    {
        // Simulate API call delay
        usleep(500000); // 0.5 seconds
        
        $apiData = $option->action_data ?? [];
        if (is_object($apiData)) {
            $apiData = (array) $apiData;
        }
        $message = $apiData['success_message'] ?? 'Operation completed successfully.';
        
        // Store result in session data
        $sessionData = $session->session_data ?? [];
        $sessionData['last_api_result'] = $apiData;
        $session->update(['session_data' => $sessionData]);
        
        return [
            'success' => true,
            'message' => $message,
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Handle successful API call
     */
    private function handleApiCallSuccess(USSDSession $session, USSDFlowOption $option, array $result): array
    {
        $apiData = $option->action_data ?? [];
        if (is_object($apiData)) {
            $apiData = (array) $apiData;
        }
        $endSessionAfterApi = $apiData['end_session_after_api'] ?? true;
        
        // Get API configuration to access data_path setting
        $apiConfigId = $apiData['api_configuration_id'] ?? null;
        $apiConfig = null;
        if ($apiConfigId) {
            $apiConfig = ExternalAPIConfiguration::find($apiConfigId);
        }
        
        // Store API result in session data
        $sessionData = $session->session_data ?? [];
        $sessionData['last_api_result'] = $result;
        
        // Extract API response data using configurable data_path
        // Priority: data_path > list_path (from dynamic_config) > 'data' (default)
        $dataPath = null;
        if ($apiConfig) {
            $dataPath = $apiConfig->data_path;
        }
        
        // If data_path is not set, try to get list_path from dynamic_config as fallback
        if (empty($dataPath)) {
            $currentFlow = $session->currentFlow;
            if ($currentFlow && $currentFlow->dynamic_config) {
                $dynamicConfig = is_array($currentFlow->dynamic_config) 
                    ? $currentFlow->dynamic_config 
                    : json_decode($currentFlow->dynamic_config, true);
                $dataPath = $dynamicConfig['list_path'] ?? null;
            }
        }
        
        // Final fallback to 'data' if nothing is configured
        if (empty($dataPath)) {
            $dataPath = 'data';
        }
        
        // Extract data from raw API response body (not from mapped data)
        // The raw_response.body contains the actual API response structure
        $fullResponseBody = $result['raw_response']['body'] ?? [];
        
        // Use data_get to extract from the configured path (supports dot notation)
        // Always use fullResponseBody as the source (raw API response)
        if (empty($dataPath) || $dataPath === 'data') {
            // Direct access to 'data' key in raw response
            $apiResponseData = data_get($fullResponseBody, 'data', []);
        } else {
            // Use data_get to extract from the configured path
            $apiResponseData = data_get($fullResponseBody, $dataPath, []);
        }
        
        // Handle case where data is a string (not an array)
        if (is_string($apiResponseData)) {
            // Store the string value directly as 'data' for {{session.data}} access
            $sessionData['data'] = $apiResponseData;
            $sessionData['api_data'] = $apiResponseData;
            $sessionData['api_response'] = ['data' => $apiResponseData];
        } else {
            // Handle array response
            $sessionData['api_response'] = $apiResponseData;
            
            // Store the full data object for {{session.data}} access
            if (is_array($apiResponseData)) {
                $sessionData['data'] = $apiResponseData;
            }
            
            // Extract individual fields for direct access
            foreach ($apiResponseData as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    if (!isset($sessionData[$key])) {
                        $sessionData[$key] = $value;
                    }
                    $sessionData['api_' . $key] = $value;
                }
            }
        }
        
        // Also store the full API response body for comprehensive access
        $sessionData['api_full_response'] = $result;
        
        // Reset retry count on successful API call
        $retryKey = $session->current_flow_id . '_' . ($option->option_value ?? 'api_call');
        if (isset($sessionData['input_retries'][$retryKey])) {
            unset($sessionData['input_retries'][$retryKey]);
        }
        // Also clear last_api_error on success
        if (isset($sessionData['last_api_error'])) {
            unset($sessionData['last_api_error']);
        }
        if (isset($sessionData['in_error_flow'])) {
            unset($sessionData['in_error_flow']);
        }
        if (isset($sessionData['error_flow_id'])) {
            unset($sessionData['error_flow_id']);
        }
        
        $session->update(['session_data' => $sessionData]);
        
        // Log successful API call
        $this->logSessionAction($session, 'api_call_success', null, json_encode($result['data']));
        
        if ($endSessionAfterApi) {
            // End session with success message
            $this->completeSession($session);
            
            return [
                'success' => true,
                'message' => $result['message'],
                'requires_input' => false,
                'session_ended' => true,
            ];
        } else {
            // Continue to next flow if specified
            // Check both next_flow_id (direct) and success_flow_id (from action_data) for compatibility
            $nextFlowId = $option->next_flow_id ?? $apiData['success_flow_id'] ?? null;
            $continueWithoutDisplay = $apiData['continue_without_display'] ?? false;
            
            if ($nextFlowId) {
                $nextFlow = USSDFlow::find($nextFlowId);
                if (!$nextFlow || !$nextFlow->is_active) {
                    // Next flow not found or inactive - end session
                    Log::warning('Next flow not found or inactive for external API call', [
                        'session_id' => $session->id,
                        'current_flow_id' => $session->current_flow_id,
                        'next_flow_id' => $nextFlowId,
                        'flow_exists' => $nextFlow !== null,
                        'flow_active' => $nextFlow ? $nextFlow->is_active : false
                    ]);
                    
                    $this->completeSession($session);
                    return [
                        'success' => false,
                        'message' => 'Configuration error: The specified next flow is not available.',
                        'requires_input' => false,
                        'session_ended' => true,
                    ];
                }
                
                // Update session to next flow
                    $session->update(['current_flow_id' => $nextFlow->id]);
                    
                // Refresh session data after update to get the latest session_data with API response
                $session->refresh();
                $sessionData = $session->session_data ?? [];
                
                // Replace placeholders in menu text with both API response data and session data
                // Pass the extracted API data (not mapped data) for {api.*} placeholders
                $extractedApiData = $sessionData['api_response'] ?? ($result['raw_response']['body'] ?? []);
                $menuText = $this->replacePlaceholdersWithApiAndSessionData($nextFlow->menu_text, $extractedApiData, $sessionData);
                
                // Process title with placeholders too
                $processedTitle = !empty($nextFlow->title) 
                    ? $this->replacePlaceholdersWithApiAndSessionData($nextFlow->title, $extractedApiData, $sessionData)
                    : '';
                
                if ($continueWithoutDisplay) {
                    // Silent navigation - don't display API message (matches dynamic flow behavior)
                    $this->logSessionAction($session, 'api_call_silent_continue', null, "Navigated from flow {$session->current_flow_id} to {$nextFlowId} without displaying API response", 'success');
                    
                    // Combine title and menu text if title has content
                    $fullMessage = !empty($processedTitle) 
                        ? $processedTitle . "\n\n" . $menuText
                        : $menuText;
                    
                    return [
                        'success' => true,
                        'message' => $fullMessage,
                        // Don't set flow_title when title is already included in message
                        // This prevents AfricasTalkingService from double-adding the title
                        'flow_title' => null,
                        'requires_input' => true,
                        'current_flow' => $nextFlow,
                        'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
                    ];
                } else {
                    // Default behavior: Show API message followed by next flow menu (backward compatible)
                    $apiMessage = $result['message'] ?? 'Operation completed successfully.';
                    
                    // Process title with placeholders too
                    $processedTitle = !empty($nextFlow->title) 
                        ? $this->replacePlaceholdersWithApiAndSessionData($nextFlow->title, $extractedApiData, $sessionData)
                        : '';
                    
                    // Combine title (if it has placeholders), API message, and menu text
                    $fullMessage = $apiMessage;
                    if (!empty($processedTitle)) {
                        $fullMessage = $processedTitle . "\n\n" . $fullMessage;
                    }
                    $fullMessage .= "\n\n" . $menuText;
                    
                    return [
                        'success' => true,
                        'message' => $fullMessage,
                        'flow_title' => $processedTitle,
                        'requires_input' => true,
                        'current_flow' => $nextFlow,
                        'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
                    ];
                }
            }
            
            // Default: end session
            $this->completeSession($session);
            return [
                'success' => true,
                'message' => $result['message'],
                'requires_input' => false,
                'session_ended' => true,
            ];
        }
    }

    /**
     * Handle API call error
     * Supports error message templates with placeholders and extracted API error messages
     * 
     * @param USSDSession $session
     * @param USSDFlowOption $option
     * @param \Exception|null $exception Legacy exception parameter (for backward compatibility)
     * @param array|null $apiResult API result array with error information (preferred)
     * @return array
     */
    private function handleApiCallError(USSDSession $session, USSDFlowOption $option, ?\Exception $exception = null, ?array $apiResult = null): array
    {
        $apiData = $option->action_data ?? [];
        if (is_object($apiData)) {
            $apiData = (array) $apiData;
        }
        
        // Get error message template from flow option
        $errorMessageTemplate = $apiData['error_message'] ?? 'Service temporarily unavailable. Please try again later.';
        
        // Extract error information from API result if available
        $apiErrorMessage = null;
        $apiErrorData = [];
        
        // Get API configuration to access error_path setting
        $apiConfigId = $apiData['api_configuration_id'] ?? null;
        $apiConfig = null;
        if ($apiConfigId) {
            $apiConfig = ExternalAPIConfiguration::find($apiConfigId);
        }
        
        if ($apiResult) {
            $apiErrorMessage = $apiResult['api_error_message'] ?? $apiResult['message'] ?? null;
            
            // Extract error data using configurable error_path (similar to data_path for success)
            $errorPath = null;
            if ($apiConfig) {
                $errorPath = $apiConfig->error_path;
            }
            
            // If error_path is not set, try error_handling.error_message_path as fallback
            if (empty($errorPath) && $apiConfig) {
                $errorHandling = $apiConfig->getErrorHandling();
                $errorPath = $errorHandling['error_message_path'] ?? null;
            }
            
            // Extract error data from response
            $fullErrorResponse = $apiResult['raw_response']['body'] ?? $apiResult;
            if (!empty($errorPath)) {
                // Use error_path to extract error data structure
                $apiErrorData = data_get($fullErrorResponse, $errorPath, $apiResult['data'] ?? []);
            } else {
                // Fallback to common error data locations
                $apiErrorData = $apiResult['data'] ?? $apiResult['error'] ?? [];
            }
        }
        
        $technicalError = $exception ? $exception->getMessage() : ($apiResult['error'] ?? 'API call failed');
        
        $sessionData = $session->session_data ?? [];
        $sessionData['error_message'] = $apiErrorMessage ?? $technicalError;
        $sessionData['api_error_message'] = $apiErrorMessage; 
        $sessionData['technical_error'] = $technicalError;
        
        // Store error data for template variable access (similar to success data)
        if (!empty($apiErrorData)) {
            if (is_string($apiErrorData)) {
                $sessionData['error_data'] = $apiErrorData;
            } elseif (is_array($apiErrorData)) {
                $sessionData['error_data'] = $apiErrorData;
                // Extract individual error fields for direct access
                foreach ($apiErrorData as $key => $value) {
                    if (is_scalar($value) || is_null($value)) {
                        $sessionData['error_' . $key] = $value;
                    }
                }
            }
        }
        
        // Track retry attempts for API calls
        $retryKey = $session->current_flow_id . '_' . ($option->option_value ?? 'api_call');
        $retryCount = $sessionData['input_retries'][$retryKey] ?? 0;
        $maxRetries = config('ussd.max_input_retries', 3);
        
        // Check if retry limit exceeded for this API call
        if ($maxRetries > 0 && $retryCount >= $maxRetries) {
            $this->logSessionAction($session, 'max_retries_exceeded', $option->option_value ?? 'api_call', 'Maximum retry attempts exceeded', 'error');
            
            // End session gracefully with friendly message (like exit option)
            return $this->handleEndSession($session, null, 'Thank you for using our service.');
        }
        
        // Increment retry count for this API call
        if (!isset($sessionData['input_retries'])) {
            $sessionData['input_retries'] = [];
        }
        $sessionData['input_retries'][$retryKey] = $retryCount + 1;
        
        $sessionData['last_api_error'] = [
            'flow_id' => $session->current_flow_id,
            'option_id' => $option->id ?? null,
            'option_value' => $option->option_value ?? null,
            'api_configuration_id' => $apiData['api_configuration_id'] ?? null,
            'retry_count' => $retryCount + 1,
        ];
        
        // Also store dynamic flow error info if this came from a dynamic flow selection
        $previousFlowId = $sessionData['previous_flow_id'] ?? null;
        $selectedDynamicOption = $sessionData['selected_dynamic_option'] ?? null;
        if ($previousFlowId && $selectedDynamicOption) {
            $sessionData['last_dynamic_error'] = [
                'flow_id' => $previousFlowId,
                'option_index' => $sessionData['last_dynamic_selection_index'] ?? null,
                'option_value' => $selectedDynamicOption['value'] ?? null,
            ];
        }
        
        // Merge API error data into session data for placeholder access
        if (!empty($apiErrorData)) {
            foreach ($apiErrorData as $key => $value) {
                if (is_scalar($value)) {
                    $sessionData['api_error_' . $key] = $value;
                }
            }
        }
        
        $session->update(['session_data' => $sessionData]);
        
        // Log the API error with 'error' status
        $errorLogMessage = $apiErrorMessage ?? $technicalError ?? 'API call failed';
        $this->logSessionAction($session, 'api_call_error', $option->option_value ?? null, $errorLogMessage, 'error', null, $technicalError);
        
        $errorMessage = $this->processErrorMessageTemplate($errorMessageTemplate, [
            'error_message' => $apiErrorMessage ?? $technicalError,
            'api_error_message' => $apiErrorMessage,
            'technical_error' => $technicalError,
            'session' => $sessionData,
            'api_data' => $apiErrorData,
        ]);
        
        // PRIORITY 1: Check if there was an input collection before this API call
        // This should take precedence over error flows or dynamic flow retries
        // If user entered any input (PIN, text, number, phone, account, amount, etc.) 
        // and API failed with non-critical error, they should be able to re-enter the input
        $lastInputType = $sessionData['last_input_type_before_api'] ?? null;
        $lastInputFlowId = $sessionData['last_input_flow_id'] ?? null;
        $lastInputActionData = $sessionData['last_input_action_data'] ?? [];
        
        if ($lastInputType && $lastInputFlowId) {
            // Clear the old input value (handle all possible field names)
            // Input can be stored with different names: input_type, store_as value, or field name
            $inputFieldNames = [
                $lastInputType, // e.g., input_pin
                str_replace('input_', '', $lastInputType), // e.g., pin
            ];
            
            // Also check if there was a custom store_as field
            if (isset($lastInputActionData['store_as']) && !empty($lastInputActionData['store_as'])) {
                $inputFieldNames[] = $lastInputActionData['store_as'];
            }
            
            // Remove duplicates
            $inputFieldNames = array_unique($inputFieldNames);
            
            foreach ($inputFieldNames as $fieldName) {
                unset($sessionData[$fieldName]);
                unset($sessionData['collected_inputs'][$fieldName]);
                unset($sessionData['selected_item_data'][$fieldName]);
            }
            
            // Find the input flow and option
            $inputFlow = USSDFlow::find($lastInputFlowId);
            if ($inputFlow) {
                $inputOption = $inputFlow->options()
                    ->where('action_type', $lastInputType)
                    ->where('is_active', true)
                    ->first();
                
                if ($inputOption) {
                    // Set up retry flag and navigate back to input flow
                    $sessionData['retrying_input_after_api_error'] = true;
                    $sessionData['collecting_input'] = true;
                    $sessionData['input_type'] = $lastInputType;
                    $inputPrompt = $inputOption->action_data['prompt'] ?? $this->getDefaultPrompt($lastInputType, $inputOption->option_text);
                    $sessionData['input_prompt'] = $inputPrompt;
                    $sessionData['input_action_data'] = $inputOption->action_data ?? [];
                    $sessionData['in_error_flow'] = false;
                    unset($sessionData['error_flow_id']);
                    $session->update(['session_data' => $sessionData, 'current_flow_id' => $inputFlow->id]);
                    
                    $inputTypeLabel = str_replace(['input_', '_'], ['', ' '], $lastInputType);
                    $this->logSessionAction($session, 'input_retry_after_api_error', null, "Re-prompting for {$inputTypeLabel} ({$lastInputType}) to retry failed API call", 'success');
                    
                    // Get error message from API result or session data
                    // $apiErrorMessage is already extracted from $apiResult at the top of this method
                    $errorMessage = $apiErrorMessage ?? $sessionData['api_error_message'] ?? $sessionData['error_message'] ?? 'An error occurred. Please try again.';
                    
                    // Build message with error and prompt
                    $fullMessage = $errorMessage . "\n\n" . $inputPrompt;
                    
                    // Return directly with error message and prompt, ensuring collecting_input is set
                    return [
                        'success' => true,
                        'message' => $fullMessage,
                        'flow_title' => $inputFlow->title,
                        'flow_description' => $inputFlow->description,
                        'requires_input' => true,
                        'current_flow' => $inputFlow,
                        'input_type' => $lastInputType,
                    ];
                }
            }
        }
        
        // PRIORITY 2: Check for configured error flow
        $errorFlowId = $apiData['error_flow_id'] ?? null;
        
        if ($errorFlowId) {
            $errorFlow = USSDFlow::find($errorFlowId);
            if ($errorFlow && $errorFlow->is_active) {
                $sessionData['in_error_flow'] = true;
                $sessionData['error_flow_id'] = $errorFlowId;
                $session->update(['session_data' => $sessionData, 'current_flow_id' => $errorFlow->id]);
                
                $menuText = $this->replacePlaceholdersWithApiAndSessionData($errorFlow->menu_text, [], $sessionData);
                
                return [
                    'success' => true,
                    'message' => $menuText,
                    'flow_title' => $errorFlow->title,
                    'requires_input' => true,
                    'current_flow' => $errorFlow,
                    'options' => $errorFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
                ];
            }
        }
        
        // PRIORITY 3: Check if API error came from dynamic flow selection
        $previousFlowId = $sessionData['previous_flow_id'] ?? null;
        $lastDynamicSelection = $sessionData['last_dynamic_selection_index'] ?? null;
        
        if ($previousFlowId && $lastDynamicSelection) {
            $previousFlow = USSDFlow::find($previousFlowId);
            if ($previousFlow && $previousFlow->flow_type === 'dynamic' && $previousFlow->is_active) {
                $sessionData['in_error_flow'] = true;
                $sessionData['last_dynamic_error'] = [
                    'flow_id' => $previousFlowId,
                    'option_index' => $lastDynamicSelection,
                ];
                $session->update(['session_data' => $sessionData, 'current_flow_id' => $previousFlowId]);
                $session->refresh();
                $session->load('currentFlow');
                
                // Regenerate dynamic flow display
                $flowDisplay = $this->getCurrentFlowDisplay($session);
                $fullMessage = $errorMessage . "\n\n" . $flowDisplay['message'];
                
                return [
                    'success' => false,
                    'message' => $fullMessage,
                    'flow_title' => $flowDisplay['flow_title'] ?? $previousFlow->title,
                    'requires_input' => true,
                    'current_flow' => $previousFlow,
                ];
            }
        }
        
        // If no error flow and not from dynamic flow, stay in current flow and show error message with same options
        // This allows user to retry by selecting the same option again
        $currentFlow = $session->currentFlow;
        if ($currentFlow) {
            // Set error flow flag to enable retry logic
            $sessionData['in_error_flow'] = true;
            $session->update(['session_data' => $sessionData]);
            
            $menuText = $this->replacePlaceholdersWithApiAndSessionData($currentFlow->menu_text, [], $sessionData);
            $fullMessage = $errorMessage . "\n\n" . $menuText;
            
            return [
                'success' => false,
                'message' => $fullMessage,
                'flow_title' => $currentFlow->title,
                'requires_input' => true,
                'current_flow' => $currentFlow,
                'options' => $currentFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
            ];
        }
        
        // Default: end session with error message
        $this->completeSession($session);
        
        return [
            'success' => false,
            'message' => $errorMessage,
            'requires_input' => false,
            'session_ended' => true,
        ];
    }
    
    /**
     * Process error message template with placeholders
     * Supports {{error_message}}, {{api_error_message}}, {{technical_error}}, and nested paths
     * 
     * @param string $template Error message template
     * @param array $context Context data for placeholder replacement
     * @return string Processed error message
     */
    private function processErrorMessageTemplate(string $template, array $context): string
    {
        // Replace placeholders in format {{variable}} or {{nested.path}}
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($context) {
            $path = trim($matches[1]);
            
            // Handle nested paths (e.g., api_data.message, session.phone_number)
            $value = $this->getNestedValueFromContext($path, $context);
            
            // If value found, return it; otherwise return empty string (don't show placeholder)
            return $value !== null ? (string) $value : '';
        }, $template);
    }
    
    /**
     * Get nested value from context array using dot notation
     * 
     * @param string $path Dot-notation path (e.g., "api_data.message", "session.phone_number")
     * @param array $context Context array
     * @return mixed|null
     */
    private function getNestedValueFromContext(string $path, array $context)
    {
        $keys = explode('.', $path);
        $value = $context;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }

    /**
     * Mark session as completed
     * Note: Billing now happens on session START, not completion
     */
    private function completeSession(USSDSession $session): void
    {
        // Update status only
        $session->update(['status' => 'completed']);
    }

    /**
     * Replace placeholders in text with API response data
     */
    private function replacePlaceholdersWithApiData(string $text, array $apiData): string
    {
        $sanitizationService = app(SanitizationService::class);
        
        return preg_replace_callback('/\{api\.([^}]+)\}/', function($matches) use ($apiData, $sanitizationService) {
            $field = $matches[1];
            // Support dot notation for nested fields (e.g., api.data.field)
            $value = data_get($apiData, $field, '');
            // SECURITY: Sanitize API data output
            $value = is_scalar($value) ? (string) $value : '';
            return $sanitizationService->sanitizeOutput($value, 500);
        }, $text);
    }

    /**
     * Replace placeholders in text with both API data and session data
     */
    private function replacePlaceholdersWithApiAndSessionData(string $text, array $apiData, array $sessionData): string
    {
        // First replace API placeholders (single braces: {api.field} or {api.data.field})
        $text = $this->replacePlaceholdersWithApiData($text, $apiData);
        
        // Then replace session data placeholders (single braces: {timestamp}, {phone}, etc.)
        $text = $this->replacePlaceholders($text, $sessionData);
        
        // Finally replace template variables (double braces: {{session.field}} or {{session.data.field}})
        $sanitizationService = app(SanitizationService::class);
        $text = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($sessionData, $apiData, $sanitizationService) {
            $path = trim($matches[1]);
            
            // Handle session variables with dot notation support
            if (str_starts_with($path, 'session.')) {
                $field = substr($path, 8); // Remove 'session.' prefix
                // Support dot notation for nested paths (e.g., session.data.field)
                $value = data_get($sessionData, $field, '');
                
                $value = is_scalar($value) ? (string) $value : '';
                return $sanitizationService->sanitizeOutput($value, 500);
            }
            
            // Handle input variables
            if (str_starts_with($path, 'input.')) {
                $field = substr($path, 6); // Remove 'input.' prefix
                $collectedInput = $sessionData['collected_input'] ?? [];
                $value = data_get($collectedInput, $field, '');
                $value = is_scalar($value) ? (string) $value : '';
                return $sanitizationService->sanitizeOutput($value, 500);
            }
            
            // Handle api variables (for consistency with double braces syntax)
            if (str_starts_with($path, 'api.')) {
                $field = substr($path, 4); // Remove 'api.' prefix
                // Support dot notation for nested paths (e.g., api.data.field)
                $value = data_get($apiData, $field, '');
                $value = is_scalar($value) ? (string) $value : '';
                return $sanitizationService->sanitizeOutput($value, 500);
            }
            
            return $matches[0]; // Return original if no match
        }, $text);
        
        return $text;
    }

    /**
     * Handle input request (PIN, account number, phone number, etc.)
     */
    private function handleInputRequest(USSDSession $session, USSDFlowOption $option): array
    {
        $actionData = $option->action_data ?? [];
        if (is_object($actionData)) {
            $actionData = (array) $actionData;
        }
        $prompt = $actionData['prompt'] ?? $this->getDefaultPrompt($option->action_type, $option->option_text);
        
        // Set session to input collection mode
        $sessionData = $session->session_data ?? [];
        $sessionData['collecting_input'] = true;
        $sessionData['input_type'] = $option->action_type;
        $sessionData['input_prompt'] = $prompt;
        $sessionData['input_action_data'] = $actionData;
        $sessionData['next_flow_after_input'] = $option->next_flow_id ?? null;
        $sessionData['success_message_after_input'] = $actionData['success_message'] ?? null;
        
        $session->update(['session_data' => $sessionData]);
        
        return [
            'success' => true,
            'message' => $prompt,
            'flow_title' => $session->currentFlow->title,
            'flow_description' => $session->currentFlow->description,
            'requires_input' => true,
            'current_flow' => $session->currentFlow,
            'input_type' => $option->action_type,
        ];
    }

    /**
     * Log session action for monitoring
     */
    private function logSessionAction(
        USSDSession $session, 
        string $actionType, 
        ?string $inputData, 
        ?string $outputData, 
        string $status = 'success',
        ?string $responseTime = null,
        ?string $errorMessage = null
    ): void {
        USSDSessionLog::create([
            'session_id' => $session->id,
            'ussd_id' => $session->ussd_id,
            'flow_id' => $session->current_flow_id,
            'action_type' => $actionType,
            'input_data' => $inputData,
            'output_data' => $outputData,
            'response_time' => $responseTime,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => [
                'user_agent' => $session->user_agent,
                'ip_address' => $session->ip_address,
                'step_count' => $session->step_count,
            ],
            'action_timestamp' => now(),
        ]);
    }

    /**
     * Get session analytics
     */
    public function getSessionAnalytics(USSD $ussd, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $sessions = USSDSession::where('ussd_id', $ussd->id)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $logs = USSDSessionLog::where('ussd_id', $ussd->id)
            ->whereDate('action_timestamp', '>=', $startDate)
            ->whereDate('action_timestamp', '<=', $endDate);

        return [
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $sessions->where('status', 'completed')->count(),
            'active_sessions' => $sessions->where('status', 'active')->count(),
            'error_sessions' => $sessions->where('status', 'error')->count(),
            'total_interactions' => $logs->count(),
            'average_session_duration' => $this->calculateAverageSessionDuration($ussd->id, $startDate, $endDate),
            'completion_rate' => $this->calculateCompletionRate($ussd->id, $startDate, $endDate),
            'error_rate' => $this->calculateErrorRate($ussd->id, $startDate, $endDate),
        ];
    }

    /**
     * Calculate average session duration
     */
    private function calculateAverageSessionDuration(int $ussdId, Carbon $startDate, Carbon $endDate): float
    {
        $sessions = USSDSession::where('ussd_id', $ussdId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('status', 'completed')
            ->whereNotNull('last_activity')
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }


        $totalDuration = $sessions->sum(function ($session) {

            if (!$session->created_at || !$session->last_activity) {
                return 0;
            }
            
            $duration = $session->last_activity->diffInSeconds($session->created_at, true);
            
            return max(0, $duration);
        });

        return round($totalDuration / $sessions->count(), 2);
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate(int $ussdId, Carbon $startDate, Carbon $endDate): float
    {
        $totalSessions = USSDSession::where('ussd_id', $ussdId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();

        if ($totalSessions === 0) {
            return 0;
        }

        $completedSessions = USSDSession::where('ussd_id', $ussdId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('status', 'completed')
            ->count();

        return round(($completedSessions / $totalSessions) * 100, 2);
    }

    /**
     * Calculate error rate
     */
    private function calculateErrorRate(int $ussdId, Carbon $startDate, Carbon $endDate): float
    {
        $totalLogs = USSDSessionLog::where('ussd_id', $ussdId)
            ->whereDate('action_timestamp', '>=', $startDate)
            ->whereDate('action_timestamp', '<=', $endDate)
            ->count();

        if ($totalLogs === 0) {
            return 0;
        }

        $errorLogs = USSDSessionLog::where('ussd_id', $ussdId)
            ->whereDate('action_timestamp', '>=', $startDate)
            ->whereDate('action_timestamp', '<=', $endDate)
            ->where('status', 'error')
            ->count();

        return round(($errorLogs / $totalLogs) * 100, 2);
    }

    /**
     * Handle input collection for various input types
     */
    private function handleInputCollection(USSDSession $session, string $input, string $inputType, string $inputPrompt): array
    {
        $sessionData = $session->session_data ?? [];
        $actionData = $sessionData['input_action_data'] ?? [];
        
        // Validate input based on type
        $validationResult = $this->validateInput($input, $inputType, $sessionData);
        
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'message' => $validationResult['error_message'] . "\n\n" . $inputPrompt,
                'flow_title' => $session->currentFlow->title,
                'requires_input' => true,
                'current_flow' => $session->currentFlow,
            ];
        }

        $sanitizationService = app(SanitizationService::class);
        $sanitizedInput = $sanitizationService->sanitizeInput($input, $inputType);
        
        $storeAs = $actionData['store_as'] ?? $inputType;
        $storeAs = $sanitizationService->sanitizeKey($storeAs);
        $sessionData[$storeAs] = $sanitizedInput;
        $sessionData['collected_inputs'][$inputType] = $input;
        
        // Also store in selected_item_data format (like dynamic flows) for template compatibility
        // This allows using {{selected_item_data.field_name}} in template variables
        if (!isset($sessionData['selected_item_data'])) {
            $sessionData['selected_item_data'] = [];
        }
        
        // Store in selected_item_data using the store_as key (or input type if not specified)
        // This provides a single, predictable location for the data
        $sessionData['selected_item_data'][$storeAs] = $sanitizedInput;
        
        // Also store with the input type name for backward compatibility
        if ($storeAs !== $inputType) {
            $sessionData['selected_item_data'][$inputType] = $sanitizedInput;
        }
        
        // Extract to top level for direct access (e.g., {amount} instead of {selected_item_data.amount})
        // Only if the key doesn't already exist to avoid overwriting
        if (!isset($sessionData[$storeAs])) {
            $sessionData[$storeAs] = $sanitizedInput;
        }
        
        $sessionData['collecting_input'] = false;
        // Store last input info before clearing (for retry on credential errors)
        $sessionData['last_input_type_before_api'] = $inputType;
        $sessionData['last_input_action_data'] = $actionData;
        $sessionData['last_input_flow_id'] = $session->current_flow_id;
        $sessionData['input_type'] = null;
        $sessionData['input_prompt'] = null;
        $sessionData['input_action_data'] = null;
        
        $session->update(['session_data' => $sessionData]);

        $this->logSessionAction($session, 'input_collected', $input, "Collected $inputType: $input");

        $nextFlowId = $sessionData['next_flow_after_input'] ?? null;
        $endSessionAfterInput = $actionData['end_session_after_input'] ?? false;
        $afterInputAction = $actionData['after_input_action'] ?? null;
        
        // Check if we need to trigger an API call after input collection
        if ($afterInputAction === 'api_call' || $afterInputAction === 'external_api_call') {
            $apiConfigId = $actionData['api_configuration_id'] ?? null;
            
            if ($apiConfigId) {
                // Create a temporary option to trigger the API call
                $tempOption = new USSDFlowOption();
                $tempOption->action_type = $afterInputAction;
                $tempOption->next_flow_id = $nextFlowId;
                $tempOption->action_data = [
                    'api_configuration_id' => $apiConfigId,
                    'end_session_after_api' => $actionData['end_session_after_api'] ?? false,
                    'next_flow_id' => $nextFlowId,
                ];
                
                // Trigger the API call
                return $this->handleApiCall($session, $tempOption);
            }
        }
        
        if ($endSessionAfterInput) {
            // End session after input collection
            $this->completeSession($session);
            $successMessage = $actionData['success_message'] ?? "✓ Data saved successfully!\n\nThank you for using our service.";
            
            return [
                'success' => true,
                'message' => $successMessage,
                'requires_input' => false,
                'session_ended' => true,
            ];
        } elseif ($nextFlowId) {
            $nextFlow = USSDFlow::find($nextFlowId);
            if ($nextFlow && $nextFlow->is_active) {
                $session->update(['current_flow_id' => $nextFlow->id]);
                $sessionData['next_flow_after_input'] = null;
                $session->update(['session_data' => $sessionData]);
                
                $menuText = $this->replacePlaceholders($nextFlow->menu_text, $sessionData);
                
                return [
                    'success' => true,
                    'message' => $menuText,
                    'flow_title' => $nextFlow->title,
                    'requires_input' => true,
                    'current_flow' => $nextFlow,
                    'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
                ];
            }
        }

        // If no next flow specified, return to current flow with success message
        $currentFlow = $session->currentFlow;
        $successMessage = $actionData['success_message'] ?? "✓ Data saved successfully!\n\n" . $currentFlow->menu_text;
        
        return [
            'success' => true,
            'message' => $successMessage,
            'flow_title' => $currentFlow->title,
            'requires_input' => true,
            'current_flow' => $currentFlow,
            'options' => $currentFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }

    /**
     * Validate input based on type
     */
    private function validateInput(string $input, string $inputType, array $sessionData): array
    {
        $actionData = $sessionData['input_action_data'] ?? [];
        
        switch ($inputType) {
            case 'input_text':
                return $this->validateTextInput($input, $actionData);
                
            case 'input_number':
                return $this->validateNumberInput($input, $actionData);
                
            case 'input_phone':
                return $this->validatePhoneInput($input, $actionData);
                
            case 'input_account':
                return $this->validateAccountInput($input, $actionData);
                
            case 'input_pin':
                return $this->validatePinInput($input, $actionData);
                
            case 'input_amount':
                return $this->validateAmountInput($input, $actionData);
                
            default:
                return ['valid' => true, 'error_message' => null];
        }
    }

    /**
     * Validate text input
     */
    private function validateTextInput(string $input, array $actionData): array
    {
        $validation = $actionData['validation'] ?? null;
        $errorMessage = $actionData['error_message'] ?? 'Invalid text input.';
        
        if ($validation && !preg_match("/$validation/", $input)) {
            return ['valid' => false, 'error_message' => $errorMessage];
        }
        
        return ['valid' => true, 'error_message' => null];
    }

    /**
     * Validate number input
     */
    private function validateNumberInput(string $input, array $actionData): array
    {
        if (!is_numeric($input)) {
            return ['valid' => false, 'error_message' => $actionData['error_message'] ?? 'Please enter a valid number.'];
        }
        
        $min = $actionData['min'] ?? null;
        $max = $actionData['max'] ?? null;
        $errorMessage = $actionData['error_message'] ?? 'Invalid number input.';
        
        if ($min !== null && $input < $min) {
            return ['valid' => false, 'error_message' => $errorMessage];
        }
        
        if ($max !== null && $input > $max) {
            return ['valid' => false, 'error_message' => $errorMessage];
        }
        
        return ['valid' => true, 'error_message' => null];
    }

    /**
     * Validate phone input
     */
    private function validatePhoneInput(string $input, array $actionData): array
    {
        $countryCode = $actionData['country_code'] ?? '+234';
        $errorMessage = $actionData['error_message'] ?? 'Please enter a valid phone number.';
        
        // Remove any non-digit characters
        $cleanInput = preg_replace('/[^0-9]/', '', $input);
        
        // Check for specific length validation if provided
        if (isset($actionData['validation']) && strpos($actionData['validation'], 'length:') === 0) {
            $expectedLength = (int) substr($actionData['validation'], 7); // Extract number after "length:"
            if (strlen($cleanInput) !== $expectedLength) {
                return ['valid' => false, 'error_message' => $errorMessage];
            }
        } else {
            // Basic validation for phone numbers (10-15 digits)
            if (strlen($cleanInput) < 10 || strlen($cleanInput) > 15) {
                return ['valid' => false, 'error_message' => $errorMessage];
            }
        }
        
        return ['valid' => true, 'error_message' => null];
    }

    /**
     * Validate account input
     */
    private function validateAccountInput(string $input, array $actionData): array
    {
        $expectedLength = $actionData['length'] ?? 10;
        $errorMessage = $actionData['error_message'] ?? 'Please enter a valid account number.';
        
        if (strlen($input) !== (int)$expectedLength) {
            return ['valid' => false, 'error_message' => $errorMessage];
        }
        
        return ['valid' => true, 'error_message' => null];
    }

    /**
     * Validate PIN input
     */
    private function validatePinInput(string $input, array $actionData): array
    {
        $expectedLength = $actionData['length'] ?? 4;
        $errorMessage = $actionData['error_message'] ?? 'Please enter a valid PIN.';
        
        if (!is_numeric($input)) {
            return ['valid' => false, 'error_message' => $errorMessage];
        }
        
        if (strlen($input) !== (int)$expectedLength) {
            return ['valid' => false, 'error_message' => $errorMessage];
        }
        
        return ['valid' => true, 'error_message' => null];
    }

    /**
     * Validate amount input
     */
    private function validateAmountInput(string $input, array $actionData): array
    {
        // Remove any non-numeric characters except decimal point
        $cleanInput = preg_replace('/[^0-9.]/', '', $input);
        
        if (!is_numeric($cleanInput) || $cleanInput <= 0) {
            return [
                'valid' => false,
                'error_message' => $actionData['error_message'] ?? 'Please enter a valid amount.'
            ];
        }

        // Check minimum amount if specified
        if (isset($actionData['min_amount']) && $cleanInput < $actionData['min_amount']) {
            return [
                'valid' => false,
                'error_message' => $actionData['error_message'] ?? "Amount must be at least {$actionData['min_amount']}."
            ];
        }

        // Check maximum amount if specified
        if (isset($actionData['max_amount']) && $cleanInput > $actionData['max_amount']) {
            return [
                'valid' => false,
                'error_message' => $actionData['error_message'] ?? "Amount cannot exceed {$actionData['max_amount']}."
            ];
        }

        return [
            'valid' => true,
            'cleaned_value' => number_format($cleanInput, 2)
        ];
    }

    /**
     * Handle registration processing
     */
    private function handleProcessRegistration(USSDSession $session, USSDFlowOption $option): array
    {
        $actionData = $option->action_data ?? [];
        $requiredFields = $actionData['required_fields'] ?? [];
        $sessionData = $session->session_data ?? [];
        
        // Check if all required fields are completed
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($sessionData[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            return [
                'success' => false,
                'message' => $actionData['error_message'] ?? 'Please complete all required fields first.',
                'requires_input' => true,
                'current_flow' => $session->currentFlow,
            ];
        }
        
        // Generate registration ID
        $registrationId = 'REG-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        // Store registration data
        $sessionData['registration_id'] = $registrationId;
        $sessionData['registration_completed'] = true;
        $sessionData['registration_timestamp'] = now()->toISOString();
        
        $session->update(['session_data' => $sessionData]);
        
        // Navigate to summary flow
        if ($option->next_flow_id) {
            $nextFlow = USSDFlow::find($option->next_flow_id);
            if ($nextFlow) {
                $session->update(['current_flow_id' => $nextFlow->id]);
                
                // Replace placeholders in menu text
                $menuText = $this->replacePlaceholders($nextFlow->menu_text, $sessionData);
                
                return [
                    'success' => true,
                    'message' => $menuText,
                    'requires_input' => true,
                    'current_flow' => $nextFlow,
                    'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => 'Registration completed successfully!',
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Handle feedback processing
     */
    private function handleProcessFeedback(USSDSession $session, USSDFlowOption $option): array
    {
        $actionData = $option->action_data ?? [];
        $requiredFields = $actionData['required_fields'] ?? [];
        $sessionData = $session->session_data ?? [];
        
        // Check if all required fields are completed
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($sessionData[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            return [
                'success' => false,
                'message' => $actionData['error_message'] ?? 'Please provide a rating and comment first.',
                'requires_input' => true,
                'current_flow' => $session->currentFlow,
            ];
        }
        
        // Generate feedback ID
        $feedbackId = 'FB-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        // Store feedback data
        $sessionData['feedback_id'] = $feedbackId;
        $sessionData['feedback_completed'] = true;
        $sessionData['feedback_timestamp'] = now()->toISOString();
        
        $session->update(['session_data' => $sessionData]);
        
        // Navigate to summary flow
        if ($option->next_flow_id) {
            $nextFlow = USSDFlow::find($option->next_flow_id);
            if ($nextFlow) {
                $session->update(['current_flow_id' => $nextFlow->id]);
                
                // Replace placeholders in menu text
                $menuText = $this->replacePlaceholders($nextFlow->menu_text, $sessionData);
                
                return [
                    'success' => true,
                    'message' => $menuText,
                    'requires_input' => true,
                    'current_flow' => $nextFlow,
                    'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => 'Feedback submitted successfully!',
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Handle contact processing
     */
    private function handleProcessContact(USSDSession $session, USSDFlowOption $option): array
    {
        $actionData = $option->action_data ?? [];
        $requiredFields = $actionData['required_fields'] ?? [];
        $sessionData = $session->session_data ?? [];
        
        // Check if all required fields are completed
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($sessionData[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            return [
                'success' => false,
                'message' => $actionData['error_message'] ?? 'Please provide at least your name and phone number.',
                'requires_input' => true,
                'current_flow' => $session->currentFlow,
            ];
        }
        
        // Generate contact ID
        $contactId = 'CON-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        // Store contact data
        $sessionData['contact_id'] = $contactId;
        $sessionData['contact_completed'] = true;
        $sessionData['contact_timestamp'] = now()->toISOString();
        
        $session->update(['session_data' => $sessionData]);
        
        // Navigate to summary flow
        if ($option->next_flow_id) {
            $nextFlow = USSDFlow::find($option->next_flow_id);
            if ($nextFlow) {
                $session->update(['current_flow_id' => $nextFlow->id]);
                
                // Replace placeholders in menu text
                $menuText = $this->replacePlaceholders($nextFlow->menu_text, $sessionData);
                
                return [
                    'success' => true,
                    'message' => $menuText,
                    'requires_input' => true,
                    'current_flow' => $nextFlow,
                    'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => 'Contact information saved successfully!',
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Replace placeholders in text with session data
     */
    private function replacePlaceholders(string $text, array $sessionData): string
    {
        $sanitizationService = app(SanitizationService::class);
        
        $replacements = [
            '{timestamp}' => date('YmdHis'),
            '{phone}' => $sessionData['phone'] ?? ($sessionData['phone_number'] ?? 'Not provided'),
            '{amount}' => $sessionData['amount'] ?? '0',
            '{error_message}' => $sanitizationService->sanitizeOutput($sessionData['error_message'] ?? 'Unknown error', 200),
        ];
        
        $result = str_replace(array_keys($replacements), array_values($replacements), $text);
        
        // SECURITY: Final sanitization of the entire text to ensure safety
        return $sanitizationService->sanitizeOutput($result);
    }

    /**
     * Get default prompt based on input type
     */
    private function getDefaultPrompt(string $inputType, string $optionText): string
    {
        switch ($inputType) {
            case 'input_text':
                return "Please enter your " . strtolower($optionText) . ":";
            case 'input_number':
                return "Please enter a number:";
            case 'input_phone':
                return "Please enter your phone number:";
            case 'input_account':
                return "Please enter your account number:";
            case 'input_pin':
                return "Please enter your PIN:";
            case 'input_amount':
                return "Please enter the amount:";
            case 'input_selection':
                return "Please make your selection:";
            default:
                return "Please enter your input:";
        }
    }

    /**
     * Resolve template variables in strings
     */
    private function resolveTemplateVariables(?string $template, USSDSession $session): ?string
    {
        if (!$template) {
            return $template;
        }

        $sessionData = $session->session_data ?? [];
        $collectedInput = $sessionData['collected_input'] ?? [];

        // Replace template variables
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($session, $sessionData, $collectedInput) {
            $path = $matches[1];
            
            // Handle session variables
            if (str_starts_with($path, 'session.')) {
                $field = substr($path, 8); // Remove 'session.' prefix
                return $session->$field ?? $sessionData[$field] ?? '';
            }
            
            // Handle input variables
            if (str_starts_with($path, 'input.')) {
                $field = substr($path, 6); // Remove 'input.' prefix
                return $collectedInput[$field] ?? '';
            }
            
            return $matches[0]; // Return original if no match
        }, $template);
    }

    /**
     * Find marketplace API by service type
     */
    private function findMarketplaceApiByService(USSDSession $session): ? ExternalAPIConfiguration
    {
        $sessionData = $session->session_data ?? [];
        $collectedInput = $sessionData['collected_input'] ?? [];
        $service = $collectedInput['service'] ?? '';
        
        if (!$service) {
            return null;
        }

        // Map service names to API configurations
        $serviceMapping = [
            'MTN Airtime' => 'MTN Airtime Top-Up',
            'Airtel Airtime' => 'Airtel Airtime Top-Up',
            'Glo Airtime' => 'Glo Airtime Top-Up',
            '9mobile Airtime' => '9mobile Airtime Top-Up',
            'MTN Data' => 'MTN Data Bundle',
            'Airtel Data' => 'Airtel Data Bundle',
            'Glo Data' => 'Glo Data Bundle',
            '9mobile Data' => '9mobile Data Bundle',
            'Ikeja Electric' => 'Ikeja Electric Bill Payment',
            'Eko Electricity' => 'Eko Electricity Bill Payment',
            'Water Bill' => 'Water Bill Payment',
            'Cable TV' => 'Cable TV Subscription',
            'GT Bank Transfer' => 'GT Bank Transfer',
            'Paystack Payment' => 'Paystack Payment Gateway',
        ];

        $apiName = $serviceMapping[$service] ?? $service;
        
        // Find the API configuration
        return ExternalAPIConfiguration::where('name', $apiName)
            ->where('is_marketplace_template', true)
            ->where('is_active', true)
            ->first();
    }
    

    /**
     * Get currency symbol from currency code
     */
    private function getCurrencySymbol(string $currency): string
    {
        $currencySymbols = [
            'NGN' => '₦',
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            'KES' => 'KSh',
            'GHS' => '₵',
            'ZAR' => 'R',
        ];
        
        return $currencySymbols[$currency] ?? $currency;
    }
}
