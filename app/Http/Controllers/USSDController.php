<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use App\Models\Business;
use App\Http\Requests\USSD\StoreUSSDRequest;
use App\Http\Requests\USSD\UpdateUSSDRequest;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\USSDFlow;
use App\Models\USSDFlowOption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class USSDController extends Controller
{
    /**
     * Display a listing of USSDs for the authenticated user.
     */
    public function index()
    {
        $ussds = Auth::user()->ussds()->with('business')->latest()->get();
        
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
        
        $ussd = USSD::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'pattern' => $validated['pattern'],
            'user_id' => Auth::id(),
            'business_id' => $business->id,
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
            'ussd' => $ussd->load(['business', 'flows.options'])
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

        return Inertia::render('USSD/Configure', [
            'ussd' => $ussd->load(['flows' => function($query) {
                $query->with('options')->orderBy('sort_order');
            }])
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
                'menu_text' => 'required|string|max:1000|min:5',
                'description' => 'nullable|string|max:500',
            ], [
                'name.required' => 'Flow name is required.',
                'name.min' => 'Flow name must be at least 2 characters.',
                'name.max' => 'Flow name cannot exceed 255 characters.',
                'title.max' => 'Title cannot exceed 255 characters.',
                'menu_text.required' => 'Menu text is required.',
                'menu_text.min' => 'Menu text must be at least 5 characters.',
                'menu_text.max' => 'Menu text cannot exceed 1000 characters.',
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

            // Create the flow
            $flow = $ussd->flows()->create([
                'name' => $validated['name'],
                'title' => $validated['title'] ?? null,
                'menu_text' => $validated['menu_text'],
                'description' => $validated['description'] ?? '',
                'is_root' => false,
                'sort_order' => $ussd->flows()->count(),
                'is_active' => true,
            ]);

            // Parse menu_text to create initial options
            $flow->parseMenuTextToOptions();

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
            'menu_text' => 'required|string|max:1000|min:5',
            'description' => 'nullable|string|max:500',
            'options' => 'array',
            'options.*.option_text' => 'required|string|max:255|min:1',
            'options.*.option_value' => 'required|string|max:50|min:1',
            'options.*.action_type' => 'required|in:navigate,message,end_session,input_text,input_number,input_phone,input_account,input_pin,input_amount,input_selection',
            'options.*.action_data' => 'nullable|array',
            'options.*.action_data.message' => 'required_if:options.*.action_type,message|string|max:500',
            'options.*.action_data.prompt' => 'nullable|string|max:200',
            'options.*.action_data.error_message' => 'nullable|string|max:200',
            'options.*.action_data.success_message' => 'nullable|string|max:500',
            'options.*.next_flow_id' => 'nullable',
        ], [
            'name.required' => 'Flow name is required.',
            'name.min' => 'Flow name must be at least 2 characters.',
            'name.max' => 'Flow name cannot exceed 255 characters.',
            'menu_text.required' => 'Menu text is required.',
            'menu_text.min' => 'Menu text must be at least 5 characters.',
            'menu_text.max' => 'Menu text cannot exceed 1000 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'options.*.option_text.required' => 'Option text is required.',
            'options.*.option_text.min' => 'Option text must be at least 1 character.',
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

        // Check for duplicate flow names within the same USSD (excluding current flow)
        $existingFlow = $ussd->flows()->where('name', $validated['name'])->where('id', '!=', $flow->id)->first();
        if ($existingFlow) {
            return response()->json([
                'success' => false,
                'errors' => ['name' => 'A flow with this name already exists.']
            ], 422);
        }

        // Update basic flow information
        $flow->update([
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'menu_text' => $validated['menu_text'],
            'description' => $validated['description'] ?? '',
        ]);

        // Handle options if provided
        if (isset($validated['options'])) {
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
            
            // Update menu_text to match the new options
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
            'action_data.prompt.max' => 'Prompt cannot exceed 200 characters.',
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
}
