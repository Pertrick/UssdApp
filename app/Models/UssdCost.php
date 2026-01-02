<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UssdCost extends Model
{
    protected $fillable = [
        'country',
        'network',
        'cost_per_session',
        'currency',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'cost_per_session' => 'integer',
        'is_active' => 'boolean',
        'effective_from' => 'date',
    ];

    public static function getActiveCost(string $country, string $network): ?self
    {
        // Get the most recent active cost that's effective
        return self::where('country', $country)
            ->where('network', $network)
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->first();
    }
}
