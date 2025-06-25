<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class USSDFlowOption extends Model
{
    use HasFactory;
    protected $table = 'ussd_flow_options';
    protected $fillable = [
        'flow_id',
        'option_text',
        'option_value',
        'next_flow_id',
        'action_type',
        'action_data',
        'requires_input',
        'input_validation',
        'input_prompt',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'action_data' => 'array',
        'requires_input' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(USSDFlow::class, 'flow_id');
    }

    public function nextFlow(): BelongsTo
    {
        return $this->belongsTo(USSDFlow::class, 'next_flow_id');
    }
}
