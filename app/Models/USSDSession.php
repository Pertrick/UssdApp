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
        'session_id',
        'phone_number',
        'current_flow_id',
        'user_input',
        'session_data',
        'status',
        'step_count',
        'last_activity',
        'expires_at',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'session_data' => 'array',
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ussd(): BelongsTo
    {
        return $this->belongsTo(USSD::class);
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
