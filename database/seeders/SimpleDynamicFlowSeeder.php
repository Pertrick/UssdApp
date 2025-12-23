<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\USSD;
use App\Models\FlowStep;
use App\Models\FlowConfig;
use App\Models\ExternalAPIConfiguration;

class SimpleDynamicFlowSeeder extends Seeder
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
        
        // Create a simple demo USSD service
        $ussd = USSD::firstOrCreate(
            ['pattern' => '*999#'],
            [
                'name' => 'Simple Dynamic Flow Demo',
                'description' => 'A simple demo to test dynamic flow features',
                'user_id' => $user->id,
                'business_id' => $business->id,
                'environment_id' => $environment->id,
                'is_active' => true,
            ]
        );

        // Create simple flow configurations
        $this->createSimpleConfigs($ussd);

        // Create a simple API configuration for demo
        $this->createSimpleApi($ussd);

        // Create simple flow steps
        $this->createSimpleSteps($ussd);

        $this->command->info('Simple Dynamic Flow Demo seeded successfully!');
        $this->command->info("USSD Pattern: {$ussd->pattern}");
        $this->command->info("Test this in your simulator!");
    }

    protected function createSimpleConfigs(USSD $ussd): void
    {
        $configs = [
            [
                'key' => 'welcome_message',
                'value' => 'Welcome to our dynamic flow demo!',
                'description' => 'Welcome message for users',
            ],
            [
                'key' => 'currency',
                'value' => 'NGN',
                'description' => 'Default currency',
            ],
            [
                'key' => 'demo_data',
                'value' => [
                    ['name' => 'Option A', 'value' => 'a', 'price' => '100'],
                    ['name' => 'Option B', 'value' => 'b', 'price' => '200'],
                    ['name' => 'Option C', 'value' => 'c', 'price' => '300'],
                ],
                'description' => 'Demo data for dynamic menu',
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

    protected function createSimpleApi(USSD $ussd): void
    {
        // Simple API that returns demo data
        $api = ExternalAPIConfiguration::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'name' => 'Demo Data API',
            ],
            [
                'description' => 'Simple API that returns demo data',
                'category' => 'custom',
                'provider_name' => 'Demo Provider',
                'endpoint_url' => 'https://jsonplaceholder.typicode.com/posts/1',
                'method' => 'GET',
                'timeout' => 30,
                'retry_attempts' => 2,
                'auth_type' => 'none',
                'auth_config' => json_encode([]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'request_mapping' => [],
                'response_mapping' => [
                    'title' => 'title',
                    'body' => 'body',
                ],
                'success_criteria' => [
                    [
                        'field' => 'id',
                        'operator' => 'exists',
                        'value' => true,
                    ],
                ],
                'error_handling' => [
                    'fallback_message' => 'Demo API call failed.',
                    'success_message' => 'Demo data loaded successfully!',
                ],
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success',
                'environment' => 'production',
            ]
        );
    }

    protected function createSimpleSteps(USSD $ussd): void
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
                    'title' => '{{config.welcome_message}}',
                    'message' => 'This is a simple dynamic flow demo. Let\'s test the features!',
                ],
                'next_step' => 'main_menu',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        // Step 2: Main menu
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'main_menu',
            ],
            [
                'type' => FlowStep::TYPE_MENU,
                'data' => [
                    'title' => 'Main Menu',
                    'prompt' => 'Choose an option:',
                    'options' => [
                        ['label' => 'Test Dynamic Menu', 'value' => 'dynamic'],
                        ['label' => 'Test API Call', 'value' => 'api'],
                        ['label' => 'Test Input Collection', 'value' => 'input'],
                        ['label' => 'Exit', 'value' => 'exit'],
                    ],
                ],
                'next_step' => 'handle_menu_choice',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        // Step 3: Handle menu choice (condition step)
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'handle_menu_choice',
            ],
            [
                'type' => FlowStep::TYPE_CONDITION,
                'data' => [
                    'conditions' => [
                        [
                            'field' => 'session.data.last_user_input',
                            'operator' => 'equals',
                            'value' => 'dynamic',
                        ],
                    ],
                ],
                'next_step' => 'dynamic_menu_demo',
                'sort_order' => 3,
                'is_active' => true,
            ]
        );

        // Step 4: Dynamic menu demo
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'dynamic_menu_demo',
            ],
            [
                'type' => FlowStep::TYPE_DYNAMIC_MENU,
                'data' => [
                    'title' => 'Dynamic Menu Demo',
                    'prompt' => 'Choose from dynamic options:',
                    'source' => 'config.demo_data',
                    'list_path' => null,
                    'label_field' => 'name',
                    'value_field' => 'value',
                    'empty_message' => 'No options available.',
                ],
                'next_step' => 'show_selection',
                'sort_order' => 4,
                'is_active' => true,
            ]
        );

        // Step 5: API call demo
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'api_call_demo',
            ],
            [
                'type' => FlowStep::TYPE_API_CALL,
                'data' => [
                    'api_config_id' => ExternalAPIConfiguration::where('ussd_id', $ussd->id)
                        ->where('name', 'Demo Data API')
                        ->first()
                        ->id,
                    'store_as' => 'api_response',
                ],
                'next_step' => 'show_api_result',
                'sort_order' => 5,
                'is_active' => true,
            ]
        );

        // Step 6: Input collection demo
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'input_demo',
            ],
            [
                'type' => FlowStep::TYPE_INPUT,
                'data' => [
                    'prompt' => 'Enter your name:',
                    'store_as' => 'user_name',
                    'validation' => [
                        [
                            'type' => 'required',
                            'message' => 'Name is required',
                        ],
                        [
                            'type' => 'min_length',
                            'min' => 2,
                            'message' => 'Name must be at least 2 characters',
                        ],
                    ],
                    'success_message' => 'Name saved successfully!',
                ],
                'next_step' => 'show_input_result',
                'sort_order' => 6,
                'is_active' => true,
            ]
        );

        // Step 7: Show selection result
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'show_selection',
            ],
            [
                'type' => FlowStep::TYPE_MESSAGE,
                'data' => [
                    'title' => 'Selection Result',
                    'message' => 'You selected: {{session.data.last_user_input}}\nThis demonstrates dynamic menu generation!',
                ],
                'next_step' => 'main_menu',
                'sort_order' => 7,
                'is_active' => true,
            ]
        );

        // Step 8: Show API result
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'show_api_result',
            ],
            [
                'type' => FlowStep::TYPE_MESSAGE,
                'data' => [
                    'title' => 'API Call Result',
                    'message' => 'API Response:\nTitle: {{api.api_response.title}}\nBody: {{api.api_response.body}}',
                ],
                'next_step' => 'main_menu',
                'sort_order' => 8,
                'is_active' => true,
            ]
        );

        // Step 9: Show input result
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'show_input_result',
            ],
            [
                'type' => FlowStep::TYPE_MESSAGE,
                'data' => [
                    'title' => 'Input Result',
                    'message' => 'Hello {{session.data.user_name}}!\nThis demonstrates input collection and validation.',
                ],
                'next_step' => 'main_menu',
                'sort_order' => 9,
                'is_active' => true,
            ]
        );

        // Step 10: Exit
        FlowStep::firstOrCreate(
            [
                'ussd_id' => $ussd->id,
                'step_id' => 'exit',
            ],
            [
                'type' => FlowStep::TYPE_MESSAGE,
                'data' => [
                    'title' => 'Thank You!',
                    'message' => 'Thanks for testing the dynamic flow demo!\nGoodbye!',
                ],
                'next_step' => null,
                'sort_order' => 10,
                'is_active' => true,
            ]
        );
    }
}