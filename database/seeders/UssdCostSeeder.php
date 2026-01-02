<?php

namespace Database\Seeders;

use App\Models\UssdCost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UssdCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get default costs from config
        $defaultCost = config('services.africastalking.cost_per_session.default', 3.0);
        $costs = config('services.africastalking.cost_per_session', []);
        $currency = config('services.africastalking.cost_currency', 'NGN');
        $country = 'NG'; // Nigeria
        
        // Convert to smallest unit (kobo for NGN)
        $conversionFactor = 100;
        
        // Networks to seed (Nigeria networks)
        $networks = [
            'MTN' => $costs['mtn'] ?? $defaultCost,
            'Airtel' => $costs['airtel'] ?? $defaultCost,
            'Glo' => $costs['glo'] ?? $defaultCost,
            '9mobile' => $costs['9mobile'] ?? $defaultCost,
            'default' => $defaultCost, // Fallback for unknown networks
        ];
        
        // Check if data already exists
        if (UssdCost::where('country', $country)->exists()) {
            $this->command->info('USSD costs already exist. Skipping seed.');
            return;
        }
        
        // Seed network costs
        foreach ($networks as $network => $costInMainCurrency) {
            $costInSmallestUnit = (int) round((float) $costInMainCurrency * $conversionFactor);
            
            UssdCost::create([
                'country' => $country,
                'network' => $network,
                'cost_per_session' => $costInSmallestUnit,
                'currency' => $currency,
                'effective_from' => now()->toDateString(),
                'is_active' => true,
            ]);
            
            $this->command->info("Seeded cost for {$network}: {$costInMainCurrency} {$currency} ({$costInSmallestUnit} kobo)");
        }
        
        $this->command->info('USSD costs seeded successfully!');
        $this->command->warn('Note: Update these costs based on actual AfricasTalking pricing from your dashboard.');
    }
}
