<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use App\Models\Business;
use App\Models\USSDSession;
use App\Models\BillingChangeRequest;
use App\Enums\BillingMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Show billing dashboard
     */
    public function billingDashboard(Request $request)
    {
        $business = Auth::user()->primaryBusiness;
        $period = $request->get('period', 'month');
        $billingFilter = $request->get('billing_filter', 'all'); // all, live, simulated

        // Get real-time billing stats
        $billingStats = $this->billingService->getRealTimeStats($business);

        // Get recent sessions with optional filtering
        $sessionsQuery = USSDSession::whereHas('ussd', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })
        ->where('is_billed', true)
        ->with('ussd');

        // Apply billing status filter
        if ($billingFilter === 'live') {
            $sessionsQuery->where('billing_status', 'charged');
        } elseif ($billingFilter === 'simulated') {
            $sessionsQuery->where('billing_status', 'simulated');
        }

        $recentSessions = $sessionsQuery
        ->orderBy('billed_at', 'desc')
        ->limit(50)
        ->get();

        // Get available payment gateways
        $paymentService = new \App\Services\PaymentService();
        $availableGateways = $paymentService->getAvailableGateways();

        return Inertia::render('Billing/Dashboard', [
            'billingStats' => $billingStats,
            'recentSessions' => $recentSessions,
            'sessionPrice' => $business->session_price ?? 0.02,
            'availableGateways' => $availableGateways,
            'billingFilter' => $billingFilter,
            'testBalance' => $business->test_balance ?? 0, // Add test balance for partitioning
            'currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
            'currencySymbol' => config('app.currency_symbol', '₦'),
        ]);
    }

    /**
     * Get filtered session data for AJAX requests
     */
    public function getFilteredSessions(Request $request)
    {
        $business = Auth::user()->primaryBusiness;
        $billingFilter = $request->get('billing_filter', 'all');
        $period = $request->get('period', 'month');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $sessionsQuery = USSDSession::whereHas('ussd', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })
        ->where('is_billed', true)
        ->with('ussd');

        // Apply billing status filter
        if ($billingFilter === 'live') {
            $sessionsQuery->where('billing_status', 'charged');
        } elseif ($billingFilter === 'simulated') {
            $sessionsQuery->where('billing_status', 'simulated');
        }

        // Apply period filter
        switch ($period) {
            case 'today':
                $sessionsQuery->whereDate('billed_at', today());
                break;
            case 'week':
                $sessionsQuery->whereBetween('billed_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $sessionsQuery->whereMonth('billed_at', now()->month);
                break;
            case 'year':
                $sessionsQuery->whereYear('billed_at', now()->year);
                break;
        }

        $sessions = $sessionsQuery
        ->orderBy('billed_at', 'desc')
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Add funds to business account
     */
    public function addFunds(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000'
        ]);

        $business = Auth::user()->primaryBusiness;
        $amount = $request->amount;

        $success = $this->billingService->addFunds($business, $amount, 'manual');

        if ($success) {
            return redirect()->route('billing.dashboard')
                ->with('success', "Successfully added \${$amount} to your account balance.");
        } else {
            return redirect()->route('billing.dashboard')
                ->with('error', 'Failed to add funds. Please try again.');
        }
    }

    /**
     * Add test funds to business account
     */
    public function addTestFunds(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000'
        ]);

        $business = Auth::user()->primaryBusiness;
        $amount = $request->amount;

        $success = $this->billingService->addTestFunds($business, $amount);

        if ($success) {
            return redirect()->route('billing.dashboard')
                ->with('success', "Successfully added \${$amount} to your test balance.");
        } else {
            return redirect()->route('billing.dashboard')
                ->with('error', 'Failed to add test funds. Please try again.');
        }
    }

    /**
     * Get billing summary for API
     */
    public function getSummary(Request $request)
    {
        $business = Auth::user()->primaryBusiness;
        $period = $request->get('period', 'month');

        $summary = $this->billingService->getBillingSummary($business, $period);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get real-time stats for API
     */
    public function getStats()
    {
        $business = Auth::user()->primaryBusiness;
        $stats = $this->billingService->getRealTimeStats($business);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get session history
     */
    public function getSessionHistory(Request $request)
    {
        $business = Auth::user()->primaryBusiness;
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $sessions = USSDSession::whereHas('ussd', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })
        ->where('is_billed', true)
        ->with('ussd')
        ->orderBy('billed_at', 'desc')
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Export billing data
     */
    public function export(Request $request)
    {
        $business = Auth::user()->primaryBusiness;
        $period = $request->get('period', 'month');

        $sessions = USSDSession::whereHas('ussd', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })
        ->where('is_billed', true)
        ->with('ussd')
        ->orderBy('billed_at', 'desc');

        // Filter by period
        switch ($period) {
            case 'today':
                $sessions->whereDate('billed_at', today());
                break;
            case 'week':
                $sessions->whereBetween('billed_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $sessions->whereMonth('billed_at', now()->month);
                break;
            case 'year':
                $sessions->whereYear('billed_at', now()->year);
                break;
        }

        $sessions = $sessions->get();

        // Generate CSV
        $filename = "billing_export_{$period}_" . now()->format('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($sessions) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Session ID',
                'Phone Number',
                'USSD Service',
                'Amount',
                'Status',
                'Date',
                'Invoice ID'
            ]);

            // CSV data
            foreach ($sessions as $session) {
                fputcsv($file, [
                    $session->session_id,
                    $session->phone_number,
                    $session->ussd->name,
                    $session->billing_amount,
                    $session->billing_status,
                    $session->billed_at->format('Y-m-d H:i:s'),
                    $session->invoice_id
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Request billing method change
     */
    public function requestBillingMethodChange(Request $request)
    {
        $request->validate([
            'requested_method' => 'required|in:prepaid,postpaid',
            'reason' => 'nullable|string|max:500',
        ]);

        $business = Auth::user()->primaryBusiness;

        // Check if there's already a pending request
        $existingRequest = $business->pendingBillingChangeRequest();
        if ($existingRequest) {
            return back()->with('error', 'You already have a pending billing method change request.');
        }

        // Check if requesting the same method
        if ($business->billing_method?->value === $request->requested_method) {
            return back()->with('error', 'You are already on this billing method.');
        }

        try {
            DB::beginTransaction();

            // Create billing change request
            BillingChangeRequest::create([
                'business_id' => $business->id,
                'requested_by' => Auth::id(),
                'current_method' => $business->billing_method?->value ?? 'postpaid',
                'requested_method' => $request->requested_method,
                'reason' => $request->reason,
                'status' => BillingChangeRequest::STATUS_PENDING,
            ]);

            // Update business with request info
            $business->update([
                'billing_change_request' => $request->requested_method,
                'billing_change_reason' => $request->reason,
                'billing_change_requested_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Billing method change request submitted successfully! An admin will review your request.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit request: ' . $e->getMessage());
        }
    }

    /**
     * Cancel billing method change request
     */
    public function cancelBillingMethodChangeRequest()
    {
        $business = Auth::user()->primaryBusiness;
        $pendingRequest = $business->pendingBillingChangeRequest();

        if (!$pendingRequest) {
            return back()->with('error', 'No pending billing method change request found.');
        }

        try {
            DB::beginTransaction();

            $pendingRequest->cancel();

            $business->update([
                'billing_change_request' => null,
                'billing_change_reason' => null,
                'billing_change_requested_at' => null,
            ]);

            DB::commit();

            return back()->with('success', 'Billing method change request cancelled successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to cancel request: ' . $e->getMessage());
        }
    }

    /**
     * Get billing method change request status
     */
    public function getBillingMethodChangeRequestStatus()
    {
        $business = Auth::user()->primaryBusiness;
        $pendingRequest = $business->pendingBillingChangeRequest();

        if (!$pendingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'No pending request found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pendingRequest->id,
                'current_method' => $pendingRequest->current_method,
                'requested_method' => $pendingRequest->requested_method,
                'reason' => $pendingRequest->reason,
                'status' => $pendingRequest->status,
                'requested_at' => $pendingRequest->created_at,
                'reviewed_at' => $pendingRequest->reviewed_at,
                'admin_notes' => $pendingRequest->admin_notes,
            ],
        ]);
    }
}
