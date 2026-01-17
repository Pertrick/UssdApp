<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Business;
use App\Models\UssdCost;
use App\Models\NetworkPricing;
use App\Models\Environment;
use App\Models\USSDSession;
use App\Enums\BillingMethod;
use Illuminate\Http\Request;
use App\Enums\EnvironmentType;
use Illuminate\Support\Facades\DB;
use App\Models\BillingChangeRequest;
use App\Services\GatewayCostService;
use Illuminate\Support\Facades\Storage;
use App\Enums\BusinessRegistrationStatus;
use App\Models\WebhookEvent;

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
        $productionEnvironmentIds = Environment::whereIn('name', [EnvironmentType::PRODUCTION->value, 'live'])
            ->pluck('id')
            ->toArray();

        // Today's stats - only production/live
        $todaySessions = USSDSession::where('is_billed', true)
            ->where('billing_status', 'charged') 
            ->whereIn('environment_id', $productionEnvironmentIds)
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
            ->where('billing_status', 'charged')
            ->whereIn('environment_id', $productionEnvironmentIds)
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
            ->where('billing_status', 'charged')
            ->whereIn('environment_id', $productionEnvironmentIds)
            ->get();

        // Revenue is already in main currency (decimal)
        $allTimeRevenue = (float) $allTimeSessions->sum('billing_amount');
        
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
        $query = User::with(['businesses' => function($q) {
            $q->select('id', 'user_id', 'business_name', 'account_balance', 'test_balance', 'billing_currency', 'billing_method', 'is_primary');
        }, 'roles'])
        ->withCount('businesses');

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);
        
        $users->getCollection()->transform(function ($user) {
            $businessesCount = isset($user->businesses_count) 
                ? $user->businesses_count 
                : (isset($user->businesses) ? $user->businesses->count() : 0);
            
            $user->businesses_summary = [
                'total_balance' => $user->businesses ? $user->businesses->sum('account_balance') : 0,
                'total_test_balance' => $user->businesses ? $user->businesses->sum('test_balance') : 0,
                'primary_business' => $user->businesses ? $user->businesses->where('is_primary', true)->first() : null,
            ];
            
            // Explicitly set businesses_count to ensure it's included in JSON response
            $user->setAttribute('businesses_count', $businessesCount);
            
            return $user;
        });
        
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
        $roles = Role::withCount('users')->get();
        
        $country = config('app.country', 'NG');
        $currency = config('app.currency', 'NGN');
        
        // Get all networks (MTN, Airtel, Glo, 9mobile)
        $networks = ['MTN', 'Airtel', 'Glo', '9mobile'];
        $networkPricing = [];
        
        $gatewayCostService = app(\App\Services\GatewayCostService::class);
        
        foreach ($networks as $network) {
            // Get latest AT cost from ussd_costs table
            $atCost = UssdCost::getActiveCost($country, $network);
            $atCostInMainCurrency = 0;
            
            if ($atCost) {
                $atCostInMainCurrency = $gatewayCostService->convertFromSmallestUnit(
                    $atCost->cost_per_session,
                    $atCost->currency ?? $currency
                );
            }
            
            // Get markup from network_pricing table
            $pricing = \App\Models\NetworkPricing::getActivePricing($country, $network);
            
            $networkPricing[] = [
                'id' => $pricing?->id,
                'network' => $network,
                'at_cost' => $atCostInMainCurrency,
                'at_cost_updated_at' => $atCost?->updated_at?->toDateString(),
                'markup_percentage' => $pricing?->markup_percentage ?? 50.0,
                'minimum_price' => $pricing?->minimum_price,
                'currency' => $currency,
                'is_active' => $pricing?->is_active ?? true,
            ];
        }
        
        return Inertia::render('Admin/Settings', [
            'roles' => $roles,
            'networkPricing' => $networkPricing,
        ]);
    }
    
    /**
     * Create or update network pricing (markup)
     */
    public function createNetworkPricing(Request $request)
    {
        $request->validate([
            'network' => 'required|string|max:255',
            'markup_percentage' => 'required|numeric|min:0|max:1000',
            'minimum_price' => 'nullable|numeric|min:0',
            'country' => 'required|string|size:2',
            'currency' => 'required|string|size:3',
        ]);
        
        NetworkPricing::updateOrCreate(
            [
                'country' => $request->country,
                'network' => $request->network,
            ],
            [
                'markup_percentage' => $request->markup_percentage,
                'minimum_price' => $request->minimum_price,
                'currency' => $request->currency,
                'is_active' => true,
            ]
        );
        
        return back()->with('success', 'Network pricing created successfully!');
    }
    
    /**
     * Update network pricing (markup)
     */
    public function updateNetworkPricing(Request $request, NetworkPricing $networkPricing)
    {
        $request->validate([
            'markup_percentage' => 'required|numeric|min:0|max:1000',
            'minimum_price' => 'nullable|numeric|min:0',
        ]);
        
        $networkPricing->update([
            'markup_percentage' => $request->markup_percentage,
            'minimum_price' => $request->minimum_price,
        ]);
        
        return back()->with('success', 'Network pricing updated successfully!');
    }
    
    /**
     * Update business discount
     */
    public function updateBusinessDiscount(Request $request, Business $business)
    {
        $request->validate([
            'discount_type' => 'required|in:none,percentage,fixed',
            'discount_percentage' => 'nullable|numeric|min:0|max:100|required_if:discount_type,percentage',
            'discount_amount' => 'nullable|numeric|min:0|required_if:discount_type,fixed',
        ]);
        
        $business->update([
            'discount_type' => $request->discount_type,
            'discount_percentage' => $request->discount_type === 'percentage' ? $request->discount_percentage : null,
            'discount_amount' => $request->discount_type === 'fixed' ? $request->discount_amount : null,
        ]);
        
        return back()->with('success', 'Business discount updated successfully!');
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

    /**
     * Comprehensive billing report with profit/loss analysis
     * Shows revenue, gateway costs, and profit for all businesses with network breakdown
     * 
     * IMPORTANT: Only includes PRODUCTION sessions. Testing sessions are excluded.
     */
    public function billingReport(Request $request)
    {
        $currency = config('app.currency', 'NGN');
        $currencySymbol = config('app.currency_symbol', '₦');
        $gatewayCostService = app(GatewayCostService::class);

        // Date range filters
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        // Additional filters
        $networkFilter = $request->input('network'); // Filter by specific network
        $businessFilter = $request->input('business_id'); // Filter by specific business

        // Get production/live environment IDs only (exclude testing)
        $productionEnvironmentIds = Environment::whereIn('name', [EnvironmentType::PRODUCTION->value, 'live'])
            ->pluck('id')
            ->toArray();

        // Explicitly exclude testing environment IDs
        $testingEnvironmentIds = Environment::where('name', EnvironmentType::TESTING->value)
            ->pluck('id')
            ->toArray();

        // Base query for billed production sessions ONLY
        // Excludes: testing environments, testing billing status, and any non-production sessions
        $baseQuery = USSDSession::where('is_billed', true)
            ->where('billing_status', 'charged') // Only charged sessions (excludes 'testing' status)
            ->whereIn('environment_id', $productionEnvironmentIds) // Only production/live environments
            ->whereNotIn('environment_id', $testingEnvironmentIds) // Explicitly exclude testing
            ->whereBetween('billed_at', [$startDate, $endDate])
            ->with(['ussd.business', 'environment']);

        // Apply network filter if provided
        if ($networkFilter) {
            $baseQuery->where('network_provider', $networkFilter);
        }

        // Apply business filter if provided
        if ($businessFilter) {
            $baseQuery->whereHas('ussd', function($q) use ($businessFilter) {
                $q->where('business_id', $businessFilter);
            });
        }

        // Platform-wide summary
        $allSessions = (clone $baseQuery)->get();
        $platformRevenue = (float) $allSessions->sum('billing_amount');
        $platformGatewayCostsInSmallestUnit = (int) ($allSessions->sum('gateway_cost') ?? 0);
        $platformGatewayCosts = $gatewayCostService->convertFromSmallestUnit($platformGatewayCostsInSmallestUnit, $currency);
        $platformProfit = $platformRevenue - $platformGatewayCosts;
        $platformMargin = $platformRevenue > 0 ? ($platformProfit / $platformRevenue) * 100 : 0;

        // Network breakdown (all businesses combined)
        $networkBreakdown = [];
        $networks = $allSessions->whereNotNull('network_provider')->pluck('network_provider')->unique()->sort();
        
        foreach ($networks as $network) {
            $networkSessions = $allSessions->where('network_provider', $network);
            $networkRevenue = (float) $networkSessions->sum('billing_amount');
            $networkCostsInSmallestUnit = (int) ($networkSessions->sum('gateway_cost') ?? 0);
            $networkCosts = $gatewayCostService->convertFromSmallestUnit($networkCostsInSmallestUnit, $currency);
            $networkProfit = $networkRevenue - $networkCosts;
            $networkMargin = $networkRevenue > 0 ? ($networkProfit / $networkRevenue) * 100 : 0;
            $networkAvgCostPerSession = $networkSessions->count() > 0 
                ? $networkCosts / $networkSessions->count() 
                : 0;
            $networkAvgRevenuePerSession = $networkSessions->count() > 0 
                ? $networkRevenue / $networkSessions->count() 
                : 0;

            $networkBreakdown[] = [
                'network' => $network,
                'sessions' => $networkSessions->count(),
                'revenue' => round($networkRevenue, 2),
                'gateway_costs' => round($networkCosts, 2),
                'profit' => round($networkProfit, 2),
                'margin_percentage' => round($networkMargin, 2),
                'avg_cost_per_session' => round($networkAvgCostPerSession, 4),
                'avg_revenue_per_session' => round($networkAvgRevenuePerSession, 4),
            ];
        }

        // Unknown/Null network sessions
        $unknownNetworkSessions = $allSessions->whereNull('network_provider');
        if ($unknownNetworkSessions->count() > 0) {
            $unknownRevenue = (float) $unknownNetworkSessions->sum('billing_amount');
            $unknownCostsInSmallestUnit = (int) ($unknownNetworkSessions->sum('gateway_cost') ?? 0);
            $unknownCosts = $gatewayCostService->convertFromSmallestUnit($unknownCostsInSmallestUnit, $currency);
            $unknownProfit = $unknownRevenue - $unknownCosts;
            $unknownMargin = $unknownRevenue > 0 ? ($unknownProfit / $unknownRevenue) * 100 : 0;

            $networkBreakdown[] = [
                'network' => 'Unknown/Unspecified',
                'sessions' => $unknownNetworkSessions->count(),
                'revenue' => round($unknownRevenue, 2),
                'gateway_costs' => round($unknownCosts, 2),
                'profit' => round($unknownProfit, 2),
                'margin_percentage' => round($unknownMargin, 2),
                'avg_cost_per_session' => round($unknownCosts / max($unknownNetworkSessions->count(), 1), 4),
                'avg_revenue_per_session' => round($unknownRevenue / max($unknownNetworkSessions->count(), 1), 4),
            ];
        }

        // Per-business breakdown
        $businessBreakdown = [];
        $businesses = Business::with(['ussds'])->get();

        foreach ($businesses as $business) {
            $businessSessions = $allSessions->filter(function($session) use ($business) {
                return $session->ussd && $session->ussd->business_id === $business->id;
            });

            if ($businessSessions->isEmpty()) {
                continue; // Skip businesses with no sessions in this period
            }

            $businessRevenue = (float) $businessSessions->sum('billing_amount');
            $businessCostsInSmallestUnit = (int) ($businessSessions->sum('gateway_cost') ?? 0);
            $businessCosts = $gatewayCostService->convertFromSmallestUnit($businessCostsInSmallestUnit, $currency);
            $businessProfit = $businessRevenue - $businessCosts;
            $businessMargin = $businessRevenue > 0 ? ($businessProfit / $businessRevenue) * 100 : 0;

            // Network breakdown for this business
            $businessNetworkBreakdown = [];
            $businessNetworks = $businessSessions->whereNotNull('network_provider')->pluck('network_provider')->unique()->sort();
            
            foreach ($businessNetworks as $network) {
                $networkSessions = $businessSessions->where('network_provider', $network);
                $networkRevenue = (float) $networkSessions->sum('billing_amount');
                $networkCostsInSmallestUnit = (int) ($networkSessions->sum('gateway_cost') ?? 0);
                $networkCosts = $gatewayCostService->convertFromSmallestUnit($networkCostsInSmallestUnit, $currency);
                $networkProfit = $networkRevenue - $networkCosts;

                $businessNetworkBreakdown[] = [
                    'network' => $network,
                    'sessions' => $networkSessions->count(),
                    'revenue' => round($networkRevenue, 2),
                    'gateway_costs' => round($networkCosts, 2),
                    'profit' => round($networkProfit, 2),
                ];
            }

            // Get business billing method and pricing
            $sessionPrice = $business->session_price ?? 0;
            $billingMethod = $business->billing_method?->value ?? 'unknown';

            $businessBreakdown[] = [
                'id' => $business->id,
                'business_name' => $business->business_name,
                'business_email' => $business->business_email,
                'billing_method' => $billingMethod,
                'session_price' => round($sessionPrice, 4),
                'currency' => $business->billing_currency ?? $currency,
                'sessions' => $businessSessions->count(),
                'revenue' => round($businessRevenue, 2),
                'gateway_costs' => round($businessCosts, 2),
                'profit' => round($businessProfit, 2),
                'margin_percentage' => round($businessMargin, 2),
                'avg_cost_per_session' => round($businessCosts / max($businessSessions->count(), 1), 4),
                'avg_revenue_per_session' => round($businessRevenue / max($businessSessions->count(), 1), 4),
                'is_profitable' => $businessProfit >= 0,
                'network_breakdown' => $businessNetworkBreakdown,
            ];
        }

        // Sort businesses by revenue (descending)
        usort($businessBreakdown, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        // Sessions without gateway cost (data quality check)
        $sessionsWithoutCost = $allSessions->whereNull('gateway_cost')->count();
        $sessionsWithCost = $allSessions->whereNotNull('gateway_cost')->count();

        // Calculate average values
        $avgCostPerSession = $allSessions->count() > 0 
            ? $platformGatewayCosts / $allSessions->count() 
            : 0;
        $avgRevenuePerSession = $allSessions->count() > 0 
            ? $platformRevenue / $allSessions->count() 
            : 0;

        // Summary statistics
        $summary = [
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'total_sessions' => $allSessions->count(),
            'sessions_with_gateway_cost' => $sessionsWithCost,
            'sessions_without_gateway_cost' => $sessionsWithoutCost,
            'revenue' => round($platformRevenue, 2),
            'gateway_costs' => round($platformGatewayCosts, 2),
            'profit' => round($platformProfit, 2),
            'margin_percentage' => round($platformMargin, 2),
            'avg_cost_per_session' => round($avgCostPerSession, 4),
            'avg_revenue_per_session' => round($avgRevenuePerSession, 4),
            'avg_profit_per_session' => round($platformProfit / max($allSessions->count(), 1), 4),
            'total_businesses' => count($businessBreakdown),
            'profitable_businesses' => collect($businessBreakdown)->where('is_profitable', true)->count(),
            'unprofitable_businesses' => collect($businessBreakdown)->where('is_profitable', false)->count(),
        ];

        // Get available networks and businesses for filter dropdowns
        $availableNetworks = USSDSession::where('is_billed', true)
            ->where('billing_status', 'charged')
            ->whereIn('environment_id', $productionEnvironmentIds)
            ->whereNotNull('network_provider')
            ->distinct()
            ->pluck('network_provider')
            ->sort()
            ->values();

        // Get businesses that have production sessions
        $availableBusinesses = Business::whereHas('ussds', function($q) use ($productionEnvironmentIds) {
                $q->whereHas('sessions', function($sq) use ($productionEnvironmentIds) {
                    $sq->where('is_billed', true)
                       ->where('billing_status', 'charged')
                       ->whereIn('environment_id', $productionEnvironmentIds);
                });
            })
            ->select('id', 'business_name')
            ->orderBy('business_name')
            ->get();

        return Inertia::render('Admin/BillingReport', [
            'summary' => $summary,
            'platform_summary' => [
                'revenue' => round($platformRevenue, 2),
                'gateway_costs' => round($platformGatewayCosts, 2),
                'profit' => round($platformProfit, 2),
                'margin_percentage' => round($platformMargin, 2),
            ],
            'network_breakdown' => $networkBreakdown,
            'business_breakdown' => $businessBreakdown,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'network' => $networkFilter,
                'business_id' => $businessFilter,
            ],
            'available_networks' => $availableNetworks,
            'available_businesses' => $availableBusinesses,
        ]);
    }

    /**
     * View all billing sessions for a specific business
     * Shows individual sessions with session strings for auditing
     */
    public function businessBillingSessions(Request $request, Business $business)
    {
        $currency = config('app.currency', 'NGN');
        $currencySymbol = config('app.currency_symbol', '₦');
        $gatewayCostService = app(GatewayCostService::class);

        // Date range filters
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        // Additional filters
        $networkFilter = $request->input('network');

        // Get production/live environment IDs only
        $productionEnvironmentIds = Environment::whereIn('name', [EnvironmentType::PRODUCTION->value, 'live'])
            ->pluck('id')
            ->toArray();

        $testingEnvironmentIds = Environment::where('name', EnvironmentType::TESTING->value)
            ->pluck('id')
            ->toArray();

        // Query for this business's sessions
        $query = USSDSession::where('is_billed', true)
            ->where('billing_status', 'charged')
            ->whereIn('environment_id', $productionEnvironmentIds)
            ->whereNotIn('environment_id', $testingEnvironmentIds)
            ->whereBetween('billed_at', [$startDate, $endDate])
            ->whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })
            ->with(['ussd.business', 'environment'])
            ->orderByDesc('billed_at');

        // Apply network filter if provided
        if ($networkFilter) {
            $query->where('network_provider', $networkFilter);
        }

        $sessions = $query->paginate(50);

        // Calculate totals
        $totalSessions = $sessions->total();
        $totalRevenue = (float) $sessions->sum('billing_amount');
        $totalGatewayCostsInSmallestUnit = (int) ($sessions->sum('gateway_cost') ?? 0);
        $totalGatewayCosts = $gatewayCostService->convertFromSmallestUnit($totalGatewayCostsInSmallestUnit, $currency);
        $totalProfit = $totalRevenue - $totalGatewayCosts;

        // Get available networks for filter (all production sessions for this business)
        $availableNetworksQuery = USSDSession::whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })
            ->where('is_billed', true)
            ->where('billing_status', 'charged')
            ->whereIn('environment_id', $productionEnvironmentIds)
            ->whereNotIn('environment_id', $testingEnvironmentIds)
            ->whereNotNull('network_provider');
        
        $availableNetworks = $availableNetworksQuery
            ->distinct()
            ->pluck('network_provider')
            ->sort()
            ->values()
            ->toArray();
        
        // Fallback: if no networks found in query, extract from current sessions
        if (empty($availableNetworks) && $sessions->count() > 0) {
            $availableNetworks = $sessions->getCollection()
                ->whereNotNull('network_provider')
                ->pluck('network_provider')
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        }

        return Inertia::render('Admin/BusinessBillingSessions', [
            'business' => $business,
            'sessions' => $sessions,
            'summary' => [
                'total_sessions' => $totalSessions,
                'total_revenue' => round($totalRevenue, 2),
                'total_gateway_costs' => round($totalGatewayCosts, 2),
                'total_profit' => round($totalProfit, 2),
                'currency' => $currency,
                'currency_symbol' => $currencySymbol,
                'period_start' => $startDate->format('M d, Y'),
                'period_end' => $endDate->format('M d, Y'),
            ],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'network' => $networkFilter,
            ],
            'available_networks' => $availableNetworks,
        ]);
    }

    /**
     * Show webhook events page
     */
    public function webhookEvents(Request $request)
    {
        $query = WebhookEvent::with('ussdSession');

        // Filter by source
        if ($request->has('source') && $request->source) {
            $query->where('source', $request->source);
        }

        // Filter by processing status
        if ($request->has('status') && $request->status) {
            $query->where('processing_status', $request->status);
        }

        // Filter by event type
        if ($request->has('event_type') && $request->event_type) {
            $query->where('event_type', $request->event_type);
        }

        // Search by session ID
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('session_id', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $events = $query->latest()->paginate(50);

        // Statistics
        $stats = [
            'total' => WebhookEvent::count(),
            'processed' => WebhookEvent::where('processing_status', 'processed')->count(),
            'failed' => WebhookEvent::where('processing_status', 'failed')->count(),
            'pending' => WebhookEvent::where('processing_status', 'pending')->count(),
        ];

        return Inertia::render('Admin/WebhookEvents', [
            'events' => $events,
            'stats' => $stats,
            'filters' => $request->only(['source', 'status', 'event_type', 'search', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Show webhook event details
     */
    public function showWebhookEvent(WebhookEvent $webhookEvent)
    {
        $webhookEvent->load('ussdSession.ussd.business');

        return Inertia::render('Admin/WebhookEventDetail', [
            'event' => $webhookEvent,
        ]);
    }
}
