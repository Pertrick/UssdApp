<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class USSDSessionLog extends Model
{
    use HasFactory;
    protected $table = 'ussd_session_logs'; 
    protected $fillable = [
        'session_id',
        'ussd_id',
        'flow_id',
        'flow_option_id',
        'action_type',
        'input_data',
        'output_data',
        'response_time',
        'status',
        'error_message',
        'metadata',
        'action_timestamp',
    ];

    protected $casts = [
        'metadata' => 'array',
        'action_timestamp' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(USSDSession::class, 'session_id');
    }

    public function ussd(): BelongsTo
    {
        return $this->belongsTo(USSD::class);
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(USSDFlow::class, 'flow_id');
    }

    public function flowOption(): BelongsTo
    {
        return $this->belongsTo(USSDFlowOption::class, 'flow_option_id');
    }
}
