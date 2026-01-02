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
        // Get the root flow for this USSD
        $rootFlow = USSDFlow::where('ussd_id', $ussd->id)
            ->where('is_root', true)
            ->where('is_active', true)
            ->first();

        if (!$rootFlow) {
            throw new \Exception('No root flow found for this USSD service');
        }

        $sessionEnvironment = $environment ?? $ussd->environment?->name ?? 'testing';
        
        // Get environment ID
        $environmentModel = Environment::where('name', $sessionEnvironment)->first();
        $environmentId = $environmentModel?->id;

        if ($sessionId) {
            $existingSession = USSDSession::where('session_id', $sessionId)
                ->where('ussd_id', $ussd->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();

            if ($existingSession) {
                // Reuse existing session - update activity, don't bill again
                $existingSession->update([
                    'last_activity' => now(),
                ]);
                
                Log::info('Reusing existing session', [
                    'session_id' => $existingSession->id,
                    'ussd_session_id' => $existingSession->session_id,
                    'phone_number' => $phoneNumber,
                ]);
                
                return $existingSession;
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
            'expires_at' => now()->addMinutes(30), // 30 minute timeout
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
        if ($sessionEnvironment === 'testing') {
            // Simulate billing for testing sessions (no real charges)
            $this->simulateBilling($session);
        } elseif ($sessionEnvironment === 'production' || $sessionEnvironment === 'live') {
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
                'billing_status' => 'simulated', // Special status for testing
                'billed_at' => now(),
                'invoice_id' => 'TEST-' . Str::random(8)
            ]);

            // Log the simulated billing
            $this->logSessionAction($session, 'billing_simulated', null, "Session cost: \${$sessionCost} (simulated)");
            
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
            // Update session activity
            $session->update([
                'last_activity' => now(),
                'step_count' => $session->step_count + 1,
            ]);

            // Simulate billing for testing sessions (per step)
            if ($session->ussd->environment && $session->ussd->environment->name === 'testing' && !$session->is_billed) {
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
                // We're collecting input, validate and process it
                // For input collection, use the full input (not just last part)
                return $this->handleInputCollection($session, $input, $inputType, $inputPrompt);
            }

            // Extract the last selection from cumulative input (e.g., "1*1*1*2" -> "2")
            // AfricasTalking sends cumulative input, but we only need the last selection
            $lastSelection = $this->extractLastSelection($input);
            
            Log::info('Processing input', [
                'session_id' => $session->id,
                'raw_input' => $input,
                'last_selection' => $lastSelection,
                'flow_id' => $currentFlow->id,
                'flow_type' => $currentFlow->flow_type
            ]);

            // Handle dynamic flow selection
            if ($currentFlow->flow_type === 'dynamic') {
                return $this->handleDynamicFlowSelection($session, $lastSelection, $currentFlow);
            }
            
            // Find the option that matches user input (static flows)
            $selectedOption = USSDFlowOption::where('flow_id', $currentFlow->id)
                ->where('option_value', $lastSelection)
                ->where('is_active', true)
                ->first();

            if (!$selectedOption) {
                // Invalid input - show error message
                $errorMessage = "Invalid option. Please try again.\n\n" . $currentFlow->menu_text;
                $this->logSessionAction($session, 'invalid_input', $lastSelection, $errorMessage, 'error');
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'flow_title' => $currentFlow->title,
                    'requires_input' => true,
                    'current_flow' => $currentFlow,
                ];
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
        if (empty($input)) {
            return '';
        }

        // If input contains "*", extract the last part
        if (strpos($input, '*') !== false) {
            $parts = explode('*', $input);
            $lastPart = end($parts);
            return trim($lastPart);
        }

        // If no "*", return the input as-is
        return trim($input);
    }

    /**
     * Get the current flow display (handles both static and dynamic flows)
     */
    public function getCurrentFlowDisplay(USSDSession $session): array
    {
        Log::info('getCurrentFlowDisplay called', [
            'session_id' => $session->id,
            'current_flow_id' => $session->current_flow_id
        ]);
        
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
        
        Log::info('Current flow found', [
            'session_id' => $session->id,
            'flow_id' => $currentFlow->id,
            'flow_type' => $currentFlow->flow_type,
            'flow_title' => $currentFlow->title,
            'flow_name' => $currentFlow->name
        ]);
        
        // Check if this is a dynamic flow
        if ($currentFlow->flow_type === 'dynamic') {
            // Check if we already have cached API data for pagination
            $sessionData = $session->session_data ?? [];
            
            Log::info('Dynamic flow detected, checking cache', [
                'session_id' => $session->id,
                'flow_id' => $currentFlow->id,
                'has_cached_api_data' => isset($sessionData['cached_api_data']),
                'has_dynamic_options' => isset($sessionData['dynamic_options']),
                'session_data_keys' => array_keys($sessionData)
            ]);
            
            if (isset($sessionData['cached_api_data']) && isset($sessionData['dynamic_options'])) {
                Log::info('Using cached data for pagination', [
                    'session_id' => $session->id,
                    'flow_id' => $currentFlow->id
                ]);
                // Use cached data for pagination - no need to make another API call
                return $this->regenerateDynamicFlowFromCache($session, $currentFlow);
            }
            
            Log::info('No cache found, making API call', [
                'session_id' => $session->id,
                'flow_id' => $currentFlow->id
            ]);
            // First time or no cache - make API call
            return $this->processDynamicFlow($session, $currentFlow);
        }
        
        // Handle static flow (existing logic)
        Log::info('Static flow detected', [
            'session_id' => $session->id,
            'flow_id' => $currentFlow->id
        ]);
        
        return [
            'success' => true,
            'message' => $currentFlow->getFullDisplayText($session),
            'flow_title' => $currentFlow->title,
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
                'requires_input' => false,
                'current_flow' => $flow,
            ];
        }
        
        // Store the dynamic options and cache the API data in session for pagination
        $sessionData = $session->session_data ?? [];
        $sessionData['dynamic_options'] = $result['options'] ?? [];
        $sessionData['dynamic_continuation_type'] = $result['continuation_type'] ?? 'continue';
        $sessionData['dynamic_next_flow_id'] = $result['next_flow_id'] ?? null;
        $sessionData['cached_api_data'] = $result['cached_api_data'] ?? null; // Cache the raw API data
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
        Log::info('regenerateDynamicFlowFromCache called', [
            'session_id' => $session->id,
            'flow_id' => $flow->id,
            'flow_title' => $flow->title,
            'flow_name' => $flow->name
        ]);
        
        $sessionData = $session->session_data ?? [];
        $cachedApiData = $sessionData['cached_api_data'] ?? null;
        $dynamicConfig = $flow->dynamic_config ?? [];
        
        Log::info('Cache data check', [
            'session_id' => $session->id,
            'flow_id' => $flow->id,
            'has_cached_api_data' => !is_null($cachedApiData),
            'cached_api_data_type' => gettype($cachedApiData),
            'dynamic_config' => $dynamicConfig
        ]);
        
        if (!$cachedApiData) {
            Log::warning('No cached API data found, falling back to API call', [
                'session_id' => $session->id,
                'flow_id' => $flow->id
            ]);
            // Fallback to making a new API call if cache is missing
            return $this->processDynamicFlow($session, $flow);
        }
        
        Log::info('About to format API response to options', [
            'session_id' => $session->id,
            'flow_id' => $flow->id,
            'cached_api_data_sample' => is_array($cachedApiData) ? array_slice($cachedApiData, 0, 2) : $cachedApiData
        ]);
        
        // Use cached data to regenerate options with current page
        $dynamicProcessor = app(DynamicFlowProcessor::class);
        $options = $dynamicProcessor->formatApiResponseToOptions($cachedApiData, $dynamicConfig, $session);
        
        Log::info('Options generated from cache', [
            'session_id' => $session->id,
            'flow_id' => $flow->id,
            'options_count' => count($options),
            'options_sample' => array_slice($options, 0, 3)
        ]);
        
        // Update the dynamic options in session
        $sessionData['dynamic_options'] = $options;
        $session->update(['session_data' => $sessionData]);
        
        // Format the message with dynamic options
        $flowTitle = $flow->title ?? $flow->name ?? 'Menu';
        $message = $flowTitle;
        if (!empty($options)) {
            $message .= "\n";
            foreach ($options as $index => $option) {
                $message .= ($index + 1) . ". " . $option['label'] . "\n";
            }
        } else {
            $message .= "\n" . ($dynamicConfig['empty_message'] ?? 'No options available');
        }
        
        Log::info('regenerateDynamicFlowFromCache completed successfully', [
            'session_id' => $session->id,
            'flow_id' => $flow->id,
            'message_length' => strlen($message),
            'flow_title' => $flowTitle
        ]);
        
        return [
            'success' => true,
            'message' => $message,
            'flow_title' => $flowTitle,
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
        
        // Find the selected option from dynamic options
        $selectedOption = null;
        $inputNumber = (int) $input;
        
        if ($inputNumber > 0 && $inputNumber <= count($dynamicOptions)) {
            $selectedOption = $dynamicOptions[$inputNumber - 1];
        }

        
        // Handle pagination navigation
        if ($selectedOption && in_array($selectedOption['value'], ['PAGINATION_NEXT', 'PAGINATION_BACK'])) {
            Log::info('Pagination navigation detected', [
                'session_id' => $session->id,
                'flow_id' => $flow->id,
                'selected_option' => $selectedOption,
                'input' => $input
            ]);
            
            $newPage = $selectedOption['data']['page'] ?? 1;
            
            Log::info('Updating session with new page', [
                'session_id' => $session->id,
                'new_page' => $newPage,
                'current_session_data' => $sessionData
            ]);
            
            // Update session data with new page
            $sessionData['current_page'] = $newPage;
            $session->update(['session_data' => $sessionData]);
            
            // Log pagination action
            $this->logSessionAction($session, 'pagination', $input, "Page {$newPage}");
            
            Log::info('About to call getCurrentFlowDisplay', [
                'session_id' => $session->id,
                'flow_id' => $flow->id,
                'updated_session_data' => $sessionData
            ]);
            
            // Regenerate the dynamic flow display with new page
            try {
                $flowDisplay = $this->getCurrentFlowDisplay($session);
                
                Log::info('getCurrentFlowDisplay successful', [
                    'session_id' => $session->id,
                    'flow_display' => $flowDisplay
                ]);
                
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
            // Invalid input - regenerate the dynamic flow display
            $flowDisplay = $this->getCurrentFlowDisplay($session);
            $errorMessage = "Invalid option. Please try again.\n\n" . $flowDisplay['message'];
            $this->logSessionAction($session, 'invalid_input', $input, $errorMessage, 'error');
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'flow_title' => $flow->title,
                'requires_input' => true,
                'current_flow' => $flow,
            ];
        }
        
        // Log the user input
        $this->logSessionAction($session, 'user_input', $input, $selectedOption['label']);
        
        // Store the selected option data in session
        $sessionData['selected_dynamic_option'] = $selectedOption;
        
        // Store the full item data for use in subsequent flows
        if (isset($selectedOption['data'])) {
            $sessionData['selected_item_data'] = $selectedOption['data'];
            $sessionData['selected_item_value'] = $selectedOption['value'];
            $sessionData['selected_item_label'] = $selectedOption['label'];
            
            // Extract specific fields for template variables
            $itemData = $selectedOption['data'];
            $sessionData['selected_service'] = $itemData['name'] ?? $itemData['service'] ?? $itemData['title'] ?? $selectedOption['label'];
            $sessionData['amount'] = $itemData['amount'] ?? $itemData['price'] ?? $itemData['cost'] ?? '0';
            $sessionData['phone_number'] = $session->phone_number ?? 'Not provided';
            
            // Store additional common fields that might be useful
            $sessionData['service_id'] = $itemData['id'] ?? $itemData['service_id'] ?? '';
            $sessionData['service_description'] = $itemData['description'] ?? $itemData['details'] ?? '';
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
        // Check if this option should use the registered phone number
        $actionData = $option->action_data ?? [];
        // Convert object to array if needed
        if (is_object($actionData)) {
            $actionData = (array) $actionData;
        }
        if (isset($actionData['use_registered_phone']) && $actionData['use_registered_phone']) {
            $sessionData = $session->session_data ?? [];
            $sessionData['recipient_phone'] = $session->phone_number;
            $sessionData['recipient_type'] = 'self';
            $session->update(['session_data' => $sessionData]);
            
            Log::info('Using registered phone number for option', [
                'session_id' => $session->id,
                'option_value' => $option->option_value,
                'phone_number' => $session->phone_number
            ]);
        }
        
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
                
            case 'process_survey':
                return $this->handleProcessSurvey($session, $option);
                
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
            $apiConfig = \App\Models\ExternalAPIConfiguration::find($apiConfigId);
            
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
                throw new \Exception($result['message'] ?? 'API call failed');
            }
            
            // Handle success response
            return $this->handleApiCallSuccess($session, $option, $result);
            
        } catch (\Exception $e) {
            // Log the error
            $this->logSessionAction($session, 'api_call_error', null, $e->getMessage(), 'error');
            
            // Handle error response
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
        
        // Store API result in session data
        $sessionData = $session->session_data ?? [];
        $sessionData['last_api_result'] = $result;
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
            $nextFlowId = $option->next_flow_id;
            if ($nextFlowId) {
                $nextFlow = USSDFlow::find($nextFlowId);
                if ($nextFlow && $nextFlow->is_active) {
                    $session->update(['current_flow_id' => $nextFlow->id]);
                    
                    // Replace placeholders in menu text with both API response data and session data
                    $sessionData = $session->session_data ?? [];
                    $menuText = $this->replacePlaceholdersWithApiAndSessionData($nextFlow->menu_text, $result['data'], $sessionData);
                    
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
     */
    private function handleApiCallError(USSDSession $session, USSDFlowOption $option, \Exception $exception): array
    {
        $apiData = $option->action_data ?? [];
        if (is_object($apiData)) {
            $apiData = (array) $apiData;
        }
        $errorMessage = $apiData['error_message'] ?? 'Service temporarily unavailable. Please try again later.';
        
        // Store the actual error message in session data for placeholder replacement
        $sessionData = $session->session_data ?? [];
        $sessionData['error_message'] = $exception->getMessage() ?: $errorMessage;
        $session->update(['session_data' => $sessionData]);
        
        // Check if there's an error flow to navigate to
        $errorFlowId = $apiData['error_flow_id'] ?? null;
        
        if ($errorFlowId) {
            $errorFlow = USSDFlow::find($errorFlowId);
            if ($errorFlow && $errorFlow->is_active) {
                $session->update(['current_flow_id' => $errorFlow->id]);
                
                // Replace placeholders in menu text with session data including error message
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
        return preg_replace_callback('/\{api\.([^}]+)\}/', function($matches) use ($apiData) {
            $field = $matches[1];
            return $apiData[$field] ?? '';
        }, $text);
    }

    /**
     * Replace placeholders in text with both API data and session data
     */
    private function replacePlaceholdersWithApiAndSessionData(string $text, array $apiData, array $sessionData): string
    {
        // First replace API placeholders
        $text = $this->replacePlaceholdersWithApiData($text, $apiData);
        
        // Then replace session data placeholders
        $text = $this->replacePlaceholders($text, $sessionData);
        
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

        // Store the collected input with the specified key
        $storeAs = $actionData['store_as'] ?? $inputType;
        $sessionData[$storeAs] = $input;
        $sessionData['collected_inputs'][$inputType] = $input;
        $sessionData['collecting_input'] = false;
        $sessionData['input_type'] = null;
        $sessionData['input_prompt'] = null;
        $sessionData['input_action_data'] = null;
        
        $session->update(['session_data' => $sessionData]);

        // Log the input collection
        $this->logSessionAction($session, 'input_collected', $input, "Collected $inputType: $input");

        // Check if there's a next flow to navigate to or if we should end session
        $nextFlowId = $sessionData['next_flow_after_input'] ?? null;
        $endSessionAfterInput = $actionData['end_session_after_input'] ?? false;
        
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
                
                // Replace placeholders in menu text with session data
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
     * Handle survey processing
     */
    private function handleProcessSurvey(USSDSession $session, USSDFlowOption $option): array
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
                'message' => $actionData['error_message'] ?? 'Please complete all survey questions first.',
                'requires_input' => true,
                'current_flow' => $session->currentFlow,
            ];
        }
        
        // Generate survey ID
        $surveyId = 'SUR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        // Process income range text
        $incomeText = $this->getIncomeRangeText($sessionData['survey_income'] ?? '');
        
        // Generate recommendations based on profile
        $recommendations = $this->generateRecommendations($sessionData);
        
        // Store survey data
        $sessionData['survey_id'] = $surveyId;
        $sessionData['survey_completed'] = true;
        $sessionData['survey_timestamp'] = now()->toISOString();
        $sessionData['survey_income_text'] = $incomeText;
        $sessionData['recommendations'] = $recommendations;
        
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
            'message' => 'Survey completed successfully!',
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
        $replacements = [
            '{customer_name}' => $sessionData['customer_name'] ?? 'User',
            '{customer_email}' => $sessionData['customer_email'] ?? 'Not provided',
            '{customer_phone}' => $sessionData['customer_phone'] ?? 'Not provided',
            '{customer_address}' => $sessionData['customer_address'] ?? 'Not provided',
            '{feedback_name}' => $sessionData['feedback_name'] ?? 'Anonymous',
            '{service_rating}' => $sessionData['service_rating'] ?? '0',
            '{feedback_comment}' => $sessionData['feedback_comment'] ?? 'No comment',
            '{survey_age}' => $sessionData['survey_age'] ?? '0',
            '{survey_occupation}' => $sessionData['survey_occupation'] ?? 'Not specified',
            '{survey_city}' => $sessionData['survey_city'] ?? 'Not specified',
            '{survey_income_text}' => $sessionData['survey_income_text'] ?? 'Not specified',
            '{contact_name}' => $sessionData['contact_name'] ?? 'User',
            '{contact_phone}' => $sessionData['contact_phone'] ?? 'Not provided',
            '{contact_email}' => $sessionData['contact_email'] ?? 'Not provided',
            '{contact_whatsapp}' => $sessionData['contact_whatsapp'] ?? 'Not provided',
            '{timestamp}' => date('YmdHis'),
            '{recommendations}' => $sessionData['recommendations'] ?? 'No specific recommendations at this time.',
            // Marketplace integration placeholders
            '{phone}' => $sessionData['phone'] ?? 'Not provided',
            '{amount}' => $sessionData['amount'] ?? '0',
            '{transaction_id}' => $sessionData['transaction_id'] ?? 'N/A',
            '{error_message}' => $sessionData['error_message'] ?? 'Unknown error',
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Get income range text from selection
     */
    private function getIncomeRangeText(string $incomeSelection): string
    {
        $incomeRanges = [
            '1' => 'Below ₦50,000',
            '2' => '₦50,000 - ₦100,000',
            '3' => '₦100,000 - ₦200,000',
            '4' => 'Above ₦200,000',
        ];
        
        return $incomeRanges[$incomeSelection] ?? 'Not specified';
    }

    /**
     * Generate recommendations based on survey data
     */
    private function generateRecommendations(array $sessionData): string
    {
        $age = (int)($sessionData['survey_age'] ?? 0);
        $occupation = strtolower($sessionData['survey_occupation'] ?? '');
        $income = $sessionData['survey_income'] ?? '';
        
        $recommendations = [];
        
        // Age-based recommendations
        if ($age >= 18 && $age <= 25) {
            $recommendations[] = '• Student-friendly mobile banking features';
        } elseif ($age >= 26 && $age <= 35) {
            $recommendations[] = '• Investment and savings products';
        } elseif ($age >= 36 && $age <= 50) {
            $recommendations[] = '• Family banking and insurance products';
        } else {
            $recommendations[] = '• Retirement planning services';
        }
        
        // Occupation-based recommendations
        if (strpos($occupation, 'student') !== false) {
            $recommendations[] = '• Student loan and scholarship information';
        } elseif (strpos($occupation, 'teacher') !== false || strpos($occupation, 'professor') !== false) {
            $recommendations[] = '• Education sector banking products';
        } elseif (strpos($occupation, 'engineer') !== false) {
            $recommendations[] = '• Technology and innovation banking';
        } elseif (strpos($occupation, 'doctor') !== false || strpos($occupation, 'nurse') !== false) {
            $recommendations[] = '• Healthcare professional banking';
        }
        
        // Income-based recommendations
        if ($income === '1') {
            $recommendations[] = '• Budget-friendly banking solutions';
        } elseif ($income === '2') {
            $recommendations[] = '• Savings and investment opportunities';
        } elseif ($income === '3' || $income === '4') {
            $recommendations[] = '• Premium banking and wealth management';
        }
        
        // Default recommendation
        if (empty($recommendations)) {
            $recommendations[] = '• Personalized banking consultation';
        }
        
        return implode("\n", $recommendations);
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
    private function findMarketplaceApiByService(USSDSession $session): ?\App\Models\ExternalAPIConfiguration
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
        return \App\Models\ExternalAPIConfiguration::where('name', $apiName)
            ->where('is_marketplace_template', true)
            ->where('is_active', true)
            ->first();
    }
}
