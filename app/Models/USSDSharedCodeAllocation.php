<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class USSDSharedCodeAllocation extends Model
{
    protected $table = 'ussd_shared_code_allocations';

    protected $fillable = [
        'gateway_ussd_id',
        'option_value',
        'target_ussd_id',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function gatewayUssd(): BelongsTo
    {
        return $this->belongsTo(USSD::class, 'gateway_ussd_id');
    }

    public function targetUssd(): BelongsTo
    {
        return $this->belongsTo(USSD::class, 'target_ussd_id');
    }
}
