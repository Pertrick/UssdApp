<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\BusinessRegistrationStatus;
use App\Enums\BusinessType;
use App\Enums\DirectorIdType;

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
