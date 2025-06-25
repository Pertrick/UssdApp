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

        return Inertia::render('USSD/Show', [
            'ussd' => $ussd->load('business')
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

        return Inertia::render('USSD/Simulator', [
            'ussd' => $ussd->load('business')
        ]);
    }

    /**
     * Store a new flow for the USSD.
     */
    public function storeFlow(Request $request, USSD $ussd)
    {
        // Debug logging
        \Log::info('storeFlow method called', [
            'ussd_id' => $ussd->id,
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);

        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== Auth::id()) {
            \Log::warning('Unauthorized access attempt', [
                'ussd_user_id' => $ussd->user_id,
                'auth_user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'errors' => ['general' => 'Unauthorized access']
            ], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'menu_text' => 'required|string|max:1000|min:5',
                'description' => 'nullable|string|max:500',
            ], [
                'name.required' => 'Flow name is required.',
                'name.min' => 'Flow name must be at least 2 characters.',
                'name.max' => 'Flow name cannot exceed 255 characters.',
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

            $flow = $ussd->flows()->create([
                'name' => $validated['name'],
                'menu_text' => $validated['menu_text'],
                'description' => $validated['description'] ?? '',
                'is_root' => false,
                'is_active' => true,
                'sort_order' => $ussd->flows()->count(),
            ]);

            \Log::info('Flow created successfully', ['flow_id' => $flow->id]);

            return response()->json([
                'success' => true,
                'flow' => $flow->load('options'),
                'message' => 'Flow created successfully!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation failed in storeFlow', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error in storeFlow method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['general' => 'An error occurred while creating the flow: ' . $e->getMessage()]
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
            'menu_text' => 'required|string|max:1000|min:5',
            'description' => 'nullable|string|max:500',
            'options' => 'array',
            'options.*.option_text' => 'required|string|max:255|min:1',
            'options.*.option_value' => 'required|string|max:50|min:1',
            'options.*.action_type' => 'required|in:navigate,message,end_session',
            'options.*.action_data' => 'nullable|array',
            'options.*.action_data.message' => 'required_if:options.*.action_type,message|string|max:500',
            'options.*.next_flow_id' => 'nullable|exists:ussd_flows,id',
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

        $flow->update([
            'name' => $validated['name'],
            'menu_text' => $validated['menu_text'],
            'description' => $validated['description'] ?? '',
        ]);

        // Handle options
        if (isset($validated['options'])) {
            // Validate that all next_flow_id values belong to the same USSD
            foreach ($validated['options'] as $optionData) {
                if (isset($optionData['next_flow_id']) && $optionData['next_flow_id']) {
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
            foreach ($validated['options'] as $optionData) {
                $flow->options()->create([
                    'option_text' => $optionData['option_text'],
                    'option_value' => $optionData['option_value'],
                    'action_type' => $optionData['action_type'],
                    'action_data' => $optionData['action_data'] ?? null,
                    'next_flow_id' => $optionData['next_flow_id'] ?? null,
                    'requires_input' => false,
                    'sort_order' => 0,
                    'is_active' => true,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'flow' => $flow->load('options'),
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
            'action_type' => 'required|in:navigate,message,end_session',
            'action_data' => 'nullable|array',
            'action_data.message' => 'required_if:action_type,message|string|max:500',
            'next_flow_id' => 'nullable|exists:ussd_flows,id',
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
            'next_flow_id.exists' => 'Selected flow does not exist.',
        ]);

        // Validate that next_flow_id belongs to the same USSD if provided
        if ($validated['next_flow_id']) {
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
            'action_type' => 'required|in:navigate,message,end_session',
            'action_data' => 'nullable|array',
            'action_data.message' => 'required_if:action_type,message|string|max:500',
            'next_flow_id' => 'nullable|exists:ussd_flows,id',
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
            'next_flow_id.exists' => 'Selected flow does not exist.',
        ]);

        // Validate that next_flow_id belongs to the same USSD if provided
        if ($validated['next_flow_id']) {
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

        return response()->json([
            'success' => true,
            'message' => 'Option deleted successfully!'
        ]);
    }
}
