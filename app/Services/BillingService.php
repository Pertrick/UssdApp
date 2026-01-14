<?php

namespace App\Services;

use App\Models\USSDSession;
use App\Models\Business;
use App\Models\User;
use App\Models\BillingTransaction;
use App\Enums\BillingMethod;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BillingService
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Calculate session cost based on business pricing
     */
    public function calculateSessionCost(USSDSession $session): float
    {
        $ussd = $session->ussd;
        $business = $ussd->business;
        
        // Get pricing from business settings
        $sessionPrice = $business->session_price ?? 0.02; // Default per-session price
        $currency = $business->billing_currency ?? config('app.currency', 'NGN');
        
        return $sessionPrice;
    }

    /**
     * Bill a session (routes to prepaid or postpaid handler)
     * Called on session START (first request) to match AfricasTalking billing model
     */
    public function billSession(USSDSession $session): bool
    {
        $business = $session->ussd->business;
        
        // Check if billing is enabled
        if (!$business->billing_enabled) {
            return true; // Skip billing if disabled
        }

        // Route based on billing method
        if ($business->isPrepaid()) {
            return $this->billSessionPrepaid($session);
        } elseif ($business->isPostpaid()) {
            return $this->billSessionPostpaid($session);
        }

        // Default to prepaid if method not set
        return $this->billSessionPrepaid($session);
    }

    /**
     * Bill session using prepaid method (immediate charge)
     */
    protected function billSessionPrepaid(USSDSession $session): bool
    {
        try {
            DB::beginTransaction();
            
            // Calculate cost
            $amount = $this->calculateSessionCost($session);
            $business = $session->ussd->business;
            
            // Check if business has sufficient balance
            if ($business->account_balance < $amount) {
                Log::warning('Insufficient balance for session billing', [
                    'session_id' => $session->id,
                    'business_id' => $business->id,
                    'required_amount' => $amount,
                    'available_balance' => $business->account_balance
                ]);
                
                $session->update([
                    'billing_status' => 'failed',
                    'billing_amount' => $amount,
                    'error_message' => 'Insufficient account balance'
                ]);
                
                DB::rollBack();
                return false;
            }
            
            // Record balance before
            $balanceBefore = $business->account_balance;
            
            // Deduct from business balance
            $business->decrement('account_balance', $amount);
            
            // Update session billing
            $session->update([
                'is_billed' => true,
                'billing_amount' => $amount,
                'billing_currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
                'billing_status' => 'charged',
                'billed_at' => now(),
                'invoice_id' => $this->generateInvoiceId($session)
            ]);
            
            // Create billing transaction
            $this->createBillingTransaction($session, $amount, 'prepaid', $balanceBefore, $business->fresh()->account_balance);
            
            // Log the transaction
            $this->logBillingTransaction($session, $amount);
            
            DB::commit();
            
            Log::info('Session billed successfully (prepaid)', [
                'session_id' => $session->id,
                'business_id' => $business->id,
                'amount' => $amount,
                'new_balance' => $business->fresh()->account_balance
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Session billing failed (prepaid)', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
            
            $session->update([
                'billing_status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Bill session using postpaid method (accumulate for invoice)
     */
    protected function billSessionPostpaid(USSDSession $session): bool
    {
        try {
            DB::beginTransaction();
            
            // Calculate cost
            $amount = $this->calculateSessionCost($session);
            $business = $session->ussd->business;
            
            // Check if account is suspended
            if ($business->isAccountSuspended()) {
                Log::warning('Account suspended, cannot bill session', [
                    'session_id' => $session->id,
                    'business_id' => $business->id,
                ]);
                
                $session->update([
                    'billing_status' => 'failed',
                    'billing_amount' => $amount,
                    'error_message' => 'Account is suspended'
                ]);
                
                DB::rollBack();
                return false;
            }
            
            // Check credit limit (optional - can allow usage with warning)
            $outstandingBalance = $business->getOutstandingBalance();
            $availableCredit = $business->getAvailableCredit();
            
            if ($business->credit_limit && ($outstandingBalance + $amount) > $business->credit_limit) {
                Log::warning('Credit limit exceeded for session billing', [
                    'session_id' => $session->id,
                    'business_id' => $business->id,
                    'required_amount' => $amount,
                    'outstanding_balance' => $outstandingBalance,
                    'credit_limit' => $business->credit_limit
                ]);
                
                // Option: Allow with warning or block
                // For now, we'll allow but log warning
                // You can change this to return false if you want to block
            }
            
            // Mark session as pending billing (will be added to invoice later)
            $session->update([
                'is_billed' => false, // Not billed yet, will be invoiced
                'billing_amount' => $amount,
                'billing_currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
                'billing_status' => 'pending', // Will be changed to 'invoiced' when added to invoice
            ]);
            
            // Create pending transaction record
            $this->createBillingTransaction($session, $amount, 'postpaid', null, null, 'pending');
            
            DB::commit();
            
            Log::info('Session marked for postpaid billing', [
                'session_id' => $session->id,
                'business_id' => $business->id,
                'amount' => $amount,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Session billing failed (postpaid)', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
            
            $session->update([
                'billing_status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Create billing transaction record
     */
    protected function createBillingTransaction(
        USSDSession $session,
        float $amount,
        string $method,
        ?float $balanceBefore = null,
        ?float $balanceAfter = null,
        string $status = 'completed'
    ): BillingTransaction {
        $business = $session->ussd->business;
        
        return BillingTransaction::create([
            'business_id' => $business->id,
            'ussd_session_id' => $session->id,
            'transaction_number' => $this->generateTransactionNumber($business),
            'type' => BillingTransaction::TYPE_CHARGE,
            'method' => $method,
            'amount' => $amount,
            'currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
            'status' => $status,
            'description' => "Session charge: {$session->phone_number}",
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ]);
    }

    /**
     * Generate transaction number
     */
    protected function generateTransactionNumber(Business $business): string
    {
        $timestamp = now()->format('YmdHis');
        $businessId = str_pad($business->id, 4, '0', STR_PAD_LEFT);
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "TXN-{$businessId}-{$timestamp}-{$random}";
    }

    /**
     * Generate unique invoice ID
     */
    protected function generateInvoiceId(USSDSession $session): string
    {
        $businessId = $session->ussd->business_id;
        $sessionId = $session->id;
        $timestamp = now()->format('YmdHis');
        
        return "INV-{$businessId}-{$sessionId}-{$timestamp}";
    }

    /**
     * Log billing transaction
     */
    protected function logBillingTransaction(USSDSession $session, float $amount): void
    {
        // You can create a separate transactions table or use activity logs
        \App\Services\ActivityService::log(
            $session->ussd->user_id,
            'session_billed',
            "Session billed: {$session->phone_number} - \${$amount}",
            'App\Models\USSDSession',
            $session->id,
            [
                'amount' => $amount,
                'phone_number' => $session->phone_number,
                'invoice_id' => $session->invoice_id
            ]
        );
    }

    /**
     * Get billing summary for business
     */
    public function getBillingSummary(Business $business, $period = 'month'): array
    {
        $query = USSDSession::whereHas('ussd', function($q) use ($business) {
            $q->where('business_id', $business->id);
        })->where('is_billed', true);

        // Filter by period using proper datetime ranges to avoid timezone issues
        switch ($period) {
            case 'today':
                $query->whereBetween('billed_at', [
                    now()->startOfDay(),
                    now()->endOfDay()
                ]);
                break;
            case 'week':
                $query->whereBetween('billed_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereBetween('billed_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
            case 'year':
                $query->whereBetween('billed_at', [
                    now()->startOfYear(),
                    now()->endOfYear()
                ]);
                break;
        }

        $sessions = $query->get();

        return [
            'total_sessions' => $sessions->count(),
            'total_amount' => $sessions->sum('billing_amount'),
            'average_session_cost' => $sessions->avg('billing_amount'),
            // Use business billing currency, defaulting to configured app currency (e.g. NGN)
            'currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
            'period' => $period
        ];
    }

    /**
     * Get real-time billing stats (supports both prepaid and postpaid)
     */
    public function getRealTimeStats(Business $business): array
    {
        $baseStats = [
            'billing_method' => $business->billing_method?->value ?? 'postpaid',
            'currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
        ];

        if ($business->isPrepaid()) {
            // Prepaid stats
            $today = USSDSession::whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('is_billed', true)
            ->whereBetween('billed_at', [
                now()->startOfDay(),
                now()->endOfDay()
            ])
            ->get();

            $thisMonth = USSDSession::whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('is_billed', true)
            ->whereBetween('billed_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->get();

            // Separate production and testing billing
            $todayProduction = $today->where('billing_status', 'charged');
            $todayTesting = $today->where('billing_status', 'testing');
            $monthProduction = $thisMonth->where('billing_status', 'charged');
            $monthTesting = $thisMonth->where('billing_status', 'testing');

            return array_merge($baseStats, [
                'today' => [
                    'sessions' => $today->count(),
                    'amount' => $today->sum('billing_amount'),
                    'production_sessions' => $todayProduction->count(),
                    'production_amount' => $todayProduction->sum('billing_amount'),
                    'testing_sessions' => $todayTesting->count(),
                    'testing_amount' => $todayTesting->sum('billing_amount')
                ],
                'this_month' => [
                    'sessions' => $thisMonth->count(),
                    'amount' => $thisMonth->sum('billing_amount'),
                    'production_sessions' => $monthProduction->count(),
                    'production_amount' => $monthProduction->sum('billing_amount'),
                    'testing_sessions' => $monthTesting->count(),
                    'testing_amount' => $monthTesting->sum('billing_amount')
                ],
                'account_balance' => $business->account_balance,
                'test_balance' => $business->test_balance,
            ]);
        } else {
            // Postpaid stats
            $unbilledSessions = USSDSession::whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('is_billed', false)
            ->where('billing_status', 'pending')
            ->get();

            $invoicedSessions = USSDSession::whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('billing_status', 'invoiced')
            ->get();

            $outstandingBalance = $business->getOutstandingBalance();
            $availableCredit = $business->getAvailableCredit();
            $creditLimit = $business->credit_limit ?? 0;

            // For UI compatibility, also provide today/this_month stats and balances
            $today = USSDSession::whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('is_billed', true)
            ->whereBetween('billed_at', [
                now()->startOfDay(),
                now()->endOfDay()
            ])
            ->get();

            $thisMonth = USSDSession::whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })->where('is_billed', true)
            ->whereBetween('billed_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->get();

            $todayProduction = $today->where('billing_status', 'charged');
            $todayTesting = $today->where('billing_status', 'testing');
            $monthProduction = $thisMonth->where('billing_status', 'charged');
            $monthTesting = $thisMonth->where('billing_status', 'testing');

            return array_merge($baseStats, [
                // Keep original postpaid-specific stats
                'unbilled_sessions' => $unbilledSessions->count(),
                'unbilled_amount' => $unbilledSessions->sum('billing_amount'),
                'invoiced_sessions' => $invoicedSessions->count(),
                'invoiced_amount' => $invoicedSessions->sum('billing_amount'),
                'outstanding_balance' => $outstandingBalance,
                'credit_limit' => $creditLimit,
                'available_credit' => $availableCredit,
                'account_suspended' => $business->isAccountSuspended(),
                // UI-compatible aggregates
                'today' => [
                    'sessions' => $today->count(),
                    'amount' => $today->sum('billing_amount'),
                    'production_sessions' => $todayProduction->count(),
                    'production_amount' => $todayProduction->sum('billing_amount'),
                    'testing_sessions' => $todayTesting->count(),
                    'testing_amount' => $todayTesting->sum('billing_amount'),
                ],
                'this_month' => [
                    'sessions' => $thisMonth->count(),
                    'amount' => $thisMonth->sum('billing_amount'),
                    'production_sessions' => $monthProduction->count(),
                    'production_amount' => $monthProduction->sum('billing_amount'),
                    'testing_sessions' => $monthTesting->count(),
                    'testing_amount' => $monthTesting->sum('billing_amount'),
                ],
                'account_balance' => $business->account_balance,
                'test_balance' => $business->test_balance,
            ]);
        }
    }

    /**
     * Add funds to business account
     */
    public function addFunds(Business $business, float $amount, string $paymentMethod = 'manual'): bool
    {
        try {
            DB::beginTransaction();
            
            $business->increment('account_balance', $amount);
            
            // Log the transaction
            \App\Services\ActivityService::log(
                $business->user_id,
                'funds_added',
                "Added \${$amount} to account balance",
                'App\Models\Business',
                $business->id,
                [
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'new_balance' => $business->fresh()->account_balance
                ]
            );
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add funds', [
                'business_id' => $business->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Add test funds to business account (for testing/simulation)
     */
    public function addTestFunds(Business $business, float $amount): bool
    {
        try {
            DB::beginTransaction();
            
            $business->increment('test_balance', $amount);
            
            // Log the transaction
            \App\Services\ActivityService::log(
                $business->user_id,
                'test_funds_added',
                "Added \${$amount} to test balance",
                'App\Models\Business',
                $business->id,
                [
                    'amount' => $amount,
                    'new_test_balance' => $business->fresh()->test_balance
                ]
            );
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add test funds', [
                'business_id' => $business->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get balance for specific environment (live or test)
     */
    public function getBalanceForEnvironment(Business $business, string $environment = 'live'): float
    {
        $balance = $environment === 'test' ? $business->test_balance : $business->account_balance;
        return (float) ($balance ?? 0);
    }
}
