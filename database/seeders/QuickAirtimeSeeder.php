<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExternalAPIConfiguration;
use App\Models\USSD;
use App\Models\USSDFlow;
use App\Models\USSDFlowOption;
use App\Models\User;
use App\Models\Business;

class QuickAirtimeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Creating QuickAirtime USSD Service...');

        // Create demo user and business
        $user = User::firstOrCreate(
            ['email' => 'demo@quickairtime.com'],
            [
                'name' => 'QuickAirtime Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $business = Business::firstOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => 'QuickAirtime Solutions',
                'business_type' => 'Telecommunications',
                'registration_status' => 'completed_unverified',
                'is_active' => true,
                'is_verified' => true,
            ]
        );

        // Create marketplace API templates
        $this->createMarketplaceAPIs();

        // Create the main USSD service
        $ussd = USSD::firstOrCreate(
            ['pattern' => '555#'],
            [
                'name' => 'QuickAirtime Service',
                'description' => 'Fast and reliable airtime top-up service for all networks',
                'user_id' => $user->id,
                'business_id' => $business->id,
                'is_active' => true,
                'environment' => 'production',
            ]
        );

        // Create USSD flows
        $this->createUSSD flows($ussd);

        $this->command->info('✅ QuickAirtime USSD Service created successfully!');
        $this->command->info('');
        $this->command->info('📱 USSD Details:');
        $this->command->info('   Pattern: 555#');
        $this->command->info('   Name: QuickAirtime Service');
        $this->command->info('');
        $this->command->info('👤 Demo User:');
        $this->command->info('   Email: demo@quickairtime.com');
        $this->command->info('   Password: password');
        $this->command->info('');
        $this->command->info('🔗 Configure: http://localhost:8000/ussd/' . $ussd->id . '/configure');
        $this->command->info('🔗 Simulator: http://localhost:8000/ussd/' . $ussd->id . '/simulator');
    }

    private function createMarketplaceAPIs()
    {
        $this->command->info('📡 Creating Marketplace API Templates...');

        // MTN Airtime API
        ExternalAPIConfiguration::firstOrCreate(
            ['name' => 'MTN Airtime API', 'category' => 'marketplace'],
            [
                'user_id' => null,
                'ussd_id' => null,
                'description' => 'MTN Airtime top-up service - Production ready',
                'provider_name' => 'MTN Nigeria',
                'endpoint_url' => 'https://api.mtn.com/v1/airtime/purchase',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => '{"api_key":"{{MTN_API_KEY}}","api_secret":"{{MTN_API_SECRET}}"}',
                'headers' => '{"Content-Type":"application/json","Accept":"application/json"}',
                'request_mapping' => '{"phone_number":"{{input.phone_number}}","amount":"{{input.amount}}","reference":"{{session.session_id}}"}',
                'request_template' => '{"phone_number":"{{input.phone_number}}","amount":"{{input.amount}}","reference":"{{session.session_id}}"}',
                'response_mapping' => '{"success":"data.status","message":"data.message","transaction_id":"data.transaction_id"}',
                'success_criteria' => '[{"field":"data.status","operator":"equals","value":"success"}]',
                'error_handling' => '{"timeout_message":"MTN service unavailable","api_error_message":"{{response.data.message}}"}',
                'is_active' => true,
                'is_verified' => true,
                'is_marketplace_template' => true,
                'marketplace_category' => 'airtime',
                'marketplace_metadata' => '{"provider":"MTN","service_type":"airtime","country":"Nigeria"}',
                'environment' => 'production',
                'test_status' => 'success',
                'last_tested_at' => now(),
            ]
        );

        // Airtel Airtime API
        ExternalAPIConfiguration::firstOrCreate(
            ['name' => 'Airtel Airtime API', 'category' => 'marketplace'],
            [
                'user_id' => null,
                'ussd_id' => null,
                'description' => 'Airtel Airtime top-up service - Production ready',
                'provider_name' => 'Airtel Nigeria',
                'endpoint_url' => 'https://api.airtel.com/v1/airtime/purchase',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => '{"api_key":"{{AIRTEL_API_KEY}}","api_secret":"{{AIRTEL_API_SECRET}}"}',
                'headers' => '{"Content-Type":"application/json","Accept":"application/json"}',
                'request_mapping' => '{"phone_number":"{{input.phone_number}}","amount":"{{input.amount}}","reference":"{{session.session_id}}"}',
                'request_template' => '{"phone_number":"{{input.phone_number}}","amount":"{{input.amount}}","reference":"{{session.session_id}}"}',
                'response_mapping' => '{"success":"data.status","message":"data.message","transaction_id":"data.transaction_id"}',
                'success_criteria' => '[{"field":"data.status","operator":"equals","value":"success"}]',
                'error_handling' => '{"timeout_message":"Airtel service unavailable","api_error_message":"{{response.data.message}}"}',
                'is_active' => true,
                'is_verified' => true,
                'is_marketplace_template' => true,
                'marketplace_category' => 'airtime',
                'marketplace_metadata' => '{"provider":"Airtel","service_type":"airtime","country":"Nigeria"}',
                'environment' => 'production',
                'test_status' => 'success',
                'last_tested_at' => now(),
            ]
        );

        $this->command->info('✅ Marketplace APIs created: MTN, Airtel');
    }

    private function createUSSD flows($ussd)
    {
        $this->command->info('🔄 Creating USSD Flows...');

        // Ensure root flow exists
        $ussd->ensureRootFlow();
        $rootFlow = $ussd->rootFlow();

        // Update root flow
        $rootFlow->update([
            'name' => 'Main Menu',
            'title' => 'QuickAirtime',
            'menu_text' => "Welcome to QuickAirtime!\nFast & Reliable Airtime Service\n\n1. Buy Airtime\n2. Check Balance\n0. Exit",
        ]);

        // Clear existing options
        $rootFlow->options()->delete();

        // Create root flow options
        USSDFlowOption::create([
            'flow_id' => $rootFlow->id,
            'option_text' => 'Buy Airtime',
            'option_value' => '1',
            'action_type' => 'navigate',
            'action_data' => ['next_flow_id' => 'network_selection'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $rootFlow->id,
            'option_text' => 'Check Balance',
            'option_value' => '2',
            'action_type' => 'navigate',
            'action_data' => ['next_flow_id' => 'balance_check'],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $rootFlow->id,
            'option_text' => 'Exit',
            'option_value' => '0',
            'action_type' => 'end_session',
            'action_data' => ['message' => 'Thank you for using QuickAirtime!'],
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create Network Selection Flow
        $networkFlow = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Network Selection',
            'title' => 'Select Network',
            'description' => 'Choose your network provider',
            'menu_text' => "Select Network:\n\n1. MTN\n2. Airtel\n0. Back",
            'is_root' => false,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $networkFlow->id,
            'option_text' => 'MTN',
            'option_value' => '1',
            'action_type' => 'navigate',
            'action_data' => ['next_flow_id' => 'mtn_phone_input'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $networkFlow->id,
            'option_text' => 'Airtel',
            'option_value' => '2',
            'action_type' => 'navigate',
            'action_data' => ['next_flow_id' => 'airtel_phone_input'],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $networkFlow->id,
            'option_text' => 'Back',
            'option_value' => '0',
            'action_type' => 'navigate',
            'action_data' => ['next_flow_id' => 'root_flow'],
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create MTN Flow
        $this->createMTNFlow($ussd);
        
        // Create Airtel Flow
        $this->createAirtelFlow($ussd);

        // Create Balance Check Flow
        USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Balance Check',
            'title' => 'Account Balance',
            'description' => 'Check account balance',
            'menu_text' => "Account Balance\n\nYour current balance: ₦1,500.00\nLast transaction: ₦1,000 MTN Airtime\nDate: " . now()->format('d/m/Y H:i') . "\n\nThank you for using QuickAirtime!",
            'is_root' => false,
            'is_active' => true,
        ]);

        $this->command->info('✅ USSD Flows created successfully');
    }

    private function createMTNFlow($ussd)
    {
        // MTN Phone Input Flow
        $mtnPhoneFlow = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'MTN Phone Input',
            'title' => 'MTN Airtime',
            'description' => 'Enter MTN phone number',
            'menu_text' => "MTN Airtime Purchase\n\nEnter phone number:",
            'is_root' => false,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $mtnPhoneFlow->id,
            'option_text' => 'Continue',
            'option_value' => '*',
            'action_type' => 'input_phone',
            'action_data' => [
                'prompt' => 'Enter MTN phone number:',
                'next_flow_id' => 'mtn_amount_input',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // MTN Amount Input Flow
        $mtnAmountFlow = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'MTN Amount Input',
            'title' => 'Enter Amount',
            'description' => 'Enter airtime amount',
            'menu_text' => "Enter amount (₦50 - ₦10,000):",
            'is_root' => false,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $mtnAmountFlow->id,
            'option_text' => 'Continue',
            'option_value' => '*',
            'action_type' => 'input_amount',
            'action_data' => [
                'prompt' => 'Enter amount:',
                'next_flow_id' => 'mtn_confirmation',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // MTN Confirmation Flow
        $mtnConfirmFlow = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'MTN Confirmation',
            'title' => 'Confirm Purchase',
            'description' => 'Confirm MTN airtime purchase',
            'menu_text' => "Confirm MTN Airtime Purchase:\n\nPhone: {{input.phone_number}}\nAmount: ₦{{input.amount}}\n\n1. Confirm\n0. Cancel",
            'is_root' => false,
            'is_active' => true,
        ]);

        // Get MTN API configuration
        $mtnApiConfig = ExternalAPIConfiguration::where('name', 'MTN Airtime API')->first();

        // Confirm option with API call
        USSDFlowOption::create([
            'flow_id' => $mtnConfirmFlow->id,
            'option_text' => 'Confirm',
            'option_value' => '1',
            'action_type' => 'external_api_call',
            'action_data' => [
                'api_configuration_id' => $mtnApiConfig->id,
                'end_session_after_api' => false,
                'success_flow_id' => 'mtn_success',
                'error_flow_id' => 'mtn_error',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $mtnConfirmFlow->id,
            'option_text' => 'Cancel',
            'option_value' => '0',
            'action_type' => 'navigate',
            'action_data' => ['next_flow_id' => 'root_flow'],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // MTN Success Flow
        USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'MTN Success',
            'title' => 'Purchase Successful',
            'description' => 'MTN airtime purchase successful',
            'menu_text' => "✅ MTN Airtime Purchase Successful!\n\nPhone: {{input.phone_number}}\nAmount: ₦{{input.amount}}\nTransaction ID: {{api_response.transaction_id}}\n\nThank you for using QuickAirtime!",
            'is_root' => false,
            'is_active' => true,
        ]);

        // MTN Error Flow
        USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'MTN Error',
            'title' => 'Purchase Failed',
            'description' => 'MTN airtime purchase failed',
            'menu_text' => "❌ MTN Airtime Purchase Failed\n\n{{api_response.message}}\n\nPlease try again later or contact support.",
            'is_root' => false,
            'is_active' => true,
        ]);
    }

    private function createAirtelFlow($ussd)
    {
        // Similar structure for Airtel
        $airtelPhoneFlow = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Airtel Phone Input',
            'title' => 'Airtel Airtime',
            'description' => 'Enter Airtel phone number',
            'menu_text' => "Airtel Airtime Purchase\n\nEnter phone number:",
            'is_root' => false,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $airtelPhoneFlow->id,
            'option_text' => 'Continue',
            'option_value' => '*',
            'action_type' => 'input_phone',
            'action_data' => [
                'prompt' => 'Enter Airtel phone number:',
                'next_flow_id' => 'airtel_amount_input',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Airtel Amount Input Flow
        $airtelAmountFlow = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Airtel Amount Input',
            'title' => 'Enter Amount',
            'description' => 'Enter airtime amount',
            'menu_text' => "Enter amount (₦50 - ₦10,000):",
            'is_root' => false,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $airtelAmountFlow->id,
            'option_text' => 'Continue',
            'option_value' => '*',
            'action_type' => 'input_amount',
            'action_data' => [
                'prompt' => 'Enter amount:',
                'next_flow_id' => 'airtel_confirmation',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Airtel Confirmation Flow
        $airtelConfirmFlow = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Airtel Confirmation',
            'title' => 'Confirm Purchase',
            'description' => 'Confirm Airtel airtime purchase',
            'menu_text' => "Confirm Airtel Airtime Purchase:\n\nPhone: {{input.phone_number}}\nAmount: ₦{{input.amount}}\n\n1. Confirm\n0. Cancel",
            'is_root' => false,
            'is_active' => true,
        ]);

        // Get Airtel API configuration
        $airtelApiConfig = ExternalAPIConfiguration::where('name', 'Airtel Airtime API')->first();

        // Confirm option with API call
        USSDFlowOption::create([
            'flow_id' => $airtelConfirmFlow->id,
            'option_text' => 'Confirm',
            'option_value' => '1',
            'action_type' => 'external_api_call',
            'action_data' => [
                'api_configuration_id' => $airtelApiConfig->id,
                'end_session_after_api' => false,
                'success_flow_id' => 'airtel_success',
                'error_flow_id' => 'airtel_error',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        USSDFlowOption::create([
            'flow_id' => $airtelConfirmFlow->id,
            'option_text' => 'Cancel',
            'option_value' => '0',
            'action_type' => 'navigate',
            'action_data' => ['next_flow_id' => 'root_flow'],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Airtel Success Flow
        USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Airtel Success',
            'title' => 'Purchase Successful',
            'description' => 'Airtel airtime purchase successful',
            'menu_text' => "✅ Airtel Airtime Purchase Successful!\n\nPhone: {{input.phone_number}}\nAmount: ₦{{input.amount}}\nTransaction ID: {{api_response.transaction_id}}\n\nThank you for using QuickAirtime!",
            'is_root' => false,
            'is_active' => true,
        ]);

        // Airtel Error Flow
        USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Airtel Error',
            'title' => 'Purchase Failed',
            'description' => 'Airtel airtime purchase failed',
            'menu_text' => "❌ Airtel Airtime Purchase Failed\n\n{{api_response.message}}\n\nPlease try again later or contact support.",
            'is_root' => false,
            'is_active' => true,
        ]);
    }
}

