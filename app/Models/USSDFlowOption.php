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

    /**
     * Get the action_data attribute as an array (for easier access)
     */
    public function getActionDataAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        $decoded = json_decode($value, true);
        
        // Return as array to preserve boolean values and make access easier
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set the action_data attribute
     */
    public function setActionDataAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['action_data'] = null;
        } else {
            $this->attributes['action_data'] = json_encode($value);
        }
    }
}
