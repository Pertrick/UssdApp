<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\USSD;
use App\Models\FlowStep;
use App\Models\FlowConfig;
use App\Models\ExternalAPIConfiguration;

class DynamicFlowDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing records
        $user = \App\Models\User::first();
        $business = \App\Models\Business::first();
        $environment = \App\Models\Environment::first();
        
        if (!$user || !$business || !$environment) {
            $this->command->error('Required records (User, Business, Environment) not found. Please run other seeders first.');
            return;
        }
        
        // Create a demo USSD service
        $ussd = USSD::firstOrCreate(
            ['pattern' => '*123#'],
            [
                'name' => 'Dynamic Data Bundle Service',
                'description' => 'Demo service for dynamic data bundle purchase',
                'user_id' => $user->id,
                'business_id' => $business->id,
                'environment_id' => $environment->id,
                'is_active' => true,
            ]
        );

        // Create flow configurations
        $this->createFlowConfigs($ussd);

        // Create API configurations for the demo
        $this->createApiConfigurations($ussd);

        // Create flow steps
        $this->createFlowSteps($ussd);

        $this->command->info('Dynamic Flow Demo seeded successfully!');
        $this->command->info("USSD Pattern: {$ussd->pattern}");
    }

    protected function createFlowConfigs(USSD $ussd): void
    {
        $configs = [
            [
                'key' => 'supported_networks',
                'value' => ['MTN', 'Airtel', 'Glo', '9mobile'],
                'description' => 'List of supported mobile networks',
            ],
            [
                'key' => 'transaction_fee',
                'value' => 10,
                'description' => 'Transaction fee in NGN',
            ],
            [
                'key' => 'currency',
                'value' => 'NGN',
                'description' => 'Default currency',
            ],
            [
                'key' => 'max_amount',
                'value' => 10000,
                'description' => 'Maximum transaction amount',
            ],
        ];

        foreach ($configs as $config) {
            FlowConfig::firstOrCreate(
                [
                    'ussd_id' => $ussd->id,
                    'key' => $config['key'],
                ],
                [
                    'value' => $config['value'],
                    'description' => $config['description'],
                    'is_active' => true,
                ]
            );
        }
    }

    protected function createApiConfigurations(USSD $ussd): void
    {
        // API for fetching data bundles
        $bundleApi = ExternalAPIConfiguration::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'name' => 'Data Bundle API',
            ],
            [
                'description' => 'API to fetch available data bundles for a network',
                'category' => 'custom',
                'provider_name' => 'Telco Provider',
                'endpoint_url' => 'https://api.telco.com/{{network}}/bundles',
                'method' => 'GET',
                'timeout' => 30,
                'retry_attempts' => 2,
                'auth_type' => 'api_key',
                'auth_config' => json_encode([
                    'api_key' => '{{API_KEY}}',
                    'header_name' => 'X-API-Key',
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'request_mapping' => [
                    'network' => '{session.data.selected_network}',
                ],
                'response_mapping' => [
                    'bundles' => 'data.bundles',
                    'status' => 'status',
                ],
                'success_criteria' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => 'success',
                    ],
                ],
                'error_handling' => [
                    'fallback_message' => 'Unable to fetch data bundles. Please try again.',
                    'success_message' => 'Data bundles loaded successfully.',
                ],
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success',
                'environment' => 'production',
            ]
        );

        // API for purchasing data bundle
        $purchaseApi = ExternalAPIConfiguration::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'name' => 'Data Purchase API',
            ],
            [
                'description' => 'API to purchase a data bundle',
                'category' => 'custom',
                'provider_name' => 'Telco Provider',
                'endpoint_url' => 'https://api.telco.com/purchase',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 2,
                'auth_type' => 'api_key',
                'auth_config' => json_encode([
                    'api_key' => '{{API_KEY}}',
                    'header_name' => 'X-API-Key',
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'request_mapping' => [
                    'phone' => '{session.phone_number}',
                    'network' => '{session.data.selected_network}',
                    'bundle_id' => '{session.data.selected_bundle}',
                    'amount' => '{session.data.bundle_amount}',
                ],
                'response_mapping' => [
                    'transaction_id' => 'data.transaction_id',
                    'status' => 'status',
                    'message' => 'message',
                ],
                'success_criteria' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => 'success',
                    ],
                ],
                'error_handling' => [
                    'fallback_message' => 'Purchase failed. Please try again.',
                    'success_message' => 'Data bundle purchased successfully! Transaction ID: {transaction_id}',
                ],
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success',
                'environment' => 'production',
            ]
        );
    }

    protected function createFlowSteps(USSD $ussd): void
    {
        // Step 1: Welcome message
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'welcome',
            ],
            [
                'type' => FlowStep::TYPE_MESSAGE,
                'data' => [
                    'title' => 'Welcome to Data Bundle Service',
                    'message' => 'Buy data bundles for all networks. Select your network to continue.',
                ],
                'next_step' => 'select_network',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        // Step 2: Select network
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'select_network',
            ],
            [
                'type' => FlowStep::TYPE_MENU,
                'data' => [
                    'title' => 'Select Network',
                    'prompt' => 'Choose your network:',
                    'options' => [
                        ['label' => 'MTN', 'value' => 'mtn'],
                        ['label' => 'Airtel', 'value' => 'airtel'],
                        ['label' => 'Glo', 'value' => 'glo'],
                        ['label' => '9mobile', 'value' => '9mobile'],
                    ],
                ],
                'next_step' => 'fetch_bundles',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        // Step 3: Fetch data bundles from API
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'fetch_bundles',
            ],
            [
                'type' => FlowStep::TYPE_API_CALL,
                'data' => [
                    'api_config_id' => ExternalAPIConfiguration::where('ussd_id', $ussd->id)
                        ->where('name', 'Data Bundle API')
                        ->first()
                        ->id,
                    'store_as' => 'bundles_response',
                ],
                'next_step' => 'select_bundle',
                'sort_order' => 3,
                'is_active' => true,
            ]
        );

        // Step 4: Select data bundle (dynamic menu)
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'select_bundle',
            ],
            [
                'type' => FlowStep::TYPE_DYNAMIC_MENU,
                'data' => [
                    'title' => 'Select Data Bundle',
                    'prompt' => 'Choose a data bundle:',
                    'source' => 'api.bundles_response.bundles',
                    'list_path' => null, // bundles_response.bundles is already the list
                    'label_field' => 'name',
                    'value_field' => 'id',
                    'empty_message' => 'No bundles available for this network.',
                ],
                'next_step' => 'confirm_purchase',
                'sort_order' => 4,
                'is_active' => true,
            ]
        );

        // Step 5: Confirm purchase
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'confirm_purchase',
            ],
            [
                'type' => FlowStep::TYPE_MENU,
                'data' => [
                    'title' => 'Confirm Purchase',
                    'prompt' => 'Confirm your purchase:',
                    'options' => [
                        ['label' => 'Yes, Purchase', 'value' => 'yes'],
                        ['label' => 'No, Cancel', 'value' => 'no'],
                    ],
                ],
                'next_step' => 'process_purchase',
                'sort_order' => 5,
                'is_active' => true,
            ]
        );

        // Step 6: Process purchase (API call)
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'process_purchase',
            ],
            [
                'type' => FlowStep::TYPE_API_CALL,
                'data' => [
                    'api_config_id' => ExternalAPIConfiguration::where('ussd_id', $ussd->id)
                        ->where('name', 'Data Purchase API')
                        ->first()
                        ->id,
                    'store_as' => 'purchase_response',
                ],
                'next_step' => 'show_result',
                'sort_order' => 6,
                'is_active' => true,
                'conditions' => [
                    [
                        'field' => 'session.data.last_user_input',
                        'operator' => 'equals',
                        'value' => 'yes',
                    ],
                ],
            ]
        );

        // Step 7: Show result
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'show_result',
            ],
            [
                'type' => FlowStep::TYPE_MESSAGE,
                'data' => [
                    'title' => 'Purchase Result',
                    'message' => '{{api.purchase_response.message}}',
                ],
                'next_step' => null, // End of flow
                'sort_order' => 7,
                'is_active' => true,
            ]
        );
    }
}