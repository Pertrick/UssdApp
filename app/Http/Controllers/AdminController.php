<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\BillingChangeRequest;
use App\Models\USSDSession;
use App\Models\UssdCost;
use App\Services\GatewayCostService;
use App\Enums\UserRole;
use App\Enums\BusinessRegistrationStatus;
use App\Enums\BillingMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::whereHas('roles', function($query) {
                $query->where('name', UserRole::USER->value);
            })->count(),
            'total_businesses' => Business::count(),
            'pending_approvals' => Business::whereIn('registration_status', [
                BusinessRegistrationStatus::COMPLETED_UNVERIFIED->value,
                BusinessRegistrationStatus::UNDER_REVIEW->value
            ])->count(),
            'verified_businesses' => Business::where('registration_status', BusinessRegistrationStatus::VERIFIED->value)->count(),
            'recent_registrations' => Business::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        // Platform financial metrics (gateway cost tracking)
        $financialStats = $this->getPlatformFinancialStats();

        $recentBusinesses = Business::with('user')
            ->latest()
            ->take(5)
            ->get();

        $pendingBusinesses = Business::with('user')
            ->whereIn('registration_status', [
                BusinessRegistrationStatus::COMPLETED_UNVERIFIED->value,
                BusinessRegistrationStatus::UNDER_REVIEW->value
            ])
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'financialStats' => $financialStats,
            'recentBusinesses' => $recentBusinesses,
            'pendingBusinesses' => $pendingBusinesses,
        ]);
    }

    /**
     * Get platform-wide financial statistics (revenue, gateway costs, profit)
     * Only includes live/production sessions (excludes testing/simulation)
     */
    private function getPlatformFinancialStats(): array
    {
        $currency = config('app.currency', 'NGN');
        $currencySymbol = config('app.currency_symbol', '₦');
        $gatewayCostService = app(GatewayCostService::class);

        // Get production/live environment IDs
        $productionEnvironmentIds = \App\Models\Environment::whereIn('name', ['production', 'live'])
            ->pluck('id')
            ->toArray();

        // Today's stats - only production/live
        $todaySessions = USSDSession::where('is_billed', true)
            ->where('billing_status', 'charged') // Only successfully charged sessions
            ->whereIn('environment_id', $productionEnvironmentIds) // Only production/live environments
            ->whereDate('billed_at', today())
            ->get();

        // Revenue is already in main currency (decimal)
        $todayRevenue = (float) $todaySessions->sum('billing_amount');
        
        // Gateway costs are stored in smallest unit (integer), need to convert
        $todayGatewayCostsInSmallestUnit = (int) ($todaySessions->sum('gateway_cost') ?? 0);
        $todayGatewayCosts = $gatewayCostService->convertFromSmallestUnit($todayGatewayCostsInSmallestUnit, $currency);
        
        $todayProfit = $todayRevenue - $todayGatewayCosts;
        $todayMargin = $todayRevenue > 0 ? ($todayProfit / $todayRevenue) * 100 : 0;

        // This month's stats - only production/live
        $thisMonthSessions = USSDSession::where('is_billed', true)
            ->where('billing_status', 'charged') // Only successfully charged sessions
            ->whereIn('environment_id', $productionEnvironmentIds) // Only production/live environments
            ->whereMonth('billed_at', now()->month)
            ->whereYear('billed_at', now()->year)
            ->get();

        // Revenue is already in main currency (decimal)
        $monthRevenue = (float) $thisMonthSessions->sum('billing_amount');
        
        // Gateway costs are stored in smallest unit (integer), need to convert
        $monthGatewayCostsInSmallestUnit = (int) ($thisMonthSessions->sum('gateway_cost') ?? 0);
        $monthGatewayCosts = $gatewayCostService->convertFromSmallestUnit($monthGatewayCostsInSmallestUnit, $currency);
        
        $monthProfit = $monthRevenue - $monthGatewayCosts;
        $monthMargin = $monthRevenue > 0 ? ($monthProfit / $monthRevenue) * 100 : 0;

        // All time stats - only production/live
        $allTimeSessions = USSDSession::where('is_billed', true)
            ->where('billing_status', 'charged') // Only successfully charged sessions
            ->whereIn('environment_id', $productionEnvironmentIds) // Only production/live environments
            ->get();

        // Revenue is already in main currency (decimal)
        $allTimeRevenue = (float) $allTimeSessions->sum('billing_amount');
        
        // Gateway costs are stored in smallest unit (integer), need to convert
        $allTimeGatewayCostsInSmallestUnit = (int) ($allTimeSessions->sum('gateway_cost') ?? 0);
        $allTimeGatewayCosts = $gatewayCostService->convertFromSmallestUnit($allTimeGatewayCostsInSmallestUnit, $currency);
        
        $allTimeProfit = $allTimeRevenue - $allTimeGatewayCosts;
        $allTimeMargin = $allTimeRevenue > 0 ? ($allTimeProfit / $allTimeRevenue) * 100 : 0;

        // Debug info: Count sessions with/without gateway costs
        $todayWithCosts = $todaySessions->whereNotNull('gateway_cost')->count();
        $monthWithCosts = $thisMonthSessions->whereNotNull('gateway_cost')->count();
        $allTimeWithCosts = $allTimeSessions->whereNotNull('gateway_cost')->count();

        return [
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'today' => [
                'revenue' => round($todayRevenue, 2),
                'gateway_costs' => round($todayGatewayCosts, 2),
                'profit' => round($todayProfit, 2),
                'margin_percentage' => round($todayMargin, 2),
                'sessions' => $todaySessions->count(),
                'sessions_with_costs' => $todayWithCosts,
            ],
            'this_month' => [
                'revenue' => round($monthRevenue, 2),
                'gateway_costs' => round($monthGatewayCosts, 2),
                'profit' => round($monthProfit, 2),
                'margin_percentage' => round($monthMargin, 2),
                'sessions' => $thisMonthSessions->count(),
                'sessions_with_costs' => $monthWithCosts,
            ],
            'all_time' => [
                'revenue' => round($allTimeRevenue, 2),
                'gateway_costs' => round($allTimeGatewayCosts, 2),
                'profit' => round($allTimeProfit, 2),
                'margin_percentage' => round($allTimeMargin, 2),
                'sessions' => $allTimeSessions->count(),
                'sessions_with_costs' => $allTimeWithCosts,
            ],
        ];
    }

    /**
     * Show all businesses for admin review
     */
    public function businesses(Request $request)
    {
        $query = Business::with('user');

        // Filter by verification status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'pending':
                    $query->where('verified', false);
                    break;
                case 'verified':
                    $query->where('verified', true);
                    break;
                case 'all':
                    // No filter
                    break;
            }
        }

        // Search by business name or user name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('business_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $businesses = $query->latest()->paginate(15);

        return Inertia::render('Admin/Businesses', [
            'businesses' => $businesses,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Show business details for admin review
     */
    public function showBusiness(Business $business)
    {
        $business->load('user');
        
        return Inertia::render('Admin/BusinessDetail', [
            'business' => $business,
        ]);
    }

    /**
     * Start reviewing a business
     */
    public function startReview(Business $business)
    {
        $business->update([
            'registration_status' => BusinessRegistrationStatus::UNDER_REVIEW
        ]);

        return back()->with('success', 'Business review started successfully!');
    }

    /**
     * Approve a business
     */
    public function approveBusiness(Request $request, Business $business)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:500'
        ]);

        $business->update([
            'verified' => true,
            'verified_at' => now(),
            'registration_status' => BusinessRegistrationStatus::VERIFIED,
            'approval_notes' => $request->approval_notes
        ]);

        return back()->with('success', 'Business approved successfully!');
    }

    /**
     * Reject a business
     */
    public function rejectBusiness(Request $request, Business $business)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $business->update([
            'verified' => false,
            'verified_at' => null,
            'registration_status' => BusinessRegistrationStatus::REJECTED,
            'rejection_reason' => $request->rejection_reason
        ]);

        return back()->with('success', 'Business rejected successfully!');
    }

    /**
     * Suspend a business
     */
    public function suspendBusiness(Request $request, Business $business)
    {
        $request->validate([
            'suspension_reason' => 'required|string|max:500'
        ]);

        $business->update([
            'registration_status' => BusinessRegistrationStatus::SUSPENDED,
            'suspension_reason' => $request->suspension_reason
        ]);

        return back()->with('success', 'Business suspended successfully!');
    }

    /**
     * Show all users for admin management
     */
    public function users(Request $request)
    {
        $query = User::with(['businesses', 'roles']);

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::all();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Update user roles
     */
    public function updateUserRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user->roles()->sync($request->roles);

        return back()->with('success', 'User roles updated successfully!');
    }

    /**
     * Toggle user status (enable/disable)
     */
    public function toggleUserStatus(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "User {$status} successfully!");
    }

    /**
     * Show admin settings
     */
    public function settings()
    {
        $roles = Role::all();
        $ussdCosts = UssdCost::where('country', 'NG')
            ->orderBy('network')
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('network')
            ->map(function ($costs) {
                // Get the most recent active cost for each network
                return $costs->where('is_active', true)->sortByDesc('effective_from')->first();
            })
            ->filter()
            ->values();
        
        return Inertia::render('Admin/Settings', [
            'roles' => $roles,
            'ussdCosts' => $ussdCosts,
        ]);
    }
    
    /**
     * Update USSD cost
     */
    public function updateUssdCost(Request $request, UssdCost $ussdCost)
    {
        $request->validate([
            'cost_per_session' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'is_active' => 'boolean',
        ]);
        
        // Convert to smallest unit (kobo for NGN)
        $costInSmallestUnit = (int) round($request->cost_per_session * 100);
        
        $ussdCost->update([
            'cost_per_session' => $costInSmallestUnit,
            'effective_from' => $request->effective_from,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);
        
        return back()->with('success', 'USSD cost updated successfully!');
    }
    
    /**
     * Create new USSD cost
     */
    public function createUssdCost(Request $request)
    {
        $request->validate([
            'network' => 'required|string|max:255',
            'cost_per_session' => 'required|numeric|min:0',
            'country' => 'required|string|size:2',
            'currency' => 'required|string|size:3',
            'effective_from' => 'required|date',
        ]);
        
        // Convert to smallest unit (kobo for NGN)
        $costInSmallestUnit = (int) round($request->cost_per_session * 100);
        
        UssdCost::create([
            'country' => $request->country,
            'network' => $request->network,
            'cost_per_session' => $costInSmallestUnit,
            'currency' => $request->currency,
            'effective_from' => $request->effective_from,
            'is_active' => true,
        ]);
        
        return back()->with('success', 'USSD cost created successfully!');
    }

    /**
     * List invoices for postpaid billing
     */
    public function invoices(Request $request)
    {
        $query = Invoice::with('business')
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(20);

        $businesses = Business::orderBy('business_name')
            ->select('id', 'business_name')
            ->get();

        return Inertia::render('Admin/Invoices', [
            'invoices' => $invoices,
            'filters' => $request->only(['status']),
            'businesses' => $businesses,
        ]);
    }

    /**
     * Generate a billing cycle invoice for a business
     */
    public function generateInvoice(Request $request, \App\Services\InvoiceService $invoiceService)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
        ]);

        $business = Business::findOrFail($request->business_id);

        $invoice = $invoiceService->generateBillingCycleInvoice($business, auth()->id());

        if (!$invoice) {
            return back()->with('info', 'Business is on prepaid billing. No invoice generated.');
        }

        return back()->with('success', "Invoice {$invoice->invoice_number} generated successfully.");
    }

    /**
     * Mark an invoice as paid (and update session billing statuses)
     */
    public function markInvoicePaid(Request $request, Invoice $invoice, \App\Services\InvoiceService $invoiceService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
        ]);

        $invoiceService->recordPayment(
            $invoice,
            $request->amount,
            $request->payment_method,
            $request->reference
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} marked as paid.");
    }

    /**
     * Get business documents for download
     */
    public function downloadDocument(Business $business, $documentType)
    {
        $path = null;
        $filename = null;

        switch ($documentType) {
            case 'cac':
                $path = $business->cac_document_path;
                $filename = "CAC_Document_{$business->business_name}.pdf";
                break;
            case 'director_id':
                $path = $business->director_id_path;
                $filename = "Director_ID_{$business->business_name}.pdf";
                break;
            default:
                abort(404);
        }

        if (!$path || !Storage::exists($path)) {
            abort(404, 'Document not found');
        }

        return Storage::download($path, $filename);
    }

    /**
     * Get admin analytics
     */
    public function analytics()
    {
        $monthlyRegistrations = Business::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $verificationStats = [
            'total' => Business::count(),
            'verified' => Business::where('verified', true)->count(),
            'pending' => Business::where('verified', false)->count(),
            'rejected' => Business::where('registration_status', 'rejected')->count(),
        ];

        $recentActivity = Business::with('user')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Admin/Analytics', [
            'monthlyRegistrations' => $monthlyRegistrations,
            'verificationStats' => $verificationStats,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Show billing change requests
     */
    public function billingChangeRequests(Request $request)
    {
        $query = BillingChangeRequest::with(['business.user', 'requester', 'reviewer'])
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15);

        return Inertia::render('Admin/BillingChangeRequests', [
            'requests' => $requests,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Approve billing change request
     */
    public function approveBillingChangeRequest(Request $request, BillingChangeRequest $billingChangeRequest)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:500',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:1|max:365',
        ]);

        try {
            DB::beginTransaction();

            $business = $billingChangeRequest->business;
            $requestedMethod = $billingChangeRequest->requested_method;

            // Update business billing method
            $updateData = [
                'billing_method' => $requestedMethod,
                'billing_change_request' => null,
                'billing_change_reason' => null,
                'billing_change_requested_at' => null,
            ];

            // If switching to postpaid, set credit limit and payment terms
            if ($requestedMethod === BillingMethod::POSTPAID->value) {
                if ($request->has('credit_limit')) {
                    $updateData['credit_limit'] = $request->credit_limit;
                }
                if ($request->has('payment_terms_days')) {
                    $updateData['payment_terms_days'] = $request->payment_terms_days;
                }
            }

            $business->update($updateData);

            // Approve the request
            $billingChangeRequest->approve(
                auth()->id(),
                $request->admin_notes
            );

            DB::commit();

            return back()->with('success', 'Billing method change approved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve billing method change: ' . $e->getMessage());
        }
    }

    /**
     * Reject billing change request
     */
    public function rejectBillingChangeRequest(Request $request, BillingChangeRequest $billingChangeRequest)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $billingChangeRequest->reject(
                auth()->id(),
                $request->admin_notes
            );

            // Clear the request from business
            $business = $billingChangeRequest->business;
            $business->update([
                'billing_change_request' => null,
                'billing_change_reason' => null,
                'billing_change_requested_at' => null,
            ]);

            DB::commit();

            return back()->with('success', 'Billing method change rejected successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject billing method change: ' . $e->getMessage());
        }
    }

    /**
     * Update business billing method directly (admin override)
     */
    public function updateBusinessBillingMethod(Request $request, Business $business)
    {
        $request->validate([
            'billing_method' => 'required|in:prepaid,postpaid',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:1|max:365',
            'billing_cycle' => 'nullable|in:daily,weekly,monthly',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'billing_method' => $request->billing_method,
            ];

            // If postpaid, set credit limit and payment terms
            if ($request->billing_method === BillingMethod::POSTPAID->value) {
                if ($request->has('credit_limit')) {
                    $updateData['credit_limit'] = $request->credit_limit;
                }
                if ($request->has('payment_terms_days')) {
                    $updateData['payment_terms_days'] = $request->payment_terms_days;
                }
                if ($request->has('billing_cycle')) {
                    $updateData['billing_cycle'] = $request->billing_cycle;
                }
            }

            // Clear any pending requests
            $updateData['billing_change_request'] = null;
            $updateData['billing_change_reason'] = null;
            $updateData['billing_change_requested_at'] = null;

            $business->update($updateData);

            DB::commit();

            return back()->with('success', 'Billing method updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update billing method: ' . $e->getMessage());
        }
    }

    /**
     * Suspend/unsuspend business account (for postpaid)
     */
    public function toggleAccountSuspension(Request $request, Business $business)
    {
        if ($business->isAccountSuspended()) {
            $business->unsuspendAccount();
            return back()->with('success', 'Account unsuspended successfully!');
        } else {
            $request->validate([
                'suspension_reason' => 'required|string|max:500',
            ]);

            $business->suspendAccount($request->suspension_reason);
            return back()->with('success', 'Account suspended successfully!');
        }
    }
}
