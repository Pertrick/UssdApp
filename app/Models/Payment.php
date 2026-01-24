<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'amount',
        'currency',
        'gateway',
        'status', // pending, completed, failed, cancelled
        'reference',
        'metadata',
        'gateway_response',
        'completed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'metadata' => 'array',
        'gateway_response' => 'array',
        'completed_at' => 'datetime'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_amount',
        'gateway_name',
        'status_class'
    ];

    /**
     * Get the business that owns the payment
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the user who made the payment
     */
    public function user()
    {
        return $this->business->user;
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $currency = $this->currency ?? 'USD';
        $amount = $this->amount ?? 0;
        return $currency . ' ' . number_format($amount, 2);
    }

    /**
     * Get gateway display name
     */
    public function getGatewayNameAttribute(): string
    {
        if (!$this->gateway) {
            return 'N/A';
        }

        $gateways = [
            'stripe' => 'Credit/Debit Card',
            'paypal' => 'PayPal',
            'flutterwave' => 'Flutterwave',
            'paystack' => 'Paystack',
            'manual' => 'Bank Transfer'
        ];

        return $gateways[$this->gateway] ?? ucfirst($this->gateway);
    }

    /**
     * Get status badge class
     */
    public function getStatusClassAttribute(): string
    {
        switch ($this->status) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'failed':
                return 'bg-red-100 text-red-800';
            case 'cancelled':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
}
