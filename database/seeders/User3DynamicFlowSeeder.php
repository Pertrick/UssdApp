<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\USSD;
use App\Models\FlowStep;
use App\Models\FlowConfig;
use App\Models\ExternalAPIConfiguration;
use App\Models\User;
use App\Models\Business;
use App\Models\Environment;

class User3DynamicFlowSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::find(3);
        
        if (!$user) {
            $this->command->error('User with ID 3 not found.');
            return;
        }
        
        $business = $user->businesses()->first();
        if (!$business) {
            $this->command->error('User has no business.');
            return;
        }
        
        $environment = Environment::first();
        if (!$environment) {
            $this->command->error('No environment found.');
            return;
        }
        
        $ussd = USSD::firstOrCreate(
            ['pattern' => '*888#'],
            [
                'name' => 'User 3 Dynamic Flow Demo',
                'description' => 'Dynamic flow demo for user 3',
                'user_id' => $user->id,
                'business_id' => $business->id,
                'environment_id' => $environment->id,
                'is_active' => true,
            ]
        );

        $this->createConfigs($ussd);
        $this->createApis($ussd);
        $this->createSteps($ussd);

        $this->command->info('User 3 Dynamic Flow Demo seeded successfully!');
        $this->command->info("USSD Pattern: {$ussd->pattern}");
        $this->command->info("Test URL: http://localhost:8000/ussd/{$ussd->id}/simulator");
    }

    protected function createConfigs(USSD $ussd): void
    {
        $configs = [
            ['key' => 'welcome_message', 'value' => 'Welcome to our service platform!'],
            ['key' => 'currency', 'value' => 'NGN'],
            ['key' => 'bank_services', 'value' => [
                ['name' => 'Check Balance', 'value' => 'balance'],
                ['name' => 'Mini Statement', 'value' => 'statement'],
                ['name' => 'Transfer Money', 'value' => 'transfer'],
            ]],
            ['key' => 'airtime_amounts', 'value' => [
                ['name' => 'N100', 'value' => '100'],
                ['name' => 'N200', 'value' => '200'],
                ['name' => 'N500', 'value' => '500'],
                ['name' => 'N1000', 'value' => '1000'],
            ]],
        ];

        foreach ($configs as $config) {
            FlowConfig::firstOrCreate(
                ['ussd_id' => $ussd->id, 'key' => $config['key']],
                ['value' => $config['value'], 'is_active' => true]
            );
        }
    }

    protected function createApis(USSD $ussd): void
    {
        ExternalAPIConfiguration::firstOrCreate(
            ['ussd_id' => $ussd->id, 'name' => 'Banking API'],
            [
                'description' => 'API for banking services',
                'category' => 'custom',
                'provider_name' => 'Banking Provider',
                'endpoint_url' => 'https://api.bank.com/{{service}}',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 2,
                'auth_type' => 'api_key',
                'auth_config' => json_encode(['api_key' => '{{API_KEY}}']),
                'headers' => ['Content-Type' => 'application/json'],
                'request_mapping' => ['account_number' => '{session.phone_number}'],
                'response_mapping' => ['balance' => 'data.balance', 'status' => 'status'],
                'success_criteria' => [['field' => 'status', 'operator' => 'equals', 'value' => 'success']],
                'error_handling' => ['fallback_message' => 'Banking service unavailable.'],
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success',
                'environment' => 'production',
            ]
        );

        ExternalAPIConfiguration::firstOrCreate(
            ['ussd_id' => $ussd->id, 'name' => 'Airtime API'],
            [
                'description' => 'API for airtime purchase',
                'category' => 'custom',
                'provider_name' => 'Telco Provider',
                'endpoint_url' => 'https://api.telco.com/airtime',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 2,
                'auth_type' => 'api_key',
                'auth_config' => json_encode(['api_key' => '{{API_KEY}}']),
                'headers' => ['Content-Type' => 'application/json'],
                'request_mapping' => ['phone' => '{session.phone_number}', 'amount' => '{session.data.selected_amount}'],
                'response_mapping' => ['status' => 'status', 'message' => 'message'],
                'success_criteria' => [['field' => 'status', 'operator' => 'equals', 'value' => 'success']],
                'error_handling' => ['fallback_message' => 'Airtime service unavailable.'],
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success',
                'environment' => 'production',
            ]
        );
    }

    protected function createSteps(USSD $ussd): void
    {
        $steps = [
            ['step_id' => 'welcome', 'type' => FlowStep::TYPE_MESSAGE, 'data' => [
                'title' => '{{config.welcome_message}}',
                'message' => 'Access banking and airtime services. Choose your preferred service.',
            ], 'next_step' => 'main_menu', 'sort_order' => 1],
            
            ['step_id' => 'main_menu', 'type' => FlowStep::TYPE_MENU, 'data' => [
                'title' => 'Main Menu',
                'prompt' => 'Select a service:',
                'options' => [
                    ['label' => 'Banking Services', 'value' => 'banking'],
                    ['label' => 'Buy Airtime', 'value' => 'airtime'],
                    ['label' => 'Account Info', 'value' => 'account'],
                    ['label' => 'Exit', 'value' => 'exit'],
                ],
            ], 'next_step' => 'handle_main_choice', 'sort_order' => 2],
            
            ['step_id' => 'banking_menu', 'type' => FlowStep::TYPE_DYNAMIC_MENU, 'data' => [
                'title' => 'Banking Services',
                'prompt' => 'Choose a banking service:',
                'source' => 'config.bank_services',
                'label_field' => 'name',
                'value_field' => 'value',
            ], 'next_step' => 'banking_api_call', 'sort_order' => 3],
            
            ['step_id' => 'banking_api_call', 'type' => FlowStep::TYPE_API_CALL, 'data' => [
                'api_config_id' => ExternalAPIConfiguration::where('ussd_id', $ussd->id)->where('name', 'Banking API')->first()->id,
                'store_as' => 'banking_response',
            ], 'next_step' => 'show_banking_result', 'sort_order' => 4],
            
            ['step_id' => 'show_banking_result', 'type' => FlowStep::TYPE_MESSAGE, 'data' => [
                'title' => 'Banking Result',
                'message' => '{{api.banking_response.message}}\nBalance: {{api.banking_response.balance}} {{config.currency}}',
            ], 'next_step' => 'main_menu', 'sort_order' => 5],
            
            ['step_id' => 'airtime_menu', 'type' => FlowStep::TYPE_DYNAMIC_MENU, 'data' => [
                'title' => 'Buy Airtime',
                'prompt' => 'Select amount:',
                'source' => 'config.airtime_amounts',
                'label_field' => 'name',
                'value_field' => 'value',
            ], 'next_step' => 'airtime_api_call', 'sort_order' => 6],
            
            ['step_id' => 'airtime_api_call', 'type' => FlowStep::TYPE_API_CALL, 'data' => [
                'api_config_id' => ExternalAPIConfiguration::where('ussd_id', $ussd->id)->where('name', 'Airtime API')->first()->id,
                'store_as' => 'airtime_response',
            ], 'next_step' => 'show_airtime_result', 'sort_order' => 7],
            
            ['step_id' => 'show_airtime_result', 'type' => FlowStep::TYPE_MESSAGE, 'data' => [
                'title' => 'Airtime Purchase',
                'message' => '{{api.airtime_response.message}}',
            ], 'next_step' => 'main_menu', 'sort_order' => 8],
            
            ['step_id' => 'account_info', 'type' => FlowStep::TYPE_MESSAGE, 'data' => [
                'title' => 'Account Information',
                'message' => 'Phone: {{session.phone_number}}\nUser: {{session.ussd.user.name}}\nBusiness: {{session.ussd.business.business_name}}',
            ], 'next_step' => 'main_menu', 'sort_order' => 9],
            
            ['step_id' => 'exit', 'type' => FlowStep::TYPE_MESSAGE, 'data' => [
                'title' => 'Thank You!',
                'message' => 'Thank you for using our services!\nGoodbye!',
            ], 'next_step' => null, 'sort_order' => 10],
        ];

        foreach ($steps as $step) {
            FlowStep::firstOrCreate(
                ['ussd_id' => $ussd->id, 'step_id' => $step['step_id']],
                [
                    'type' => $step['type'],
                    'data' => $step['data'],
                    'next_step' => $step['next_step'],
                    'sort_order' => $step['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
