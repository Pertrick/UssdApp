<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Enums\EnvironmentType;
use App\Models\Business;
use App\Models\Environment;
use App\Models\USSDSession;
use App\Enums\BillingMethod;
use Illuminate\Http\Request;
use App\Services\BillingService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BillingChangeRequest;
use Illuminate\Support\Facades\Auth;

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
        
        $billingFilter = $request->get('billing_filter');
        
        // Normalize legacy values
        if ($billingFilter === 'simulated' || $billingFilter === 'live') {
            $billingFilter = $billingFilter === 'simulated' ? 'testing' : 'production';
        }
        
        // If no filter specified, determine default based on actual billing data
        if (!$billingFilter || $billingFilter === 'all') {
            $productionCount = USSDSession::whereHas('ussd', function($query) use ($business) {
                $query->where('business_id', $business->id);
            })
            ->where('is_billed', true)
            ->whereIn('billing_status', ['charged', 'invoiced'])
            ->count();
            
            $testingCount = USSDSession::whereHas('ussd', function($query) use ($business) {
                $query->where('business_id', $business->id);
            })
            ->where('is_billed', true)
            ->where('billing_status', 'testing')
            ->count();
            
            $billingFilter = $productionCount >= $testingCount ? 'production' : 'testing';
        }
        
        $ussdEnvironment = $billingFilter === 'production' ? EnvironmentType::PRODUCTION->value : EnvironmentType::TESTING->value;
        
        // Get real-time billing stats
        $billingStats = $this->billingService->getRealTimeStats($business);

        // Get recent sessions with optional filtering
        $sessionsQuery = USSDSession::whereHas('ussd', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })
        ->where('is_billed', true)
        ->with(['ussd', 'environment']);

        if ($billingFilter === 'production') {
            $sessionsQuery->whereIn('billing_status', ['charged', 'invoiced']);
        } elseif ($billingFilter === 'testing') {
            $sessionsQuery->where('billing_status', 'testing');
        }

        // Apply period filter using proper datetime ranges to avoid timezone issues
        if ($period === 'today') {
            $sessionsQuery->whereBetween('billed_at', [
                now()->startOfDay(),
                now()->endOfDay()
            ]);
        } elseif ($period === 'last_month') {
            $sessionsQuery->whereBetween('billed_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ]);
        } elseif ($period === 'month') {
            $sessionsQuery->whereBetween('billed_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ]);
        } elseif ($period === 'year') {
            $sessionsQuery->whereBetween('billed_at', [
                now()->startOfYear(),
                now()->endOfYear()
            ]);
        }
                
        if (in_array($period, ['today', 'last_month', 'month', 'year'])) {
            $sessionsQuery->whereNotNull('billed_at');
        }

        // Clone query for SQL logging before executing
        $queryClone = clone $sessionsQuery;
        $sql = $queryClone->toSql();
        $bindings = $queryClone->getBindings();

        // Get pagination page from request, default to 1
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $recentSessions = $sessionsQuery
        ->orderBy('billed_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page)
        ->withQueryString();
        
        $allStatuses = USSDSession::whereHas('ussd', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })
        ->where('is_billed', true)
        ->pluck('billing_status')
        ->unique()
        ->toArray();
        
        $statusCounts = USSDSession::whereHas('ussd', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })
        ->where('is_billed', true)
        ->selectRaw('billing_status, COUNT(*) as count')
        ->groupBy('billing_status')
        ->pluck('count', 'billing_status')
        ->toArray();
        
        Log::info('Billing Dashboard Query Results', [
            'filter' => $billingFilter,
            'period' => $period,
            'page' => $page,
            'per_page' => $perPage,
            'total_sessions' => $recentSessions->total(),
            'current_page_count' => $recentSessions->count(),
            'all_available_statuses' => $allStatuses,
            'status_counts' => $statusCounts,
            'returned_billing_statuses' => $recentSessions->pluck('billing_status')->unique()->toArray(),
            'sample_environment_ids' => $recentSessions->pluck('environment_id')->unique()->toArray(),
            'sample_billed_at_dates' => $recentSessions->take(5)->pluck('billed_at')->toArray(),
            'sql_query' => $sql,
            'sql_bindings' => $bindings,
        ]);

        // Get available payment gateways
        $paymentService = new PaymentService();
        $availableGateways = $paymentService->getAvailableGateways();


        return Inertia::render('Billing/Dashboard', [
            'billingStats' => $billingStats,
            'recentSessions' => $recentSessions,
            'sessionPrice' => $business->session_price ?? 0.02,
            'availableGateways' => $availableGateways,
            'billingFilter' => $billingFilter,
            'period' => $period,
            'ussdEnvironment' => $ussdEnvironment,
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
        
        // Normalize legacy values
        if ($billingFilter === 'simulated' || $billingFilter === 'live') {
            $billingFilter = $billingFilter === 'simulated' ? 'testing' : 'production';
        }
        
        $period = $request->get('period', 'month');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $sessionsQuery = USSDSession::whereHas('ussd', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })
        ->where('is_billed', true)
        ->with('ussd');

        // Apply billing status filter
        if ($billingFilter === 'production') {
            $sessionsQuery->whereIn('billing_status', ['charged', 'invoiced']);
        } elseif ($billingFilter === 'testing') {
            $sessionsQuery->where('billing_status', 'testing');
        }

        // Ensure billed_at is not null for period filters
        if (in_array($period, ['today', 'last_month', 'month', 'year'])) {
            $sessionsQuery->whereNotNull('billed_at');
        }

        // Apply period filter using proper datetime ranges to avoid timezone issues
        switch ($period) {
            case 'today':
                $sessionsQuery->whereBetween('billed_at', [
                    now()->startOfDay(),
                    now()->endOfDay()
                ]);
                break;
            case 'last_month':
                $sessionsQuery->whereBetween('billed_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]);
                break;
            case 'month':
                $sessionsQuery->whereBetween('billed_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
            case 'year':
                $sessionsQuery->whereBetween('billed_at', [
                    now()->startOfYear(),
                    now()->endOfYear()
                ]);
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
