<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class USSDFlow extends Model
{
    use HasFactory;
    protected $table = 'ussd_flows';

    protected $fillable = [
        'ussd_id',
        'name',
        'description',
        'menu_text',
        'is_root',
        'parent_flow_id',
        'sort_order',
        'flow_config',
        'is_active',
    ];

    protected $casts = [
        'is_root' => 'boolean',
        'is_active' => 'boolean',
        'flow_config' => 'array',
    ];

    public function ussd(): BelongsTo
    {
        return $this->belongsTo(USSD::class);
    }

    public function parentFlow(): BelongsTo
    {
        return $this->belongsTo(USSDFlow::class, 'parent_flow_id');
    }

    public function childFlows(): HasMany
    {
        return $this->hasMany(USSDFlow::class, 'parent_flow_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(USSDFlowOption::class, 'flow_id');
    }
}
