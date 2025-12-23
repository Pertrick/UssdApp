<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'invoice_number',
        'reference_number',
        'period_start',
        'period_end',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'currency',
        'status',
        'issue_date',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_reference',
        'payment_notes',
        'notes',
        'terms',
        'metadata',
        'created_by',
        'sent_at',
        'reminder_sent_at',
        'reminder_count',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'balance_due' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Invoice statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Get the business that owns this invoice
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the user who created this invoice
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all items for this invoice
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get all transactions related to this invoice
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class);
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->balance_due <= 0;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
               ($this->due_date < today() && $this->balance_due > 0 && !$this->isPaid());
    }

    /**
     * Check if invoice is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->paid_amount > 0 && $this->balance_due > 0;
    }

    /**
     * Calculate days overdue
     */
    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return max(0, today()->diffInDays($this->due_date));
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Record payment
     */
    public function recordPayment(float $amount, string $paymentMethod = null, string $reference = null): void
    {
        $this->increment('paid_amount', $amount);
        $this->decrement('balance_due', $amount);

        if ($paymentMethod) {
            $this->update(['payment_method' => $paymentMethod]);
        }

        if ($reference) {
            $this->update(['payment_reference' => $reference]);
        }

        // Update status based on payment
        if ($this->balance_due <= 0) {
            $this->update([
                'status' => self::STATUS_PAID,
                'paid_at' => now(),
            ]);
        } elseif ($this->paid_amount > 0) {
            $this->update(['status' => self::STATUS_PARTIALLY_PAID]);
        }

        // Check if overdue
        if ($this->isOverdue() && $this->status !== self::STATUS_OVERDUE) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        }
    }

    /**
     * Scope to get overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', today())
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    /**
     * Scope to get unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->where('balance_due', '>', 0)
            ->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    /**
     * Scope to get paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID)
            ->orWhere('balance_due', '<=', 0);
    }
}

