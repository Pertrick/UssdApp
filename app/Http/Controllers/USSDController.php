<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use Inertia\Inertia;
use App\Models\USSDFlow;
use App\Models\Environment;
use Illuminate\Http\Request;
use App\Enums\EnvironmentType;
use App\Models\USSDFlowOption;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ExternalAPIConfiguration;
use App\Http\Requests\USSD\StoreUSSDRequest;
use App\Http\Requests\USSD\UpdateUSSDRequest;
use App\Services\EnvironmentManagementService;

class USSDController extends Controller
{
    /**
     * Display a listing of USSDs for the authenticated user.
     */
    public function index()
    {
        $ussds = Auth::user()->ussds()
            ->with(['business', 'environment'])
            ->latest()
            ->get();
        
        return Inertia::render('USSD/Index', [
            'ussds' => $ussds
        ]);
    }

    /**
     * Show the form for creating a new USSD.
     */
    public function create()
    {
        $businesses = Auth::user()->businesses()->get();
        
        return Inertia::render('USSD/Create', [
            'businesses' => $businesses
        ]);
    }

    /**
     * Store a newly created USSD in storage.
     */
    public function store(StoreUSSDRequest $request)
    {
        $validated = $request->validated();
        
        // Ensure the business belongs to the authenticated user
        $business = Auth::user()->primaryBusiness;
        
        // Get testing environment (default for new USSDs)
        $testingEnvironment = Environment::where('name', EnvironmentType::TESTING->value)->first();
        if (!$testingEnvironment) {
            // Fallback: create testing environment if it doesn't exist
            $testingEnvironment = Environment::create([
                'name' => EnvironmentType::TESTING->value,
                'label' => 'Testing',
                'description' => 'Real API calls in test/sandbox mode',
                'color' => 'yellow',
                'allows_real_api_calls' => true,
                'is_default' => true,
                'is_active' => true,
            ]);
        }
        
        $ussd = USSD::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'pattern' => $validated['pattern'],
            'user_id' => Auth::id(),
            'business_id' => $business->id,
            'environment_id' => $testingEnvironment->id,
            'is_active' => true,
        ]);

        // Create default root flow for USSD
        $ussd->createDefaultRootFlow();

        // Log the activity
        ActivityService::logUSSDCreated(Auth::id(), $ussd->id, $ussd->name);

        return redirect()->route('ussd.index')
            ->with('success', 'USSD created successfully!');
    }

    /**
     * Display the specified USSD.
     */
    public function show(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure USSD has a root flow
        $ussd->ensureRootFlow();

        return Inertia::render('USSD/Show', [
            'ussd' => $ussd->load(['business', 'environment', 'flows.options'])
        ]);
    }

    /**
     * Show the form for editing the specified USSD.
     */
    public function edit(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $businesses = Auth::user()->businesses()->get();
        
        return Inertia::render('USSD/Edit', [
            'ussd' => $ussd->load('business'),
            'businesses' => $businesses
        ]);
    }

    /**
     * Update the specified USSD in storage.
     */
    public function update(UpdateUSSDRequest $request, USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validated();
        
        $ussd->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'pattern' => $validated['pattern'],
            'business_id' => $validated['business_id'],
        ]);

        // Log the activity
        ActivityService::logUSSDUpdated(Auth::id(), $ussd->id, $ussd->name);

        return redirect()->route('ussd.index')
            ->with('success', 'USSD updated successfully!');
    }

    /**
     * Remove the specified USSD from storage.
     */
    public function destroy(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $ussdName = $ussd->name;
        $ussd->delete();

        // Log the activity
        ActivityService::logUSSDDeleted(Auth::id(), $ussdName);

        return redirect()->route('ussd.index')
            ->with('success', 'USSD deleted successfully!');
    }

    /**
     * Toggle the active status of a USSD.
     */
    public function toggleStatus(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $ussd->update(['is_active' => !$ussd->is_active]);

        return redirect()->route('ussd.index')
            ->with('success', 'USSD status updated successfully!');
    }

    /**
     * Show the USSD configuration page.
     */
    public function configure(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure USSD has a root flow
        $ussd->ensureRootFlow();

        // Get marketplace APIs (templates)
        $marketplaceApis = ExternalAPIConfiguration::where('category', 'marketplace')
            ->where('is_marketplace_template', true)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('marketplace_category')
            ->orderBy('name')
            ->get();

        // Get user's custom APIs
        $customApis = ExternalAPIConfiguration::where('user_id', Auth::id())
            ->where('category', 'custom')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('USSD/Configure', [
            'ussd' => $ussd->load(['flows' => function($query) {
                $query->with('options')->orderBy('sort_order');
            }]),
            'marketplaceApis' => $marketplaceApis,
            'customApis' => $customApis,
        ]);
    }

    /**
     * Show the USSD simulator.
     */
    public function simulator(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure USSD has a root flow
        $ussd->ensureRootFlow();

        return Inertia::render('USSD/Simulator', [
            'ussd' => $ussd->load(['business', 'flows.options'])
        ]);
    }

    /**
     * Store a new flow for the USSD.
     */
    public function storeFlow(Request $request, USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'errors' => ['general' => 'Unauthorized access']
            ], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:500',
                'flow_type' => 'required|in:static,dynamic',
                'dynamic_config' => 'nullable|array',
                'dynamic_config.api_configuration_id' => 'required_if:flow_type,dynamic|nullable|exists:external_api_configurations,id',
                'dynamic_config.list_path' => 'nullable|string|max:255',
                'dynamic_config.label_field' => 'nullable|string|max:100',
                'dynamic_config.value_field' => 'nullable|string|max:100',
                'dynamic_config.empty_message' => 'nullable|string|max:500',
                'dynamic_config.continuation_type' => 'nullable|in:continue,end,api_dependent',
                'dynamic_config.next_flow_id' => 'nullable|exists:ussd_flows,id',
                'dynamic_config.items_per_page' => 'nullable|integer|min:3|max:20',
                'dynamic_config.next_label' => 'nullable|string|max:20',
                'dynamic_config.back_label' => 'nullable|string|max:20',
            ], [
                'name.required' => 'Flow name is required.',
                'name.min' => 'Flow name must be at least 2 characters.',
                'name.max' => 'Flow name cannot exceed 255 characters.',
                'title.max' => 'Title cannot exceed 255 characters.',
                'description.max' => 'Description cannot exceed 500 characters.',
            ]);

            // Check for duplicate flow names within the same USSD
            $existingFlow = $ussd->flows()->where('name', $validated['name'])->first();
            if ($existingFlow) {
                return response()->json([
                    'success' => false,
                    'errors' => ['name' => 'A flow with this name already exists.']
                ], 422);
            }

            // Check for duplicate flow titles within the same USSD (if title is provided)
            if (!empty($validated['title'])) {
                $existingFlowWithTitle = $ussd->flows()->where('title', $validated['title'])->first();
                if ($existingFlowWithTitle) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['title' => 'A flow with this title already exists. Please use a unique title.']
                    ], 422);
                }
            }

            // Create the flow
            $flow = $ussd->flows()->create([
                'name' => $validated['name'],
                'title' => $validated['title'] ?? null,
                'menu_text' => '', // Will be auto-generated from options
                'description' => $validated['description'] ?? '',
                'flow_type' => $validated['flow_type'],
                'dynamic_config' => $validated['dynamic_config'] ?? null,
                'is_root' => false,
                'sort_order' => $ussd->flows()->count(),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'flow' => $flow->load('options'),
                'message' => 'Flow created successfully!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['general' => 'An error occurred while creating the flow.']
            ], 500);
        }
    }

    /**
     * Update a flow for the USSD.
     */
    public function updateFlow(Request $request, USSD $ussd, USSDFlow $flow)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure the flow belongs to the USSD
        if ($flow->ussd_id !== $ussd->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'title' => 'nullable|string|max:255',
            'menu_text' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:500',
            'flow_type' => 'required|in:static,dynamic',
            'dynamic_config' => 'nullable|array',
            'dynamic_config.api_configuration_id' => 'required_if:flow_type,dynamic|nullable|exists:external_api_configurations,id',
            'dynamic_config.list_path' => 'nullable|string|max:255',
            'dynamic_config.label_field' => 'nullable|string|max:100',
            'dynamic_config.value_field' => 'nullable|string|max:100',
            'dynamic_config.empty_message' => 'nullable|string|max:500',
            'dynamic_config.continuation_type' => 'nullable|in:continue,end,api_dependent',
            'dynamic_config.next_flow_id' => 'nullable|exists:ussd_flows,id',
            'dynamic_config.items_per_page' => 'nullable|integer|min:3|max:20',
            'dynamic_config.next_label' => 'nullable|string|max:20',
            'dynamic_config.back_label' => 'nullable|string|max:20',
            'options' => 'array',
            'options.*.option_text' => 'nullable|string|max:255',
            'options.*.option_value' => 'required|string|max:50|min:1',
            'options.*.action_type' => 'required|in:navigate,message,end_session,input_text,input_number,input_phone,input_account,input_pin,input_amount,input_selection,external_api_call',
            'options.*.action_data' => 'nullable|array',
            'options.*.action_data.message' => 'nullable|string|max:500',
            'options.*.action_data.prompt' => 'nullable|string|max:200',
            'options.*.action_data.error_message' => 'nullable|string|max:200',
            'options.*.action_data.use_registered_phone' => 'nullable|boolean',
            'options.*.action_data.success_message' => 'nullable|string|max:500',
            'options.*.action_data.store_data' => 'nullable|array',
            'options.*.action_data.store_data.*' => 'nullable',
            'options.*.next_flow_id' => 'nullable',
        ], [
            'name.required' => 'Flow name is required.',
            'name.min' => 'Flow name must be at least 2 characters.',
            'name.max' => 'Flow name cannot exceed 255 characters.',
            'menu_text.max' => 'Menu text cannot exceed 1000 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'options.*.option_text.max' => 'Option text cannot exceed 255 characters.',
            'options.*.option_value.required' => 'Option value is required.',
            'options.*.option_value.min' => 'Option value must be at least 1 character.',
            'options.*.option_value.max' => 'Option value cannot exceed 50 characters.',
            'options.*.action_type.required' => 'Action type is required.',
            'options.*.action_type.in' => 'Invalid action type.',
            'options.*.action_data.message.required_if' => 'Message is required when action type is message.',
            'options.*.action_data.message.max' => 'Message cannot exceed 500 characters.',
            'options.*.action_data.prompt.max' => 'Prompt cannot exceed 200 characters.',
            'options.*.action_data.error_message.max' => 'Error message cannot exceed 200 characters.',
            'options.*.next_flow_id.exists' => 'Selected flow does not exist.',
        ]);

        // Validate and auto-generate option_text
        if (isset($validated['options'])) {
            foreach ($validated['options'] as $index => $optionData) {
                $actionType = $optionData['action_type'] ?? null;
                
                // An action is an input action if it starts with 'input_' or is 'input_collection'
                $isInputAction = $actionType && (str_starts_with($actionType, 'input_') || $actionType === 'input_collection');
                
                // Validate message is required when action_type is "message"
                if ($actionType === 'message') {
                    $message = $optionData['action_data']['message'] ?? null;
                    if (empty($message) || !is_string($message)) {
                        return response()->json([
                            'success' => false,
                            'errors' => [
                                "options.{$index}.action_data.message" => ['Message is required when action type is message.']
                            ]
                        ], 422);
                    }
                }
                
                // Validate option_text is required for non-input actions
                if (!$isInputAction && empty($optionData['option_text'])) {
                    return response()->json([
                        'success' => false,
                        'errors' => [
                            "options.{$index}.option_text" => ['Option text is required for this action type.']
                        ]
                    ], 422);
                }
                
                // Auto-generate option_text for input actions if empty
                if ($isInputAction && empty($optionData['option_text'])) {
                    $defaultTexts = [
                        'input_phone' => 'Enter Phone Number',
                        'input_text' => 'Enter Text',
                        'input_number' => 'Enter Number',
                        'input_account' => 'Enter Account Number',
                        'input_pin' => 'Enter PIN',
                        'input_amount' => 'Enter Amount',
                        'input_selection' => 'Make Selection',
                    ];
                    $validated['options'][$index]['option_text'] = $defaultTexts[$actionType] ?? 'Continue';
                }
            }
        }

        // Check for duplicate flow names within the same USSD (excluding current flow)
        $existingFlow = $ussd->flows()->where('name', $validated['name'])->where('id', '!=', $flow->id)->first();
        if ($existingFlow) {
            return response()->json([
                'success' => false,
                'errors' => ['name' => 'A flow with this name already exists.']
            ], 422);
        }

        if (!empty($validated['title'])) {
            $existingFlowWithTitle = $ussd->flows()->where('title', $validated['title'])->where('id', '!=', $flow->id)->first();
            if ($existingFlowWithTitle) {
                return response()->json([
                    'success' => false,
                    'errors' => ['title' => 'A flow with this title already exists. Please use a unique title.']
                ], 422);
            }
        }

        // Update basic flow information
        $updateData = [
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'menu_text' => $validated['menu_text'] ?? '',
            'description' => $validated['description'] ?? '',
            'flow_type' => $validated['flow_type'],
            'dynamic_config' => $validated['dynamic_config'] ?? null,
        ];
        
        $flow->update($updateData);

        // Handle options if provided (only for static flows)
        if (isset($validated['options']) && $flow->flow_type === 'static') {
            // Validate that all next_flow_id values belong to the same USSD (except special values)
            foreach ($validated['options'] as $optionData) {
                if (isset($optionData['next_flow_id']) && $optionData['next_flow_id'] && $optionData['next_flow_id'] !== 'end_session') {
                    $nextFlow = $ussd->flows()->find($optionData['next_flow_id']);
                    if (!$nextFlow) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['options' => 'One or more selected flows do not belong to this USSD.']
                        ], 422);
                    }
                }
            }
            
            // Delete existing options
            $flow->options()->delete();
            
                         // Create new options
             foreach ($validated['options'] as $index => $optionData) {
                 // Handle special "end_session" case
                 $nextFlowId = null;
                 $actionData = $optionData['action_data'] ?? [];
                 
                 // Ensure action_data is an array (handle null, object, etc.)
                 if (!is_array($actionData)) {
                     $actionData = is_object($actionData) ? (array) $actionData : [];
                 }
                 
                 if ($optionData['next_flow_id'] === 'end_session') {
                     // Store end_session flag in action_data instead of next_flow_id
                     $actionData['end_session_after_input'] = true;
                 } else {
                     $nextFlowId = $optionData['next_flow_id'] ?? null;
                 }
                 
                 $flow->options()->create([
                     'option_text' => $optionData['option_text'],
                     'option_value' => $optionData['option_value'],
                     'action_type' => $optionData['action_type'],
                     'action_data' => $actionData,
                     'next_flow_id' => $nextFlowId,
                     'requires_input' => false,
                     'sort_order' => $index + 1,
                     'is_active' => true,
                 ]);
             }
            
            // Auto-generate menu_text from options
            $flow->updateMenuTextFromOptions();
        }

        // Reload the flow with options
        $flow->load('options');

        return response()->json([
            'success' => true,
            'flow' => $flow,
            'message' => 'Flow updated successfully!'
        ]);
    }

    /**
     * Delete a flow for the USSD.
     */
    public function destroyFlow(USSD $ussd, USSDFlow $flow)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure the flow belongs to the USSD
        if ($flow->ussd_id !== $ussd->id) {
            abort(403);
        }

        // Don't allow deletion of root flow
        if ($flow->is_root) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the root flow!'
            ], 400);
        }

        $flow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Flow deleted successfully!'
        ]);
    }

    /**
     * Store a new option for a flow.
     */
    public function storeFlowOption(Request $request, USSD $ussd, USSDFlow $flow)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure the flow belongs to the USSD
        if ($flow->ussd_id !== $ussd->id) {
            abort(403);
        }

        $validated = $request->validate([
            'option_text' => 'nullable|string|max:255',
            'option_value' => 'required|string|max:50|min:1',
            'action_type' => 'required|in:navigate,message,end_session,input_text,input_number,input_phone,input_account,input_pin,input_amount,input_selection',
            'action_data' => 'nullable|array',
            'action_data.message' => 'required_if:action_type,message|string|max:500',
            'action_data.prompt' => 'nullable|string|max:200',
            'action_data.error_message' => 'nullable|string|max:200',
            'action_data.success_message' => 'nullable|string|max:500',
            'next_flow_id' => 'nullable',
        ], [
            'option_text.max' => 'Option text cannot exceed 255 characters.',
            'option_value.required' => 'Option value is required.',
            'option_value.min' => 'Option value must be at least 1 character.',
            'option_value.max' => 'Option value cannot exceed 50 characters.',
            'action_type.required' => 'Action type is required.',
            'action_type.in' => 'Invalid action type.',
            'action_data.message.required_if' => 'Message is required when action type is message.',
            'action_data.message.max' => 'Message cannot exceed 500 characters.',
            'action_data.prompt.max' => 'Prompt cannot exceed 200 characters.',
            'action_data.error_message.max' => 'Error message cannot exceed 200 characters.',
            'next_flow_id.exists' => 'Selected flow does not exist.',
        ]);

    
        $isInputAction = str_starts_with($validated['action_type'], 'input_') || $validated['action_type'] === 'input_collection';
        if (!$isInputAction && empty($validated['option_text'])) {
            return response()->json([
                'success' => false,
                'errors' => ['option_text' => ['Option text is required for this action type.']]
            ], 422);
        }
        
        // Auto-generate option_text for input actions if empty
        if ($isInputAction && empty($validated['option_text'])) {
            $defaultTexts = [
                'input_phone' => 'Enter Phone Number',
                'input_text' => 'Enter Text',
                'input_number' => 'Enter Number',
                'input_account' => 'Enter Account Number',
                'input_pin' => 'Enter PIN',
                'input_amount' => 'Enter Amount',
                'input_selection' => 'Make Selection',
            ];
            $validated['option_text'] = $defaultTexts[$validated['action_type']] ?? 'Continue';
        }

        // Validate that next_flow_id belongs to the same USSD if provided (except special values)
        if ($validated['next_flow_id'] && $validated['next_flow_id'] !== 'end_session') {
            $nextFlow = $ussd->flows()->find($validated['next_flow_id']);
            if (!$nextFlow) {
                return response()->json([
                    'success' => false,
                    'errors' => ['next_flow_id' => 'Selected flow does not belong to this USSD.']
                ], 422);
            }
        }

        // Check for duplicate option values within the same flow
        $existingOption = $flow->options()->where('option_value', $validated['option_value'])->first();
        if ($existingOption) {
            return response()->json([
                'success' => false,
                'errors' => ['option_value' => 'An option with this value already exists in this flow.']
            ], 422);
        }

        $option = $flow->options()->create([
            'option_text' => $validated['option_text'],
            'option_value' => $validated['option_value'],
            'action_type' => $validated['action_type'],
            'action_data' => $validated['action_data'] ?? null,
            'next_flow_id' => $validated['next_flow_id'] ?? null,
            'requires_input' => false,
            'sort_order' => $flow->options()->count(),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'option' => $option,
            'message' => 'Option created successfully!'
        ]);
    }

    /**
     * Update an option for a flow.
     */
    public function updateFlowOption(Request $request, USSD $ussd, USSDFlow $flow, USSDFlowOption $option)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure the flow belongs to the USSD
        if ($flow->ussd_id !== $ussd->id) {
            abort(403);
        }

        // Ensure the option belongs to the flow
        if ($option->flow_id !== $flow->id) {
            abort(403);
        }

        $validated = $request->validate([
            'option_text' => 'required|string|max:255|min:1',
            'option_value' => 'required|string|max:50|min:1',
            'action_type' => 'required|in:navigate,message,end_session,input_text,input_number,input_phone,input_account,input_pin,input_amount,input_selection',
            'action_data' => 'nullable|array',
            'action_data.message' => 'required_if:action_type,message|string|max:500',
            'action_data.prompt' => 'nullable|string|max:200',
            'action_data.error_message' => 'nullable|string|max:200',
            'action_data.success_message' => 'nullable|string|max:500',
            'next_flow_id' => 'nullable',
        ], [
            'option_text.required' => 'Option text is required.',
            'option_text.min' => 'Option text must be at least 1 character.',
            'option_text.max' => 'Option text cannot exceed 255 characters.',
            'option_value.required' => 'Option value is required.',
            'option_value.min' => 'Option value must be at least 1 character.',
            'option_value.max' => 'Option value cannot exceed 50 characters.',
            'action_type.required' => 'Action type is required.',
            'action_type.in' => 'Invalid action type.',
            'action_data.message.required_if' => 'Message is required when action type is message.',
            'action_data.message.max' => 'Message cannot exceed 500 characters.',
            'action_data.prompt.required_if' => 'Prompt is required for input types.',
            'action_data.prompt.max' => 'Prompt cannot exceed 200 characters.',
            'action_data.error_message.required_if' => 'Error message is required for input types.',
            'action_data.error_message.max' => 'Error message cannot exceed 200 characters.',
            'next_flow_id.exists' => 'Selected flow does not exist.',
        ]);

        // Validate that next_flow_id belongs to the same USSD if provided (except special values)
        if ($validated['next_flow_id'] && $validated['next_flow_id'] !== 'end_session') {
            $nextFlow = $ussd->flows()->find($validated['next_flow_id']);
            if (!$nextFlow) {
                return response()->json([
                    'success' => false,
                    'errors' => ['next_flow_id' => 'Selected flow does not belong to this USSD.']
                ], 422);
            }
        }

        // Check for duplicate option values within the same flow (excluding current option)
        $existingOption = $flow->options()->where('option_value', $validated['option_value'])->where('id', '!=', $option->id)->first();
        if ($existingOption) {
            return response()->json([
                'success' => false,
                'errors' => ['option_value' => 'An option with this value already exists in this flow.']
            ], 422);
        }

        $option->update([
            'option_text' => $validated['option_text'],
            'option_value' => $validated['option_value'],
            'action_type' => $validated['action_type'],
            'action_data' => $validated['action_data'] ?? null,
            'next_flow_id' => $validated['next_flow_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'option' => $option,
            'message' => 'Option updated successfully!'
        ]);
    }

    /**
     * Delete an option for a flow.
     */
    public function destroyFlowOption(USSD $ussd, USSDFlow $flow, USSDFlowOption $option)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure the flow belongs to the USSD
        if ($flow->ussd_id !== $ussd->id) {
            abort(403);
        }

        // Ensure the option belongs to the flow
        if ($option->flow_id !== $flow->id) {
            abort(403);
        }

        $option->delete();

        // Update menu_text to reflect the deleted option
        $flow->updateMenuTextFromOptions();

        return response()->json([
            'success' => true,
            'flow' => $flow->load('options'),
            'message' => 'Option deleted successfully!'
        ]);
    }

    /**
     * Switch USSD to live production mode
     */
    public function goLive(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $environmentService = new EnvironmentManagementService();
        $result = $environmentService->switchToProduction($ussd);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Switch USSD back to testing mode
     */
    public function goTesting(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $environmentService = new EnvironmentManagementService();
        $result = $environmentService->switchToTesting($ussd);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get production status for USSD
     */
    public function getProductionStatus(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $environmentService = new EnvironmentManagementService();
        $status = $environmentService->getEnvironmentStatus($ussd);

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Show environment overview page
     */
    public function environmentOverview()
    {
        $ussds = Auth::user()->ussds()->with(['business'])->latest()->get();
        
        $environmentService = new EnvironmentManagementService();
        $ussdsWithStatus = [];
        
        foreach ($ussds as $ussd) {
            $ussdsWithStatus[] = [
                'ussd' => $ussd,
                'environmentStatus' => $environmentService->getEnvironmentStatus($ussd)
            ];
        }

        return Inertia::render('Environment/Overview', [
            'ussdsWithStatus' => $ussdsWithStatus
        ]);
    }

    /**
     * Show environment management page
     */
    public function environment(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $environmentService = new EnvironmentManagementService();
        $environmentStatus = $environmentService->getEnvironmentStatus($ussd);

        // Load relationships and ensure gateway_credentials are accessible
        // Laravel's encrypted:array cast automatically decrypts when accessing the attribute
        $ussd->load(['business']);
        
        // Access gateway_credentials to trigger decryption (encrypted cast handles this)
        // This ensures the decrypted value is available when Inertia serializes the model
        $gatewayCredentials = $ussd->gateway_credentials;
        
        return Inertia::render('USSD/Environment', [
            'ussd' => $ussd,
            'environmentStatus' => $environmentStatus
        ]);
    }

    /**
     * Show production management page
     */
    public function production(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('USSD/Production', [
            'ussd' => $ussd->load(['business'])
        ]);
    }

    /**
     * Configure gateway for USSD
     */
    public function configureGateway(Request $request, USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'gateway_provider' => 'required|string|in:africastalking,hubtel,twilio',
            'api_key' => 'required|string|min:10',
            'username' => 'required|string|min:3',
            'pattern' => 'required|string|max:50|unique:ussds,pattern,' . $ussd->id . ',id',
        ], [
            'pattern.unique' => 'This USSD code is already in use by another service. Each service must have a unique code.',
        ]);

        try {
            // Encrypt and store credentials
            $credentials = [
                'api_key' => $validated['api_key'],
                'username' => $validated['username'],
            ];

            // Update the model attributes
            $ussd->gateway_provider = $validated['gateway_provider'];
            
            // Set credentials - the mutator and encrypted:array cast will handle encryption
            $ussd->gateway_credentials = $credentials;
            
            // Log before save to debug
            Log::debug('Before save - credentials being set', [
                'ussd_id' => $ussd->id,
                'credentials' => $credentials,
                'credentials_type' => gettype($credentials),
                'is_array' => is_array($credentials)
            ]);
            
            // Save the model
            $saved = $ussd->save();
            
            // Log after save to verify
            Log::debug('After save - checking raw value', [
                'ussd_id' => $ussd->id,
                'raw_value_exists' => !empty($ussd->getRawOriginal('gateway_credentials')),
                'raw_value_length' => strlen($ussd->getRawOriginal('gateway_credentials') ?? ''),
            ]);
            
            if (!$saved) {
                throw new \Exception('Failed to save gateway configuration to database');
            }

            Log::info('Gateway configured', [
                'ussd_id' => $ussd->id,
                'gateway_provider' => $validated['gateway_provider'],
                'user_id' => Auth::id(),
                'has_credentials' => !empty($ussd->gateway_credentials)
            ]);

            // Refresh the USSD model to get updated data from database
            $ussd->refresh();
            
            // Verify the credentials were saved correctly
            $savedCredentials = $ussd->gateway_credentials;
            $verification = [
                'has_raw_value' => !empty($ussd->getRawOriginal('gateway_credentials') ?? null),
                'is_array' => is_array($savedCredentials),
                'has_api_key' => isset($savedCredentials['api_key']) && !empty($savedCredentials['api_key']),
                'has_username' => isset($savedCredentials['username']) && !empty($savedCredentials['username']),
            ];
            
            Log::info('Gateway configuration saved and verified', [
                'ussd_id' => $ussd->id,
                'verification' => $verification
            ]);
           
            return response()->json([
                'success' => true,
                'message' => 'Gateway configured successfully!',
                'ussd' => [
                    'id' => $ussd->id,
                    'gateway_provider' => $ussd->gateway_provider,
                    'gateway_credentials' => $savedCredentials, // Will be decrypted automatically
                    'pattern' => $ussd->pattern,
                    'webhook_url' => $ussd->webhook_url,
                    'callback_url' => $ussd->callback_url,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to configure gateway', [
                'ussd_id' => $ussd->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to configure gateway: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configure webhook URL for USSD
     */
    public function configureWebhook(Request $request, USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'webhook_url' => 'required|url|max:500',
            'callback_url' => 'nullable|url|max:500',
        ]);

        try {
            $ussd->update([
                'webhook_url' => $validated['webhook_url'],
                'callback_url' => $validated['callback_url'] ?? $validated['webhook_url'],
            ]);

            Log::info('Webhook configured', [
                'ussd_id' => $ussd->id,
                'webhook_url' => $validated['webhook_url'],
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook URL configured successfully!',
                'ussd' => $ussd->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to configure webhook', [
                'ussd_id' => $ussd->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to configure webhook: ' . $e->getMessage()
            ], 500);
        }
    }
}
