<?php

namespace Database\Seeders;

use App\Models\Environment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EnvironmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $environments = [
            [
                'name' => 'testing',
                'label' => 'Testing',
                'description' => 'Real API calls in test/sandbox mode (for integration testing)',
                'color' => 'yellow',
                'allows_real_api_calls' => true,
                'is_default' => true,
                'is_active' => true,
                'settings' => [
                    'use_sandbox_endpoints' => true,
                    'log_all_calls' => true,
                    'validate_responses' => true,
                ],
            ],
            [
                'name' => 'production',
                'label' => 'Production',
                'description' => 'Real API calls in live mode (for production use)',
                'color' => 'green',
                'allows_real_api_calls' => true,
                'is_default' => false,
                'is_active' => true,
                'settings' => [
                    'use_live_endpoints' => true,
                    'log_all_calls' => true,
                    'monitor_performance' => true,
                ],
            ],
        ];

        foreach ($environments as $environment) {
            Environment::updateOrCreate(
                ['name' => $environment['name']],
                $environment
            );
        }
    }
}
