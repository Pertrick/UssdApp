<?php

namespace App\Http\Controllers;

use App\Enums\EnvironmentType;
use App\Models\USSD;
use App\Models\USSDSession;
use App\Models\FlowStep;
use App\Services\USSDSessionService;
use App\Services\DynamicFlowEngine;
use App\Services\ExternalAPIService;
use App\Services\APITestLoggingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class USSDSimulatorController extends Controller
{
    protected $sessionService;
    protected $dynamicFlowEngine;

    public function __construct(USSDSessionService $sessionService, APITestLoggingService $loggingService)
    {
        $this->sessionService = $sessionService;
        $this->dynamicFlowEngine = new DynamicFlowEngine(new ExternalAPIService($loggingService));
    }

    /**
     * Show the simulator UI for a USSD service
     */
    public function showSimulator(USSD $ussd)
    {

        // Validate USSD can be tested
        $validationError = $this->validateUssdForSimulator($ussd);
        if ($validationError) {
            return redirect()->route('ussd.show', $ussd->id)
                ->with('error', $validationError);
        }

        return Inertia::render('USSD/Simulator', [
            'ussd' => $ussd->load('environment'),
        ]);
    }

    /**
     * Start a new USSD session
     */
    public function startSession(Request $request, USSD $ussd)
    {
        // SECURITY: Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== \Illuminate\Support\Facades\Auth::id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to USSD service',
            ], 403);
        }

        // Validate USSD can be tested
        $validationError = $this->validateUssdForSimulator($ussd, $request->input('environment', 'simulation'));
        if ($validationError) {
            return response()->json([
                'success' => false,
                'error' => $validationError,
            ], 400);
        }

        $phoneNumber = $request->input('phone_number');
        $environment = $request->input('environment', 'simulation');
        $sessionId = $request->input('session_id'); // Optional: allows reusing existing session
        $userAgent = $request->header('User-Agent');
        $ipAddress = $request->ip();

        try {
            // Check if this USSD has dynamic flows (new system) or old FlowStep system
            $hasNewDynamicFlows = $ussd->flows()->where('flow_type', 'dynamic')->exists();
            $hasOldDynamicFlows = FlowStep::where('ussd_id', $ussd->id)->exists();
            
            if ($hasNewDynamicFlows || $hasOldDynamicFlows) {
                // Use dynamic flow engine
                return $this->startDynamicSession($ussd, $phoneNumber, $environment, $userAgent, $ipAddress, $sessionId);
            } else {
                // Use static flow system
                return $this->startStaticSession($ussd, $phoneNumber, $environment, $userAgent, $ipAddress, $sessionId);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Start a dynamic flow session
     */
    protected function startDynamicSession(USSD $ussd, $phoneNumber, $environment, $userAgent, $ipAddress, $sessionId = null)
    {
        $session = $this->sessionService->startSession($ussd, $phoneNumber, $userAgent, $ipAddress, $environment, $sessionId);
        
        // Check if this is using the new flow_type system or old FlowStep system
        $hasNewDynamicFlows = $ussd->flows()->where('flow_type', 'dynamic')->exists();
        $hasOldDynamicFlows = FlowStep::where('ussd_id', $ussd->id)->exists();
        
        if ($hasNewDynamicFlows) {
            // Use new dynamic flow system
            $currentFlow = $session->currentFlow;
            
            if (!$currentFlow) {
                return response()->json([
                    'success' => false,
                    'error' => 'No current flow found for this session. Please ensure the USSD service has a root flow configured.',
                ], 400);
            }
            
            // Get the flow display (handles both static and dynamic flows)
            $display = $this->sessionService->getCurrentFlowDisplay($session);
            
            return response()->json([
                'success' => $display['success'],
                'session_id' => $session->session_id,
                'menu_text' => $display['message'],
                'flow_title' => $display['flow_title'],
                'flow_description' => $display['flow_description'] ?? $currentFlow->description,
                'current_flow_id' => $session->current_flow_id,
                'options' => $display['dynamic_options'] ?? [],
                'is_dynamic' => $currentFlow->flow_type === 'dynamic',
            ]);
        } else {
            // Use old FlowStep system
            // Execute the welcome step first
            $welcomeResult = $this->dynamicFlowEngine->executeStep($session, 'welcome', []);
            
            // Then execute the main menu step
            $mainMenuResult = $this->dynamicFlowEngine->executeStep($session, 'main_menu', []);
            
            // Update session with current step
            $sessionData = $session->session_data ?? [];
            $sessionData['current_step'] = 'main_menu';
            $session->update(['session_data' => $sessionData]);
            
            // Combine welcome message and main menu
            $combinedText = $this->formatDynamicResponse($welcomeResult) . "\n\n" . $this->formatDynamicResponse($mainMenuResult);
            
            return response()->json([
                'success' => true,
                'session_id' => $session->session_id,
                'menu_text' => $combinedText,
                'flow_title' => $mainMenuResult['result']['title'] ?? '',
                'current_flow_id' => $session->current_flow_id,
                'options' => $mainMenuResult['result']['options'] ?? [],
                'is_dynamic' => true,
            ]);
        }
    }
    
    /**
     * Start a static flow session
     */
    protected function startStaticSession(USSD $ussd, $phoneNumber, $environment, $userAgent, $ipAddress, $sessionId = null)
    {
        $session = $this->sessionService->startSession($ussd, $phoneNumber, $userAgent, $ipAddress, $environment, $sessionId);

        // Load the current flow with options
        $currentFlow = $session->currentFlow;
        
        if (!$currentFlow) {
            return response()->json([
                'success' => false,
                'error' => 'No current flow found for this session. Please ensure the USSD service has a root flow configured.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'session_id' => $session->session_id,
            'menu_text' => $currentFlow->menu_text,
            'flow_title' => $currentFlow->title,
            'flow_description' => $currentFlow->description,
            'current_flow_id' => $session->current_flow_id,
            'options' => $currentFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
            'is_dynamic' => false,
        ]);
    }

    /**
     * Process user input for a session
     */
    public function processInput(Request $request, USSD $ussd)
    {
        $sessionId = $request->input('session_id');
        $input = $request->input('input');

        // First check if session exists (regardless of status)
        $session = USSDSession::where('ussd_id', $ussd->id)
            ->where('session_id', $sessionId)
            ->first();
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found',
                'message' => 'Session not found. Please start a new session.',
                'session_ended' => true,
            ], 404);
        }
        
        // Check if session is already completed/ended
        if ($session->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'Session has ended',
                'message' => 'This session has ended. Please start a new session.',
                'session_ended' => true,
            ], 200); // Return 200 to show message, not 404
        }

        // Check if this is a dynamic flow session
        $sessionData = $session->session_data ?? [];
        $currentFlow = $session->currentFlow;
        
        // Check if current flow is dynamic type or if using old FlowStep system
        $isDynamic = ($currentFlow && $currentFlow->flow_type === 'dynamic');
                    //  isset($sessionData['current_step']) || 
                    //  FlowStep::where('ussd_id', $ussd->id)->exists();
        
        if ($isDynamic) {
            // Use new dynamic flow system if flow_type is dynamic, otherwise use old system
            if ($currentFlow && $currentFlow->flow_type === 'dynamic') {
                $result = $this->sessionService->processInput($session, $input);
                
                // If the input was processed successfully and we're not ending the session,
                // refresh the session and get the current flow display for the updated flow
                if ($result['success'] && !($result['session_ended'] ?? false)) {
                    $session->refresh(); // Refresh the session to get updated current_flow_id
                    $display = $this->sessionService->getCurrentFlowDisplay($session);
                    return response()->json($display);
                }
                
                return response()->json($result);
            } else {
                return $this->processDynamicInput($session, $input);
            }
        } else {
            $result = $this->sessionService->processInput($session, $input);
            
            // If the input was processed successfully and we're not ending the session,
            // refresh the session and get the current flow display for the updated flow
            if ($result['success'] && !($result['session_ended'] ?? false)) {
                $session->refresh(); // Refresh the session to get updated current_flow_id
                $display = $this->sessionService->getCurrentFlowDisplay($session);
                return response()->json($display);
            }
            
            return response()->json($result);
        }
    }
    
    /**
     * Process input for dynamic flow
     */
    protected function processDynamicInput(USSDSession $session, $input)
    {
        try {
            $sessionData = $session->session_data ?? [];
            $currentStep = $sessionData['current_step'] ?? 'welcome';
            
            // Store user input
            $sessionData['last_user_input'] = $input;
            $session->update(['session_data' => $sessionData]);
            
            // Determine next step based on current step and input
            $nextStep = $this->determineNextStep($currentStep, $input, $sessionData);
            
            // Execute the next step
            $result = $this->dynamicFlowEngine->executeStep($session, $nextStep, ['value' => $input]);
            
            // Update session
            $sessionData['current_step'] = $nextStep;
            $session->update(['session_data' => $sessionData]);
            
            return response()->json([
                'success' => true,
                'menu_text' => $this->formatDynamicResponse($result),
                'flow_title' => $result['result']['title'] ?? '',
                'options' => $result['result']['options'] ?? [],
                'is_dynamic' => true,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'is_dynamic' => true,
            ], 500);
        }
    }
    
    /**
     * Determine next step based on current step and input
     */
    protected function determineNextStep($currentStep, $input, $sessionData)
    {
        switch ($currentStep) {
            case 'welcome':
                return 'main_menu';
                
            case 'main_menu':
                switch ($input) {
                    case '1':
                        return 'banking_menu';
                    case '2':
                        return 'airtime_menu';
                    case '3':
                        return 'account_info';
                    case '4':
                        return 'exit';
                    default:
                        return 'main_menu';
                }
                
            case 'banking_menu':
                return 'banking_api_call';
                
            case 'airtime_menu':
                return 'airtime_api_call';
                
            case 'banking_api_call':
                return 'show_banking_result';
                
            case 'airtime_api_call':
                return 'show_airtime_result';
                
            case 'show_banking_result':
            case 'show_airtime_result':
            case 'account_info':
                return 'main_menu';
                
            // Handle the simple demo flow as well
            case 'dynamic_menu_demo':
                return 'show_selection';
            case 'api_call_demo':
                return 'show_api_result';
            case 'input_demo':
                return 'show_input_result';
            case 'show_selection':
            case 'show_api_result':
            case 'show_input_result':
                return 'main_menu';
                
            default:
                return 'main_menu';
        }
    }
    
    /**
     * Format dynamic response for simulator
     */
    protected function formatDynamicResponse($result)
    {
        $response = '';
        
        if (isset($result['result']['title']) && !empty($result['result']['title'])) {
            $response .= $result['result']['title'] . "\n";
        }
        
        if (isset($result['result']['message']) && !empty($result['result']['message'])) {
            $response .= $result['result']['message'] . "\n";
        }
        
        if (isset($result['result']['prompt']) && !empty($result['result']['prompt'])) {
            $response .= $result['result']['prompt'] . "\n";
        }
        
        if (isset($result['result']['options']) && !empty($result['result']['options'])) {
            foreach ($result['result']['options'] as $index => $option) {
                $response .= ($index + 1) . ". " . $option['label'] . "\n";
            }
        }
        
        return trim($response);
    }

    /**
     * Validate USSD can be tested in simulator
     * 
     * @param USSD $ussd The USSD service to validate
     * @param string|null $environment Optional environment to check (testing/production)
     * @return string|null Error message if validation fails, null if valid
     */
    protected function validateUssdForSimulator(USSD $ussd, ?string $environment = null): ?string
    {
        // Check if USSD is active
        if (!$ussd->is_active) {
            return 'This USSD service is inactive and cannot be tested. Please activate it first.';
        }

        // Determine which environment to check
        $checkEnvironment = $environment ?? $ussd->environment?->name ?? EnvironmentType::TESTING->value;
        
        // Normalize environment name
        if ($checkEnvironment === 'simulation' || $checkEnvironment === 'test') {
            $checkEnvironment = EnvironmentType::TESTING->value;
        }
        
        // Check if USSD has a code for the specified environment
        if ($checkEnvironment === EnvironmentType::PRODUCTION->value) {
            // For production: need pattern
            $hasCode = !empty($ussd->pattern);
            if (!$hasCode) {
                return 'This USSD service does not have a live USSD code configured. Please configure a live USSD code before testing in production.';
            }
        } else {
            // For testing: need pattern
            $hasCode = !empty($ussd->pattern);
            if (!$hasCode) {
                return 'This USSD service does not have a testing USSD code configured. Please configure a testing USSD code or pattern before testing.';
            }
        }

        return null; // Validation passed
    }

    /**
     * Get session logs for monitoring
     */
    public function getSessionLogs(Request $request, USSD $ussd)
    {
        $sessionId = $request->input('session_id');
        $logs = $ussd->sessions()->where('session_id', $sessionId)->first()?->logs()->orderBy('action_timestamp')->get();
        return response()->json(['logs' => $logs]);
    }

    /**
     * Get analytics for a USSD service
     */
    public function getAnalytics(Request $request, USSD $ussd)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $analytics = $this->sessionService->getSessionAnalytics($ussd, $startDate, $endDate);
        return response()->json($analytics);
    }
}
