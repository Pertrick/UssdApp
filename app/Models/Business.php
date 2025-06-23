<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'registration_date' => 'date',
        'is_primary' => 'boolean',
    ];

    /**
     * Get the user that owns this business.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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
}
