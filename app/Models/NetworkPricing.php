<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkPricing extends Model
{
    use HasFactory;

    protected $table = 'network_pricing';

    protected $fillable = [
        'country',
        'network',
        'markup_percentage',
        'minimum_price',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'markup_percentage' => 'decimal:2',
        'minimum_price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get active pricing for a network
     */
    public static function getActivePricing(string $country, string $network): ?self
    {
        return self::where('country', $country)
            ->where('network', $network)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Calculate final price based on AT cost and markup
     * 
     * @param float $atCost AT cost per session
     * @return float Final price to charge customer
     */
    public function calculatePrice(float $atCost): float
    {
        // Calculate: Price = AT Cost × (1 + Markup%)
        $price = $atCost * (1 + ($this->markup_percentage / 100));
        
        // Apply minimum price if set (ensures profitability)
        if ($this->minimum_price && $this->minimum_price > $price) {
            $price = $this->minimum_price;
        }
        
        return round($price, 4);
    }
}
