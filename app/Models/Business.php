<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
