<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;

    protected $appends = ['icon', 'color', 'time_ago'];

    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    // Helper method to get activity icon
    public function getIconAttribute()
    {
        return match($this->activity_type) {
            'login' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
            'logout' => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
            'ussd_created' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
            'ussd_updated' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            'ussd_deleted' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
            'business_created' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'business_verified' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    // Helper method to get activity color
    public function getColorAttribute()
    {
        return match($this->activity_type) {
            'login' => 'text-green-600 bg-green-100',
            'logout' => 'text-red-600 bg-red-100',
            'ussd_created' => 'text-blue-600 bg-blue-100',
            'ussd_updated' => 'text-yellow-600 bg-yellow-100',
            'ussd_deleted' => 'text-red-600 bg-red-100',
            'business_created' => 'text-purple-600 bg-purple-100',
            'business_verified' => 'text-green-600 bg-green-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    // Helper method to format time ago
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

}
