<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\USSDSession;
use App\Models\BillingTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber(Business $business): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $businessId = str_pad($business->id, 4, '0', STR_PAD_LEFT);
        
        // Get the last invoice number for this business this year
        $lastInvoice = Invoice::where('business_id', $business->id)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastInvoice && preg_match('/INV-(\d{4})-(\d{2})-(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $sequence = (int)$matches[3] + 1;
        } else {
            $sequence = 1;
        }
        
        $sequenceNumber = str_pad($sequence, 5, '0', STR_PAD_LEFT);
        
        return "INV-{$year}-{$month}-{$sequenceNumber}";
    }

    /**
     * Create a new invoice for a business
     */
    public function createInvoice(
        Business $business,
        Carbon $periodStart = null,
        Carbon $periodEnd = null,
        int $createdBy = null
    ): Invoice {
        try {
            DB::beginTransaction();

            $periodStart = $periodStart ?? $this->getBillingCycleStart($business);
            $periodEnd = $periodEnd ?? $this->getBillingCycleEnd($business, $periodStart);

            $invoice = Invoice::create([
                'business_id' => $business->id,
                'invoice_number' => $this->generateInvoiceNumber($business),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => now(),
                'due_date' => now()->addDays((int) ($business->payment_terms_days ?? 15)),
                'currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
                'status' => Invoice::STATUS_DRAFT,
                'created_by' => $createdBy,
            ]);

            DB::commit();

            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'business_id' => $business->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create invoice', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add unbilled sessions to invoice
     */
    public function addSessionsToInvoice(Invoice $invoice, array $sessionIds = null): int
    {
        try {
            DB::beginTransaction();

            $business = $invoice->business;
            $sessionPrice = $business->session_price ?? 0.02;

            // Get unbilled sessions for this business in the invoice period
            $query = USSDSession::whereHas('ussd', function($q) use ($business) {
                $q->where('business_id', $business->id);
            })
            ->where('is_billed', false)
            ->where('billing_status', 'pending')
            ->whereBetween('created_at', [
                $invoice->period_start ?? Carbon::minValue(),
                $invoice->period_end ?? Carbon::maxValue(),
            ]);

            if ($sessionIds) {
                $query->whereIn('id', $sessionIds);
            }

            $sessions = $query->get();
            $addedCount = 0;

            foreach ($sessions as $session) {
                // Create invoice item
                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'ussd_session_id' => $session->id,
                    'description' => "USSD Session: {$session->phone_number}",
                    'details' => "Session ID: {$session->session_id}",
                    'quantity' => 1,
                    'unit_price' => $sessionPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $sessionPrice,
                    'item_type' => InvoiceItem::TYPE_SESSION,
                ]);

                // Mark session as billed (but not charged yet - will be charged when invoice is paid)
                $session->update([
                    'is_billed' => true,
                    'billing_amount' => $sessionPrice,
                    'billing_currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
                    'billing_status' => 'invoiced', // Special status for postpaid
                    'invoice_id' => $invoice->invoice_number,
                ]);

                // Create billing transaction record
                BillingTransaction::create([
                    'business_id' => $business->id,
                    'invoice_id' => $invoice->id,
                    'ussd_session_id' => $session->id,
                    'transaction_number' => $this->generateTransactionNumber($business),
                    'type' => BillingTransaction::TYPE_CHARGE,
                    'method' => 'postpaid',
                    'amount' => $sessionPrice,
                    'currency' => $business->billing_currency ?? config('app.currency', 'NGN'),
                    'status' => BillingTransaction::STATUS_PENDING,
                    'description' => "Session invoiced: {$session->phone_number}",
                ]);

                $addedCount++;
            }

            // Recalculate invoice totals
            $this->recalculateInvoiceTotals($invoice);

            DB::commit();

            Log::info('Sessions added to invoice', [
                'invoice_id' => $invoice->id,
                'sessions_added' => $addedCount,
            ]);

            return $addedCount;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add sessions to invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Recalculate invoice totals from items
     */
    public function recalculateInvoiceTotals(Invoice $invoice): void
    {
        $items = $invoice->items;
        
        $subtotal = $items->sum('total_amount');
        $taxAmount = $items->sum('tax_amount');
        $discountAmount = $items->sum('discount_amount');
        $totalAmount = $subtotal;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'balance_due' => $totalAmount - $invoice->paid_amount,
        ]);
    }

    /**
     * Generate invoice for billing cycle
     */
    public function generateBillingCycleInvoice(Business $business, int $createdBy = null): ?Invoice
    {
        if ($business->isPrepaid()) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Check if invoice already exists for this period
            $periodStart = $this->getBillingCycleStart($business);
            $periodEnd = $this->getBillingCycleEnd($business, $periodStart);

            $existingInvoice = Invoice::where('business_id', $business->id)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->first();

            if ($existingInvoice) {
                DB::rollBack();
                return $existingInvoice;
            }

            // Create new invoice
            $invoice = $this->createInvoice($business, $periodStart, $periodEnd, $createdBy);

            // Add unbilled sessions
            $this->addSessionsToInvoice($invoice);

            // Mark invoice as sent
            $invoice->markAsSent();

            DB::commit();

            Log::info('Billing cycle invoice generated', [
                'invoice_id' => $invoice->id,
                'business_id' => $business->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate billing cycle invoice', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Record payment against invoice
     */
    public function recordPayment(
        Invoice $invoice,
        float $amount,
        string $paymentMethod = null,
        string $reference = null
    ): BillingTransaction {
        try {
            DB::beginTransaction();

            // Record payment
            $invoice->recordPayment($amount, $paymentMethod, $reference);

            // Create transaction record
            $transaction = BillingTransaction::create([
                'business_id' => $invoice->business_id,
                'invoice_id' => $invoice->id,
                'transaction_number' => $this->generateTransactionNumber($invoice->business),
                'reference_number' => $reference,
                'type' => BillingTransaction::TYPE_PAYMENT,
                'method' => 'postpaid',
                'amount' => $amount,
                'currency' => $invoice->currency,
                'status' => BillingTransaction::STATUS_COMPLETED,
                'description' => "Payment for invoice {$invoice->invoice_number}",
                'gateway' => $paymentMethod,
                'gateway_transaction_id' => $reference,
            ]);

            // If invoice is fully paid, update session billing status
            if ($invoice->isPaid()) {
                $this->markInvoiceSessionsAsCharged($invoice);
            }

            DB::commit();

            Log::info('Payment recorded for invoice', [
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record payment', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark invoice sessions as charged (when invoice is paid)
     */
    protected function markInvoiceSessionsAsCharged(Invoice $invoice): void
    {
        $items = $invoice->items()->where('item_type', InvoiceItem::TYPE_SESSION)->get();

        foreach ($items as $item) {
            if ($item->ussd_session_id) {
                $item->ussdSession->update([
                    'billing_status' => 'charged',
                    'billed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get billing cycle start date
     */
    protected function getBillingCycleStart(Business $business): Carbon
    {
        $cycle = $business->billing_cycle ?? 'monthly';

        switch ($cycle) {
            case 'daily':
                return today()->startOfDay();
            case 'weekly':
                return now()->startOfWeek();
            case 'monthly':
                return now()->startOfMonth();
            default:
                return now()->startOfMonth();
        }
    }

    /**
     * Get billing cycle end date
     */
    protected function getBillingCycleEnd(Business $business, Carbon $startDate): Carbon
    {
        $cycle = $business->billing_cycle ?? 'monthly';

        switch ($cycle) {
            case 'daily':
                return $startDate->copy()->endOfDay();
            case 'weekly':
                return $startDate->copy()->endOfWeek();
            case 'monthly':
                return $startDate->copy()->endOfMonth();
            default:
                return $startDate->copy()->endOfMonth();
        }
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
     * Update overdue invoices status
     */
    public function updateOverdueInvoices(): int
    {
        $overdueInvoices = Invoice::overdue()
            ->where('status', '!=', Invoice::STATUS_OVERDUE)
            ->get();

        $count = 0;
        foreach ($overdueInvoices as $invoice) {
            $invoice->update(['status' => Invoice::STATUS_OVERDUE]);
            $count++;
        }

        return $count;
    }
}

