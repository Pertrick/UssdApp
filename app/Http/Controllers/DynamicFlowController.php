<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use App\Models\USSDSession;
use App\Models\FlowStep;
use App\Models\FlowConfig;
use App\Services\DynamicFlowEngine;
use App\Services\USSDSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DynamicFlowController extends Controller
{
    protected DynamicFlowEngine $flowEngine;
    protected USSDSessionService $sessionService;
    
    public function __construct(DynamicFlowEngine $flowEngine, USSDSessionService $sessionService)
    {
        $this->flowEngine = $flowEngine;
        $this->sessionService = $sessionService;
    }
    
    /**
     * Handle incoming USSD request for dynamic flows
     */
    public function handle(Request $request)
    {
        try {
            $phoneNumber = $request->input('phone');
            $userInput = $request->input('text', '');
            $ussdPattern = $request->input('ussd', '*123#');
            
            // Find the USSD service
            $ussd = USSD::where('pattern', $ussdPattern)
                ->where('is_active', true)
                ->first();
                
            if (!$ussd) {
                return $this->formatUssdResponse('Invalid USSD code. Please try again.');
            }
            
            // Get or create session
            $session = $this->getOrCreateSession($ussd, $phoneNumber);
            
            // Parse user input
            $inputData = $this->parseUserInput($userInput);
            
            // Determine current step
            $currentStep = $this->determineCurrentStep($session, $inputData);
            
            // Execute the step
            $result = $this->flowEngine->executeStep($session, $currentStep, $inputData);
            
            // Update session
            $this->updateSession($session, $currentStep, $result);
            
            // Format response
            return $this->formatStepResponse($result);
            
        } catch (\Exception $e) {
            Log::error('Dynamic Flow Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return $this->formatUssdResponse('An error occurred. Please try again later.');
        }
    }
    
    /**
     * Get or create a USSD session
     */
    protected function getOrCreateSession(USSD $ussd, ?string $phoneNumber): USSDSession
    {
        // Try to find existing active session
        $session = USSDSession::where('ussd_id', $ussd->id)
            ->where('phone_number', $phoneNumber)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
            
        if ($session) {
            // Update last activity
            $session->update([
                'last_activity' => now(),
                'step_count' => $session->step_count + 1,
            ]);
            
            return $session;
        }
        
        // Create new session
        return $this->sessionService->startSession($ussd, $phoneNumber);
    }
    
    /**
     * Parse user input
     */
    protected function parseUserInput(string $userInput): array
    {
        if (empty($userInput)) {
            return ['value' => '', 'step' => 'welcome'];
        }
        
        $parts = explode('*', $userInput);
        $lastPart = end($parts);
        
        return [
            'value' => $lastPart,
            'step' => 'user_input',
            'full_input' => $userInput,
        ];
    }
    
    /**
     * Determine current step based on session and input
     */
    protected function determineCurrentStep(USSDSession $session, array $inputData): string
    {
        $sessionData = $session->session_data ?? [];
        
        // If this is a new session, start with welcome
        if (empty($sessionData) || !isset($sessionData['current_step'])) {
            return 'welcome';
        }
        
        // If user provided input, we need to process it
        if (!empty($inputData['value'])) {
            return $this->processUserInput($session, $inputData);
        }
        
        // Return current step
        return $sessionData['current_step'] ?? 'welcome';
    }
    
    /**
     * Process user input and determine next step
     */
    protected function processUserInput(USSDSession $session, array $inputData): string
    {
        $sessionData = $session->session_data ?? [];
        $currentStep = $sessionData['current_step'] ?? 'welcome';
        
        // Store user input
        $sessionData['last_user_input'] = $inputData['value'];
        $session->update(['session_data' => $sessionData]);
        
        // Handle different step types
        switch ($currentStep) {
            case 'select_network':
                // Store selected network
                $sessionData['selected_network'] = $inputData['value'];
                $session->update(['session_data' => $sessionData]);
                return 'fetch_bundles';
                
            case 'select_bundle':
                // Store selected bundle
                $sessionData['selected_bundle'] = $inputData['value'];
                $session->update(['session_data' => $sessionData]);
                return 'confirm_purchase';
                
            case 'confirm_purchase':
                if ($inputData['value'] === 'yes') {
                    return 'process_purchase';
                } else {
                    return 'cancelled';
                }
                
            default:
                return $currentStep;
        }
    }
    
    /**
     * Update session with step result
     */
    protected function updateSession(USSDSession $session, string $currentStep, array $result): void
    {
        $sessionData = $session->session_data ?? [];
        $sessionData['current_step'] = $result['next_step'] ?? $currentStep;
        $sessionData['last_step_result'] = $result;
        
        $session->update(['session_data' => $sessionData]);
    }
    
    /**
     * Format step response for USSD
     */
    protected function formatStepResponse(array $result): string
    {
        $response = '';
        
        // Add title if present
        if (isset($result['result']['title']) && !empty($result['result']['title'])) {
            $response .= $result['result']['title'] . "\n";
        }
        
        // Add message if present
        if (isset($result['result']['message']) && !empty($result['result']['message'])) {
            $response .= $result['result']['message'] . "\n";
        }
        
        // Add prompt if present
        if (isset($result['result']['prompt']) && !empty($result['result']['prompt'])) {
            $response .= $result['result']['prompt'] . "\n";
        }
        
        // Add options if present
        if (isset($result['result']['options']) && !empty($result['result']['options'])) {
            foreach ($result['result']['options'] as $index => $option) {
                $response .= ($index + 1) . ". " . $option['label'] . "\n";
            }
        }
        
        return $this->formatUssdResponse($response);
    }
    
    /**
     * Format USSD response
     */
    protected function formatUssdResponse(string $message): string
    {
        return "CON " . trim($message);
    }
    
    /**
     * Show the dynamic flow builder
     */
    public function builder(USSD $ussd)
    {
        $flowSteps = FlowStep::where('ussd_id', $ussd->id)->orderBy('sort_order')->get();
        $flowConfigs = FlowConfig::where('ussd_id', $ussd->id)->get();
        
        return inertia('USSD/DynamicFlowBuilder', [
            'ussd' => $ussd,
            'flowSteps' => $flowSteps,
            'flowConfigs' => $flowConfigs,
        ]);
    }
    
    /**
     * Store a new flow step
     */
    public function storeStep(Request $request, USSD $ussd)
    {
        $request->validate([
            'step_id' => 'required|string|max:255',
            'type' => 'required|string|in:menu,api_call,dynamic_menu,input,condition,message',
            'next_step' => 'nullable|string|max:255',
            'data' => 'nullable|array',
        ]);
        
        $step = FlowStep::create([
            'ussd_id' => $ussd->id,
            'step_id' => $request->step_id,
            'type' => $request->type,
            'next_step' => $request->next_step,
            'data' => $request->data ?? [],
            'sort_order' => FlowStep::where('ussd_id', $ussd->id)->max('sort_order') + 1,
            'is_active' => true,
        ]);
        
        return redirect()->back()->with('success', 'Flow step created successfully.');
    }
    
    /**
     * Update a flow step
     */
    public function updateStep(Request $request, USSD $ussd, FlowStep $step)
    {
        $request->validate([
            'step_id' => 'required|string|max:255',
            'type' => 'required|string|in:menu,api_call,dynamic_menu,input,condition,message',
            'next_step' => 'nullable|string|max:255',
            'data' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        
        $step->update($request->only(['step_id', 'type', 'next_step', 'data', 'is_active']));
        
        return redirect()->back()->with('success', 'Flow step updated successfully.');
    }
    
    /**
     * Delete a flow step
     */
    public function destroyStep(USSD $ussd, FlowStep $step)
    {
        $step->delete();
        
        return redirect()->back()->with('success', 'Flow step deleted successfully.');
    }
    
    /**
     * Store a new flow config
     */
    public function storeConfig(Request $request, USSD $ussd)
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
            'description' => 'nullable|string|max:1000',
        ]);
        
        FlowConfig::create([
            'ussd_id' => $ussd->id,
            'key' => $request->key,
            'value' => $request->value,
            'description' => $request->description,
            'is_active' => true,
        ]);
        
        return redirect()->back()->with('success', 'Flow configuration created successfully.');
    }
    
    /**
     * Delete a flow config
     */
    public function destroyConfig(USSD $ussd, FlowConfig $config)
    {
        $config->delete();
        
        return redirect()->back()->with('success', 'Flow configuration deleted successfully.');
    }
    
    /**
     * Test a specific flow step
     */
    public function testStep(Request $request)
    {
        $stepId = $request->input('step_id');
        $ussdId = $request->input('ussd_id');
        
        if (!$stepId || !$ussdId) {
            return response()->json(['error' => 'step_id and ussd_id are required'], 400);
        }
        
        try {
            // Create a test session
            $ussd = USSD::find($ussdId);
            if (!$ussd) {
                return response()->json(['error' => 'USSD service not found'], 404);
            }
            
            $session = new USSDSession([
                'ussd_id' => $ussdId,
                'session_id' => 'test-' . uniqid(),
                'phone_number' => '+2348012345678',
                'session_data' => [
                    'selected_network' => 'mtn',
                    'test_mode' => true,
                ],
            ]);
            
            $result = $this->flowEngine->executeStep($session, $stepId, []);
            
            return response()->json([
                'success' => true,
                'step_id' => $stepId,
                'result' => $result,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}