<?php

namespace Database\Seeders;

use App\Models\NetworkPricing;
use Illuminate\Database\Seeder;

class NetworkPricingSeeder extends Seeder
{
    public function run(): void
    {
        $country = 'NG';
        $currency = 'NGN';
        $defaultMarkup = 50.0; // 50% markup

        $networks = ['MTN', 'Airtel', 'Glo', '9mobile'];

        foreach ($networks as $network) {
            NetworkPricing::updateOrCreate(
                [
                    'country' => $country,
                    'network' => $network,
                ],
                [
                    'markup_percentage' => $defaultMarkup,
                    'minimum_price' => null, // No minimum by default
                    'currency' => $currency,
                    'is_active' => true,
                ]
            );
            
            $this->command->info("Seeded network pricing for {$network}: {$defaultMarkup}% markup");
        }
        
        $this->command->info('Network pricing seeded successfully!');
    }
}
