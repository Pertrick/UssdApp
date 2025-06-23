<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\USSD;
use App\Models\User;
use App\Models\Business;

class USSDSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users and businesses
        $users = User::all();
        $businesses = Business::all();

        if ($users->isEmpty() || $businesses->isEmpty()) {
            $this->command->info('No users or businesses found. Please run UserSeeder and BusinessSeeder first.');
            return;
        }

        // Create sample USSD services
        $sampleUSSDs = [
            [
                'name' => 'Bank Balance Check',
                'description' => 'Check your account balance and recent transactions via USSD.',
                'pattern' => '123#',
                'is_active' => true,
            ],
            [
                'name' => 'Airtime Purchase',
                'description' => 'Purchase airtime for yourself or others using USSD.',
                'pattern' => '456#',
                'is_active' => true,
            ],
            [
                'name' => 'Bill Payment',
                'description' => 'Pay utility bills and other services through USSD.',
                'pattern' => '789#',
                'is_active' => false,
            ],
            [
                'name' => 'Money Transfer',
                'description' => 'Send money to other users or bank accounts via USSD.',
                'pattern' => '321#',
                'is_active' => true,
            ],
            [
                'name' => 'Customer Support',
                'description' => 'Access customer support and help services through USSD.',
                'pattern' => '654#',
                'is_active' => true,
            ],
        ];

        foreach ($sampleUSSDs as $ussdData) {
            // Assign to a random user and business
            $user = $users->random();
            $business = $businesses->where('user_id', $user->id)->first() ?? $businesses->random();

            USSD::create([
                'name' => $ussdData['name'],
                'description' => $ussdData['description'],
                'pattern' => $ussdData['pattern'],
                'user_id' => $user->id,
                'business_id' => $business->id,
                'is_active' => $ussdData['is_active'],
            ]);
        }

        $this->command->info('Sample USSD services created successfully!');
    }
}
