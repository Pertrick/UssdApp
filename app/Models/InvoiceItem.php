<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'ussd_session_id',
        'description',
        'details',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'item_type',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Item types
     */
    const TYPE_SESSION = 'session';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_CREDIT = 'credit';
    const TYPE_REFUND = 'refund';

    /**
     * Get the invoice this item belongs to
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the USSD session this item is related to (if applicable)
     */
    public function ussdSession(): BelongsTo
    {
        return $this->belongsTo(USSDSession::class);
    }

    /**
     * Calculate total amount (unit_price * quantity - discount + tax)
     */
    public function calculateTotal(): float
    {
        $subtotal = ($this->unit_price * $this->quantity) - $this->discount_amount;
        return $subtotal + $this->tax_amount;
    }
}

