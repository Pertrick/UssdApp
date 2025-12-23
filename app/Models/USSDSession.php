<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class USSDSession extends Model
{
    use HasFactory;
    protected $table = 'ussd_sessions';
    protected $fillable = [
        'ussd_id',
        'environment_id',
        'session_id',
        'phone_number',
        'current_flow_id',
        'status',
        'start_time',
        'end_time',
        'total_inputs',
        'collected_data',
        'error_message',
        'gateway_provider',
        'last_activity',
        'expires_at',
        'user_agent',
        'ip_address',
        'step_count',
        'session_data',
        // Billing fields
        'is_billed',
        'billing_amount',
        'billing_currency',
        'billing_status', // pending, charged, failed, refunded
        'billed_at',
        'invoice_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'collected_data' => 'array',
        'session_data' => 'array',
        'is_billed' => 'boolean',
        'billing_amount' => 'decimal:4',
        'billed_at' => 'datetime',
    ];

    public function ussd(): BelongsTo
    {
        return $this->belongsTo(USSD::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function currentFlow(): BelongsTo
    {
        return $this->belongsTo(USSDFlow::class, 'current_flow_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(USSDSessionLog::class, 'session_id');
    }
}
