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
     * Get the root flow for this USSD.
     */
    public function rootFlow()
    {
        return $this->flows()->where('is_root', true)->first();
    }

    /**
     * Create a default root flow for this USSD.
     */
    public function createDefaultRootFlow()
    {
        $rootFlow = $this->flows()->create([
            'name' => 'Main Menu',
            'description' => 'Main menu for ' . $this->name,
            'menu_text' => "Welcome to {$this->name}\n\n1. Option 1\n2. Option 2\n3. Option 3\n0. Exit",
            'is_root' => true,
            'parent_flow_id' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Create default options for the root flow
        $this->createDefaultRootFlowOptions($rootFlow);

        return $rootFlow;
    }

    /**
     * Create default options for the root flow.
     */
    private function createDefaultRootFlowOptions(USSDFlow $rootFlow)
    {
        $defaultOptions = [
            [
                'option_text' => 'Option 1',
                'option_value' => '1',
                'action_type' => 'message',
                'action_data' => ['message' => 'You selected Option 1. This is a placeholder message.'],
                'requires_input' => false,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'option_text' => 'Option 2',
                'option_value' => '2',
                'action_type' => 'message',
                'action_data' => ['message' => 'You selected Option 2. This is a placeholder message.'],
                'requires_input' => false,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'option_text' => 'Option 3',
                'option_value' => '3',
                'action_type' => 'message',
                'action_data' => ['message' => 'You selected Option 3. This is a placeholder message.'],
                'requires_input' => false,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'option_text' => 'Exit',
                'option_value' => '0',
                'action_type' => 'end_session',
                'action_data' => ['message' => 'Thank you for using our service.'],
                'requires_input' => false,
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($defaultOptions as $option) {
            $rootFlow->options()->create($option);
        }
    }

    /**
     * Ensure USSD has a root flow, create one if it doesn't exist.
     */
    public function ensureRootFlow()
    {
        if (!$this->rootFlow()) {
            return $this->createDefaultRootFlow();
        }
        
        return $this->rootFlow();
    }

    /**
     * Check if USSD has any flows.
     */
    public function hasFlows()
    {
        return $this->flows()->exists();
    }

    /**
     * Get the first flow (usually root flow) for this USSD.
     */
    public function firstFlow()
    {
        return $this->flows()->orderBy('sort_order')->first();
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