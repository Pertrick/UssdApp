<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use App\Models\Business;
use App\Http\Requests\USSD\StoreUSSDRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class USSDController extends Controller
{
    /**
     * Display a listing of USSDs for the authenticated user.
     */
    public function index()
    {
        $ussds = auth()->user()->ussds()->with('business')->latest()->get();
        
        return Inertia::render('USSD/Index', [
            'ussds' => $ussds
        ]);
    }

    /**
     * Show the form for creating a new USSD.
     */
    public function create()
    {
        $businesses = auth()->user()->businesses()->get();
        
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
        $business = auth()->user()->primaryBusiness;
        
        $ussd = USSD::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'pattern' => $validated['pattern'],
            'user_id' => auth()->id(),
            'business_id' => $business->id,
            'is_active' => true,
        ]);

        return redirect()->route('ussd.index')
            ->with('success', 'USSD created successfully!');
    }

    /**
     * Display the specified USSD.
     */
    public function show(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== auth()->id()) {
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
        if ($ussd->user_id !== auth()->id()) {
            abort(403);
        }

        $businesses = auth()->user()->businesses()->get();
        
        return Inertia::render('USSD/Edit', [
            'ussd' => $ussd->load('business'),
            'businesses' => $businesses
        ]);
    }

    /**
     * Update the specified USSD in storage.
     */
    public function update(StoreUSSDRequest $request, USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validated();
        
        // Ensure the business belongs to the authenticated user
        $business = auth()->user()->businesses()->findOrFail($validated['business_id']);
        
        $ussd->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'pattern' => $validated['pattern'],
            'business_id' => $business->id,
        ]);

        return redirect()->route('ussd.index')
            ->with('success', 'USSD updated successfully!');
    }

    /**
     * Remove the specified USSD from storage.
     */
    public function destroy(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== auth()->id()) {
            abort(403);
        }

        $ussd->delete();

        return redirect()->route('ussd.index')
            ->with('success', 'USSD deleted successfully!');
    }

    /**
     * Toggle the active status of a USSD.
     */
    public function toggleStatus(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== auth()->id()) {
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
        if ($ussd->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('USSD/Configure', [
            'ussd' => $ussd->load('business')
        ]);
    }

    /**
     * Show the USSD simulator.
     */
    public function simulator(USSD $ussd)
    {
        // Ensure the USSD belongs to the authenticated user
        if ($ussd->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('USSD/Simulator', [
            'ussd' => $ussd->load('business')
        ]);
    }
}
