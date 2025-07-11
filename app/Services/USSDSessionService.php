<?php

namespace App\Services;

use App\Models\USSD;
use App\Models\USSDFlow;
use App\Models\USSDFlowOption;
use App\Models\USSDSession;
use App\Models\USSDSessionLog;
use Illuminate\Support\Str;
use Carbon\Carbon;

class USSDSessionService
{
    /**
     * Start a new USSD session
     */
    public function startSession(USSD $ussd, ?string $phoneNumber = null, ?string $userAgent = null, ?string $ipAddress = null): USSDSession
    {
        // Get the root flow for this USSD
        $rootFlow = USSDFlow::where('ussd_id', $ussd->id)
            ->where('is_root', true)
            ->where('is_active', true)
            ->first();

        if (!$rootFlow) {
            throw new \Exception('No root flow found for this USSD service');
        }

        // Create session
        $session = USSDSession::create([
            'ussd_id' => $ussd->id,
            'session_id' => Str::uuid(),
            'phone_number' => $phoneNumber,
            'current_flow_id' => $rootFlow->id,
            'status' => 'active',
            'step_count' => 0,
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(30), // 30 minute timeout
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);

        // Log the session start
        $this->logSessionAction($session, 'session_start', null, $rootFlow->menu_text);

        return $session;
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
                return $this->handleInputCollection($session, $input, $inputType, $inputPrompt);
            }

            // Find the option that matches user input
            $selectedOption = USSDFlowOption::where('flow_id', $currentFlow->id)
                ->where('option_value', $input)
                ->where('is_active', true)
                ->first();

            if (!$selectedOption) {
                // Invalid input - show error message
                $errorMessage = "Invalid option. Please try again.\n\n" . $currentFlow->menu_text;
                $this->logSessionAction($session, 'invalid_input', $input, $errorMessage, 'error');
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'requires_input' => true,
                    'current_flow' => $currentFlow,
                ];
            }

            // Log the user input
            $this->logSessionAction($session, 'user_input', $input, $selectedOption->option_text);

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
            $this->logSessionAction($session, 'error', $input, $errorMessage, 'error', null, $e->getMessage());
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'requires_input' => true,
                'current_flow' => $currentFlow,
            ];
        }
    }

    /**
     * Handle different action types
     */
    private function handleAction(USSDSession $session, USSDFlowOption $option): array
    {
        switch ($option->action_type) {
            case 'navigate':
                return $this->handleNavigation($session, $option);
                
            case 'message':
                return $this->handleMessage($session, $option);
                
            case 'end_session':
                return $this->handleEndSession($session, $option);
                
            case 'api_call':
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
    private function handleNavigation(USSDSession $session, USSDFlowOption $option): array
    {
        if (!$option->next_flow_id) {
            // No next flow - end session
            return $this->handleEndSession($session, $option);
        }

        $nextFlow = USSDFlow::find($option->next_flow_id);
        
        if (!$nextFlow || !$nextFlow->is_active) {
            throw new \Exception('Next flow not found or inactive');
        }

        // Update session to new flow
        $session->update([
            'current_flow_id' => $nextFlow->id,
        ]);

        return [
            'success' => true,
            'message' => $nextFlow->menu_text,
            'requires_input' => true,
            'current_flow' => $nextFlow,
            'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }

    /**
     * Handle message response
     */
    private function handleMessage(USSDSession $session, USSDFlowOption $option): array
    {
        $message = $option->action_data['message'] ?? 'Thank you for using our service.';
        
        // End session after showing message
        $session->update(['status' => 'completed']);
        
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
    private function handleEndSession(USSDSession $session, USSDFlowOption $option): array
    {
        $message = $option->action_data['message'] ?? 'Thank you for using our service.';
        
        $session->update(['status' => 'completed']);
        
        return [
            'success' => true,
            'message' => $message,
            'requires_input' => false,
            'session_ended' => true,
        ];
    }

    /**
     * Handle API call (simulated)
     */
    private function handleApiCall(USSDSession $session, USSDFlowOption $option): array
    {
        // Simulate API call delay
        usleep(500000); // 0.5 seconds
        
        $apiData = $option->action_data;
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
     * Handle input request (PIN, account number, phone number, etc.)
     */
    private function handleInputRequest(USSDSession $session, USSDFlowOption $option): array
    {
        $actionData = $option->action_data ?? [];
        $prompt = $actionData['prompt'] ?? 'Please enter your input:';
        
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

        // Check if there's a next flow to navigate to
        $nextFlowId = $sessionData['next_flow_after_input'] ?? null;
        
        if ($nextFlowId) {
            $nextFlow = USSDFlow::find($nextFlowId);
            if ($nextFlow && $nextFlow->is_active) {
                $session->update(['current_flow_id' => $nextFlow->id]);
                $sessionData['next_flow_after_input'] = null;
                $session->update(['session_data' => $sessionData]);
                
                return [
                    'success' => true,
                    'message' => $nextFlow->menu_text,
                    'requires_input' => true,
                    'current_flow' => $nextFlow,
                    'options' => $nextFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
                ];
            }
        }

        // If no next flow specified, return to current flow with success message
        $currentFlow = $session->currentFlow;
        $successMessage = $actionData['success_message'] ?? "Data saved successfully!\n\n" . $currentFlow->menu_text;
        
        return [
            'success' => true,
            'message' => $successMessage,
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
        
        // Basic validation for phone numbers
        if (strlen($cleanInput) < 10 || strlen($cleanInput) > 15) {
            return ['valid' => false, 'error_message' => $errorMessage];
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
}
