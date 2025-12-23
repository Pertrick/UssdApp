<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\USSD;
use App\Models\USSDFlow;
use App\Models\USSDFlowOption;
use App\Models\ExternalAPIConfiguration;
use App\Models\User;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

class AirtimeIntegrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if we already have the airtime USSD service
        if (USSD::where('pattern', '999#')->exists()) {
            $this->command->info('Airtime USSD service already exists. Skipping...');
            return;
        }

        // Get first user and business
        $user = User::first();
        $business = Business::first();

        if (!$user || !$business) {
            $this->command->error('No users or businesses found. Please run UserSeeder and BusinessSeeder first.');
            return;
        }

        // Create the airtime USSD service
        $ussd = USSD::create([
            'name' => 'Airtime Purchase Service',
            'description' => 'Buy airtime for any network via USSD with real-time integration',
            'pattern' => '999#',
            'user_id' => $user->id,
            'business_id' => $business->id,
            'is_active' => true
        ]);

        // Create the external API configuration for airtime purchase
        $apiData = [
            'user_id' => $user->id,
            'name' => 'MTN Airtime Purchase API',
            'description' => 'Real-time MTN airtime purchase integration',
            'category' => 'custom',
            'provider_name' => 'MTN Nigeria',
            'endpoint_url' => 'https://api.mtn.com/v1/airtime/recharge',
            'method' => 'POST',
            'timeout' => 30,
            'retry_attempts' => 3,
            'auth_type' => 'api_key',
            'auth_config' => [
                'api_key' => 'MTN_API_KEY_12345',
                'api_secret' => 'MTN_SECRET_67890'
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-API-Key' => '{{auth_config.api_key}}'
            ],
            'request_mapping' => [
                'phone_number' => '{{session.phone_number}}',
                'amount' => '{{input.amount}}',
                'reference' => '{{session.session_id}}',
                'network' => '{{input.network}}'
            ],
            'response_mapping' => [
                'success' => 'data.status',
                'message' => 'data.message',
                'transaction_id' => 'data.transaction_id',
                'balance' => 'data.new_balance',
                'units' => 'data.units_credited'
            ],
            'success_criteria' => [
                [
                    'field' => 'data.status',
                    'operator' => 'equals',
                    'value' => 'success'
                ]
            ],
            'error_handling' => [
                'timeout_message' => 'Service temporarily unavailable. Please try again.',
                'network_error_message' => 'Network error. Please check your connection.',
                'api_error_message' => '{{response.data.message}}'
            ],
            'is_active' => true,
            'is_verified' => true,
            'test_status' => 'success',
            'environment' => 'testing'
        ];

        // Use direct database insert to avoid model encryption issues
        $data = $apiData;
        
        // JSON encode all array fields
        $data['auth_config'] = json_encode($apiData['auth_config']);
        $data['headers'] = json_encode($apiData['headers']);
        $data['request_mapping'] = json_encode($apiData['request_mapping']);
        $data['response_mapping'] = json_encode($apiData['response_mapping']);
        $data['success_criteria'] = json_encode($apiData['success_criteria']);
        $data['error_handling'] = json_encode($apiData['error_handling']);
        
        $data['created_at'] = now();
        $data['updated_at'] = now();
        
        $apiConfigId = DB::table('external_api_configurations')->insertGetId($data);
        $apiConfig = ExternalAPIConfiguration::find($apiConfigId);

        // Create USSD flows
        $flows = [];

        // 1. Main Menu
        $mainMenu = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Main Menu',
            'description' => 'Main menu for airtime purchase',
            'menu_text' => "Welcome to Airtime Purchase\n\n1. Buy Airtime\n2. Check Balance\n3. Transaction History\n4. Help\n0. Exit",
            'is_root' => true,
            'is_active' => true,
            'sort_order' => 1
        ]);
        $flows['main_menu'] = $mainMenu;

        // 2. Network Selection
        $networkSelection = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Network Selection',
            'description' => 'Select mobile network for airtime purchase',
            'menu_text' => "Select Network:\n\n1. MTN\n2. Airtel\n3. Glo\n4. 9mobile\n0. Back to Main Menu",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 2
        ]);
        $flows['network_selection'] = $networkSelection;

        // 3. Phone Number Input
        $phoneInput = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Phone Number Input',
            'description' => 'Enter phone number for airtime purchase',
            'menu_text' => "Enter phone number:\n\nFormat: 08012345678\n\n0. Back",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 3
        ]);
        $flows['phone_input'] = $phoneInput;

        // 4. Amount Selection
        $amountSelection = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Amount Selection',
            'description' => 'Select airtime amount',
            'menu_text' => "Select Amount:\n\n1. N50\n2. N100\n3. N200\n4. N500\n5. N1000\n6. Custom Amount\n0. Back",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 4
        ]);
        $flows['amount_selection'] = $amountSelection;

        // 5. Custom Amount Input
        $customAmount = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Custom Amount Input',
            'description' => 'Enter custom airtime amount',
            'menu_text' => "Enter amount (N50 - N50,000):\n\n0. Back",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 5
        ]);
        $flows['custom_amount'] = $customAmount;

        // 6. Confirmation
        $confirmation = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Confirmation',
            'description' => 'Confirm airtime purchase',
            'menu_text' => "Confirm Purchase:\n\nNetwork: {network}\nPhone: {phone}\nAmount: N{amount}\n\n1. Confirm\n2. Cancel\n0. Back",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 6
        ]);
        $flows['confirmation'] = $confirmation;

        // 7. Processing
        $processing = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Processing',
            'description' => 'Processing airtime purchase',
            'menu_text' => "Processing your request...\n\nPlease wait...",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 7
        ]);
        $flows['processing'] = $processing;

        // 8. Success
        $success = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Success',
            'description' => 'Airtime purchase successful',
            'menu_text' => "✓ Airtime Purchase Successful!\n\nNetwork: {network}\nPhone: {phone}\nAmount: N{amount}\nTransaction ID: {transaction_id}\n\nThank you for using our service!",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 8
        ]);
        $flows['success'] = $success;

        // 9. Error
        $error = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Error',
            'description' => 'Airtime purchase failed',
            'menu_text' => "❌ Purchase Failed\n\n{error_message}\n\n1. Try Again\n2. Contact Support\n0. Main Menu",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 9
        ]);
        $flows['error'] = $error;

        // Create flow options with proper navigation and actions

        // Main Menu Options
        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Buy Airtime',
            'option_value' => '1',
            'next_flow_id' => $networkSelection->id,
            'action_type' => 'navigate',
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Check Balance',
            'option_value' => '2',
            'action_type' => 'message',
            'action_data' => ['message' => 'Your wallet balance is N2,500.00\n\nThank you for using our service.'],
            'sort_order' => 2,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Transaction History',
            'option_value' => '3',
            'action_type' => 'message',
            'action_data' => ['message' => 'Recent Transactions:\n\n1. MTN - N100 - 08012345678\n   Date: 2024-01-15 14:30\n   Status: ✓ Success\n\n2. Airtel - N200 - 08098765432\n   Date: 2024-01-14 09:15\n   Status: ✓ Success\n\nThank you for using our service.'],
            'sort_order' => 3,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Help',
            'option_value' => '4',
            'action_type' => 'message',
            'action_data' => ['message' => 'Need Help?\n\n• Dial 456# to access airtime purchase\n• Select your network\n• Enter phone number\n• Choose amount\n• Confirm purchase\n\nFor support: 0800-123-4567\n\nThank you for using our service.'],
            'sort_order' => 4,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Exit',
            'option_value' => '0',
            'action_type' => 'end_session',
            'action_data' => ['message' => 'Thank you for using our airtime service. Goodbye!'],
            'sort_order' => 5,
            'is_active' => true
        ]);

        // Network Selection Options
        USSDFlowOption::create([
            'flow_id' => $networkSelection->id,
            'option_text' => 'MTN',
            'option_value' => '1',
            'next_flow_id' => $phoneInput->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'network', 'value' => 'MTN'],
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $networkSelection->id,
            'option_text' => 'Airtel',
            'option_value' => '2',
            'next_flow_id' => $phoneInput->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'network', 'value' => 'Airtel'],
            'sort_order' => 2,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $networkSelection->id,
            'option_text' => 'Glo',
            'option_value' => '3',
            'next_flow_id' => $phoneInput->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'network', 'value' => 'Glo'],
            'sort_order' => 3,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $networkSelection->id,
            'option_text' => '9mobile',
            'option_value' => '4',
            'next_flow_id' => $phoneInput->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'network', 'value' => '9mobile'],
            'sort_order' => 4,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $networkSelection->id,
            'option_text' => 'Back to Main Menu',
            'option_value' => '0',
            'next_flow_id' => $mainMenu->id,
            'action_type' => 'navigate',
            'sort_order' => 5,
            'is_active' => true
        ]);

        // Phone Input Options
        USSDFlowOption::create([
            'flow_id' => $phoneInput->id,
            'option_text' => 'Enter Phone Number',
            'option_value' => '*',
            'next_flow_id' => $amountSelection->id,
            'action_type' => 'input_phone',
            'action_data' => [
                'prompt' => 'Enter phone number:',
                'store_as' => 'phone',
                'validation' => 'length:11',
                'error_message' => 'Please enter a valid 11-digit phone number.'
            ],
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $phoneInput->id,
            'option_text' => 'Back',
            'option_value' => '0',
            'next_flow_id' => $networkSelection->id,
            'action_type' => 'navigate',
            'sort_order' => 2,
            'is_active' => true
        ]);

        // Amount Selection Options
        USSDFlowOption::create([
            'flow_id' => $amountSelection->id,
            'option_text' => 'N50',
            'option_value' => '1',
            'next_flow_id' => $confirmation->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'amount', 'value' => '50'],
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $amountSelection->id,
            'option_text' => 'N100',
            'option_value' => '2',
            'next_flow_id' => $confirmation->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'amount', 'value' => '100'],
            'sort_order' => 2,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $amountSelection->id,
            'option_text' => 'N200',
            'option_value' => '3',
            'next_flow_id' => $confirmation->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'amount', 'value' => '200'],
            'sort_order' => 3,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $amountSelection->id,
            'option_text' => 'N500',
            'option_value' => '4',
            'next_flow_id' => $confirmation->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'amount', 'value' => '500'],
            'sort_order' => 4,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $amountSelection->id,
            'option_text' => 'N1000',
            'option_value' => '5',
            'next_flow_id' => $confirmation->id,
            'action_type' => 'navigate',
            'action_data' => ['store_as' => 'amount', 'value' => '1000'],
            'sort_order' => 5,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $amountSelection->id,
            'option_text' => 'Custom Amount',
            'option_value' => '6',
            'next_flow_id' => $customAmount->id,
            'action_type' => 'navigate',
            'sort_order' => 6,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $amountSelection->id,
            'option_text' => 'Back',
            'option_value' => '0',
            'next_flow_id' => $phoneInput->id,
            'action_type' => 'navigate',
            'sort_order' => 7,
            'is_active' => true
        ]);

        // Custom Amount Options
        USSDFlowOption::create([
            'flow_id' => $customAmount->id,
            'option_text' => 'Enter Custom Amount',
            'option_value' => '*',
            'next_flow_id' => $confirmation->id,
            'action_type' => 'input_amount',
            'action_data' => [
                'prompt' => 'Enter amount (N50 - N50,000):',
                'store_as' => 'amount',
                'min_amount' => 50,
                'max_amount' => 50000,
                'error_message' => 'Amount must be between N50 and N50,000.'
            ],
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $customAmount->id,
            'option_text' => 'Back',
            'option_value' => '0',
            'next_flow_id' => $amountSelection->id,
            'action_type' => 'navigate',
            'sort_order' => 2,
            'is_active' => true
        ]);

        // Confirmation Options
        USSDFlowOption::create([
            'flow_id' => $confirmation->id,
            'option_text' => 'Confirm',
            'option_value' => '1',
            'next_flow_id' => $processing->id,
            'action_type' => 'navigate',
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $confirmation->id,
            'option_text' => 'Cancel',
            'option_value' => '2',
            'next_flow_id' => $mainMenu->id,
            'action_type' => 'navigate',
            'sort_order' => 2,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $confirmation->id,
            'option_text' => 'Back',
            'option_value' => '0',
            'next_flow_id' => $amountSelection->id,
            'action_type' => 'navigate',
            'sort_order' => 3,
            'is_active' => true
        ]);

        // Processing Options (auto-navigate to API call)
        USSDFlowOption::create([
            'flow_id' => $processing->id,
            'option_text' => 'Process',
            'option_value' => '*',
            'action_type' => 'external_api_call',
            'action_data' => [
                'api_configuration_id' => $apiConfig->id,
                'end_session_after_api' => false,
                'success_flow_id' => $success->id,
                'error_flow_id' => $error->id
            ],
            'sort_order' => 1,
            'is_active' => true
        ]);

        // Success Options
        USSDFlowOption::create([
            'flow_id' => $success->id,
            'option_text' => 'Buy More Airtime',
            'option_value' => '1',
            'next_flow_id' => $mainMenu->id,
            'action_type' => 'navigate',
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $success->id,
            'option_text' => 'Exit',
            'option_value' => '0',
            'action_type' => 'end_session',
            'action_data' => ['message' => 'Thank you for using our airtime service. Goodbye!'],
            'sort_order' => 2,
            'is_active' => true
        ]);

        // Error Options
        USSDFlowOption::create([
            'flow_id' => $error->id,
            'option_text' => 'Try Again',
            'option_value' => '1',
            'next_flow_id' => $mainMenu->id,
            'action_type' => 'navigate',
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $error->id,
            'option_text' => 'Contact Support',
            'option_value' => '2',
            'action_type' => 'message',
            'action_data' => ['message' => 'Contact Support:\n\nPhone: 0800-123-4567\nEmail: support@airtime.com\nWhatsApp: +234-800-123-4567\n\nWe apologize for the inconvenience.'],
            'sort_order' => 2,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $error->id,
            'option_text' => 'Main Menu',
            'option_value' => '0',
            'next_flow_id' => $mainMenu->id,
            'action_type' => 'navigate',
            'sort_order' => 3,
            'is_active' => true
        ]);

        $this->command->info('Airtime Integration USSD service created successfully!');
        $this->command->info("USSD Pattern: {$ussd->pattern}");
        $this->command->info("API Configuration ID: {$apiConfig->id}");
        $this->command->info('Flows created: ' . count($flows));
        
        $this->command->info("\n📱 Real-World Usage Flow:");
        $this->command->info("1. User dials 999#");
        $this->command->info("2. Selects network (MTN, Airtel, etc.)");
        $this->command->info("3. Enters phone number");
        $this->command->info("4. Chooses amount (preset or custom)");
        $this->command->info("5. Confirms purchase");
        $this->command->info("6. System calls external API to process airtime");
        $this->command->info("7. Shows success/error message");
        $this->command->info("8. User can buy more or exit");
    }
}
