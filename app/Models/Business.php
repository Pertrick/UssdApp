<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\BusinessRegistrationStatus;
use App\Enums\BusinessType;
use App\Enums\DirectorIdType;
use App\Enums\BillingMethod;

class Business extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'business_email',
        'phone',
        'state',
        'city',
        'address',
        'cac_number',
        'cac_document_path',
        'registration_date',
        'business_type',
        'director_name',
        'director_email',
        'director_phone',
        'director_id_type',
        'director_id_number',
        'director_id_path',
        'registration_status',
        'verified',
        'verified_at',
        'rejection_reason',
        'approval_notes',
        'suspension_reason',
        'is_primary',
        // Billing fields
        'account_balance',
        'test_balance',
        'billing_currency',
        'session_price',
        'billing_enabled',
        'billing_method',
        'credit_limit',
        'payment_terms_days',
        'billing_cycle',
        'billing_change_request',
        'billing_change_reason',
        'billing_change_requested_at',
        'account_suspended',
        'suspension_reason',
        'suspended_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'registration_date' => 'date',
        'verified_at' => 'datetime',
        'verified' => 'boolean',
        'is_primary' => 'boolean',
        'registration_status' => BusinessRegistrationStatus::class,
        'business_type' => BusinessType::class,
        'director_id_type' => DirectorIdType::class,
        // Billing casts
        'account_balance' => 'decimal:4',
        'test_balance' => 'decimal:4',
        'session_price' => 'decimal:4',
        'billing_enabled' => 'boolean',
        'billing_method' => BillingMethod::class,
        'credit_limit' => 'decimal:4',
        'payment_terms_days' => 'integer',
        'billing_change_requested_at' => 'datetime',
        'account_suspended' => 'boolean',
        'suspended_at' => 'datetime',
    ];

    /**
     * Get the user that owns this business.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the USSDs owned by this business.
     */
    public function ussds(): HasMany
    {
        return $this->hasMany(USSD::class);
    }

    /**
     * Get the payments made by this business.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all invoices for this business (postpaid)
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all billing transactions for this business
     */
    public function billingTransactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class);
    }

    /**
     * Get all billing change requests for this business
     */
    public function billingChangeRequests(): HasMany
    {
        return $this->hasMany(BillingChangeRequest::class);
    }

    /**
     * Get pending billing change request
     */
    public function pendingBillingChangeRequest(): ?BillingChangeRequest
    {
        return $this->billingChangeRequests()
            ->where('status', BillingChangeRequest::STATUS_PENDING)
            ->latest()
            ->first();
    }

    /**
     * Check if business uses prepaid billing
     */
    public function isPrepaid(): bool
    {
        return $this->billing_method === BillingMethod::PREPAID;
    }

    /**
     * Check if business uses postpaid billing
     */
    public function isPostpaid(): bool
    {
        return $this->billing_method === BillingMethod::POSTPAID;
    }

    /**
     * Get total outstanding balance (unpaid invoices)
     */
    public function getOutstandingBalance(): float
    {
        if ($this->isPrepaid()) {
            return 0;
        }

        return $this->invoices()
            ->unpaid()
            ->sum('balance_due') ?? 0;
    }

    /**
     * Get available credit (credit_limit - outstanding_balance)
     */
    public function getAvailableCredit(): float
    {
        if ($this->isPrepaid()) {
            return 0;
        }

        $creditLimit = $this->credit_limit ?? 0;
        $outstanding = $this->getOutstandingBalance();

        return max(0, $creditLimit - $outstanding);
    }

    /**
     * Check if business has exceeded credit limit
     */
    public function hasExceededCreditLimit(): bool
    {
        if ($this->isPrepaid()) {
            return false;
        }

        return $this->getOutstandingBalance() > ($this->credit_limit ?? 0);
    }

    /**
     * Check if account is suspended
     */
    public function isAccountSuspended(): bool
    {
        return $this->account_suspended === true;
    }

    /**
     * Suspend account
     */
    public function suspendAccount(string $reason): void
    {
        $this->update([
            'account_suspended' => true,
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);
    }

    /**
     * Unsuspend account
     */
    public function unsuspendAccount(): void
    {
        $this->update([
            'account_suspended' => false,
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);
    }

    /**
     * Scope to get primary business.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Check if this is the primary business.
     */
    public function isPrimary()
    {
        return $this->is_primary;
    }

    /**
     * Check if the business is verified.
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * Mark the business as verified.
     */
    public function markAsVerified()
    {
        $this->update([
            'verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Mark the business as unverified.
     */
    public function markAsUnverified()
    {
        $this->update([
            'verified' => false,
            'verified_at' => null,
        ]);
    }

    /**
     * Scope to get verified businesses.
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope to get unverified businesses.
     */
    public function scopeUnverified($query)
    {
        return $query->where('verified', false);
    }

    /**
     * Check if business registration is pending
     */
    public function isRegistrationPending(): bool
    {
        return $this->registration_status->isPending();
    }

    /**
     * Check if business registration is completed
     */
    public function isRegistrationCompleted(): bool
    {
        return $this->registration_status->isCompleted();
    }

    /**
     * Check if business registration is verified
     */
    public function isRegistrationVerified(): bool
    {
        return $this->registration_status->isVerified();
    }

    /**
     * Check if business registration is rejected
     */
    public function isRegistrationRejected(): bool
    {
        return $this->registration_status->isRejected();
    }

    /**
     * Get the next registration status
     */
    public function getNextRegistrationStatus(): ?BusinessRegistrationStatus
    {
        return $this->registration_status->nextStatus();
    }

    /**
     * Move to next registration status
     */
    public function moveToNextRegistrationStatus(): bool
    {
        $nextStatus = $this->getNextRegistrationStatus();
        if ($nextStatus) {
            $this->update(['registration_status' => $nextStatus]);
            return true;
        }
        return false;
    }

    /**
     * Check if business type requires multiple owners
     */
    public function requiresMultipleOwners(): bool
    {
        return $this->business_type?->requiresMultipleOwners() ?? false;
    }

    /**
     * Check if business type has limited liability
     */
    public function hasLimitedLiability(): bool
    {
        return $this->business_type?->hasLimitedLiability() ?? false;
    }

    /**
     * Get business type display name
     */
    public function getBusinessTypeDisplayName(): string
    {
        return $this->business_type?->displayName() ?? 'Not specified';
    }

    /**
     * Get director ID type display name
     */
    public function getDirectorIdTypeDisplayName(): string
    {
        return $this->director_id_type?->displayName() ?? 'Not specified';
    }

    /**
     * Check if director ID is internationally recognized
     */
    public function isDirectorIdInternationallyRecognized(): bool
    {
        return $this->director_id_type?->isInternationallyRecognized() ?? false;
    }
}
