<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class USSD extends Model
{
    use HasFactory;
    protected $table = 'ussds';

    protected $fillable = [
        'name',
        'description',
        'pattern',
        'user_id',
        'business_id',
        'is_active',
        'environment', // 'testing' or 'live'
        'gateway_provider', // 'africastalking', 'hubtel', 'twilio', etc.
        'gateway_credentials', // JSON encrypted credentials
        'monetization_enabled',
        'pricing_model', // 'per_session', 'per_transaction', 'subscription'
        'session_price', // Price per session in kobo
        'transaction_price', // Price per transaction in kobo
        'subscription_price', // Monthly subscription price
        'webhook_url',
        'callback_url',
        'live_ussd_code', // The actual USSD code for live environment
        'testing_ussd_code', // USSD code for testing
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monetization_enabled' => 'boolean',
        'gateway_credentials' => 'encrypted:array',
        'session_price' => 'decimal:2',
        'transaction_price' => 'decimal:2',
        'subscription_price' => 'decimal:2',
    ];

    /**
     * Get the user that owns the USSD.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business that owns the USSD.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }


     /**
     * Get the sessions for this USSD.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(USSDSession::class, 'ussd_id');
    }

    /**
     * Get the flows for this USSD.
     */
    public function flows(): HasMany
    {
        return $this->hasMany(USSDFlow::class, 'ussd_id');
    }

    
    /**
     * Validation rules for USSD creation/update
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'pattern' => 'required|string|max:50|unique:ussds,pattern,' . request()->route('ussd'),
        ];
    }

    /**
     * Custom validation messages
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'USSD name is required.',
            'name.max' => 'USSD name cannot exceed 255 characters.',
            'description.required' => 'USSD description is required.',
            'description.max' => 'USSD description cannot exceed 1000 characters.',
            'pattern.required' => 'USSD pattern is required.',
            'pattern.max' => 'USSD pattern cannot exceed 50 characters.',
            'pattern.unique' => 'This USSD pattern is already in use.',
        ];
    }

    // Helper methods for environment management
    public function isLive(): bool
    {
        return $this->environment === 'live';
    }

    public function isTesting(): bool
    {
        return $this->environment === 'testing';
    }

    public function getCurrentUssdCode(): string
    {
        return $this->isLive() ? $this->live_ussd_code : $this->testing_ussd_code;
    }

    public function canSwitchToLive(): bool
    {
        return $this->business && 
               $this->business->registration_status === 'verified' &&
               $this->gateway_provider &&
               $this->gateway_credentials &&
               $this->webhook_url;
    }

    public function getPricingInfo(): array
    {
        return [
            'model' => $this->pricing_model,
            'session_price' => $this->session_price,
            'transaction_price' => $this->transaction_price,
            'subscription_price' => $this->subscription_price,
            'enabled' => $this->monetization_enabled,
        ];
    }
} 