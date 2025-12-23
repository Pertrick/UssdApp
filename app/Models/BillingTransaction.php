<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'invoice_id',
        'ussd_session_id',
        'transaction_number',
        'reference_number',
        'type',
        'method',
        'amount',
        'currency',
        'status',
        'description',
        'failure_reason',
        'balance_before',
        'balance_after',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'gateway_response' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Transaction types
     */
    const TYPE_CHARGE = 'charge';
    const TYPE_PAYMENT = 'payment';
    const TYPE_REFUND = 'refund';
    const TYPE_CREDIT = 'credit';
    const TYPE_ADJUSTMENT = 'adjustment';

    /**
     * Transaction statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Get the business this transaction belongs to
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the invoice this transaction is related to (if applicable)
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the USSD session this transaction is related to (if applicable)
     */
    public function ussdSession(): BelongsTo
    {
        return $this->belongsTo(USSDSession::class);
    }

    /**
     * Get the user who created this transaction
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Scope to get completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}

