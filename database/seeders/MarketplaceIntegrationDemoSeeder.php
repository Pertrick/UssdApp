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

class MarketplaceIntegrationDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if we already have the demo USSD service
        if (USSD::where('pattern', '555#')->exists()) {
            $this->command->info('Marketplace Integration Demo USSD service already exists. Skipping...');
            return;
        }

        // Get first user and business
        $user = User::first();
        $business = Business::first();

        if (!$user || !$business) {
            $this->command->error('No users or businesses found. Please run UserSeeder and BusinessSeeder first.');
            return;
        }

        // Create the demo USSD service
        $ussd = USSD::create([
            'name' => 'Marketplace Services Demo',
            'description' => 'Demo USSD service showcasing marketplace integrations',
            'pattern' => '555#',
            'user_id' => $user->id,
            'business_id' => $business->id,
            'is_active' => true
        ]);

        // Get existing marketplace APIs
        $marketplaceApis = ExternalAPIConfiguration::where('is_marketplace_template', true)->get();
        
        if ($marketplaceApis->isEmpty()) {
            $this->command->error('No marketplace APIs found. Please run MarketplaceAPISeeder first.');
            return;
        }

        $this->command->info("Found {$marketplaceApis->count()} marketplace APIs");

        // Create USSD flows
        $flows = [];

        // 1. Main Menu
        $mainMenu = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Main Menu',
            'description' => 'Main menu for marketplace services',
            'menu_text' => "Welcome to Marketplace Services\n\n1. Buy Airtime\n2. Buy Data\n3. Pay Bills\n4. Bank Services\n5. Help\n0. Exit",
            'is_root' => true,
            'is_active' => true,
            'sort_order' => 1
        ]);
        $flows['main_menu'] = $mainMenu;

        // 2. Airtime Services
        $airtimeServices = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Airtime Services',
            'description' => 'Airtime purchase services',
            'menu_text' => "Airtime Services:\n\n1. MTN Airtime\n2. Airtel Airtime\n3. Glo Airtime\n4. 9mobile Airtime\n0. Back to Main Menu",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 2
        ]);
        $flows['airtime_services'] = $airtimeServices;

        // 3. Data Services
        $dataServices = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Data Services',
            'description' => 'Data bundle purchase services',
            'menu_text' => "Data Services:\n\n1. MTN Data\n2. Airtel Data\n3. Glo Data\n4. 9mobile Data\n0. Back to Main Menu",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 3
        ]);
        $flows['data_services'] = $dataServices;

        // 4. Bill Payment
        $billPayment = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Bill Payment',
            'description' => 'Utility bill payment services',
            'menu_text' => "Bill Payment Services:\n\n1. Ikeja Electric\n2. Eko Electricity\n3. Water Bill\n4. Cable TV\n0. Back to Main Menu",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 4
        ]);
        $flows['bill_payment'] = $billPayment;

        // 5. Bank Services
        $bankServices = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Bank Services',
            'description' => 'Banking services',
            'menu_text' => "Banking Services:\n\n1. GT Bank Transfer\n2. Paystack Payment\n3. Account Balance\n4. Transaction History\n0. Back to Main Menu",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 5
        ]);
        $flows['bank_services'] = $bankServices;

        // 6. Phone Input
        $phoneInput = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Phone Input',
            'description' => 'Enter phone number',
            'menu_text' => "Enter phone number:\n\nFormat: 08012345678\n\n0. Back",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 6
        ]);
        $flows['phone_input'] = $phoneInput;

        // 7. Amount Input
        $amountInput = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Amount Input',
            'description' => 'Enter amount',
            'menu_text' => "Enter amount:\n\nFormat: 100\n\n0. Back",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 7
        ]);
        $flows['amount_input'] = $amountInput;

        // 8. Confirmation
        $confirmation = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Confirmation',
            'description' => 'Confirm transaction',
            'menu_text' => "Confirm Transaction:\n\nService: {service}\nPhone: {phone}\nAmount: N{amount}\n\n1. Confirm\n2. Cancel\n0. Back",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 8
        ]);
        $flows['confirmation'] = $confirmation;

        // 9. Processing
        $processing = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Processing',
            'description' => 'Processing transaction',
            'menu_text' => "Processing your request...\n\nPlease wait...",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 9
        ]);
        $flows['processing'] = $processing;

        // 10. Success
        $success = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Success',
            'description' => 'Transaction successful',
            'menu_text' => "✓ Transaction Successful!\n\nService: {service}\nPhone: {phone}\nAmount: N{amount}\nTransaction ID: {transaction_id}\n\nThank you for using our service!",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 10
        ]);
        $flows['success'] = $success;

        // 11. Error
        $error = USSDFlow::create([
            'ussd_id' => $ussd->id,
            'name' => 'Error',
            'description' => 'Transaction failed',
            'menu_text' => "❌ Transaction Failed\n\n{error_message}\n\n1. Try Again\n2. Contact Support\n0. Main Menu",
            'is_root' => false,
            'is_active' => true,
            'sort_order' => 11
        ]);
        $flows['error'] = $error;

        // Create flow options

        // Main Menu Options
        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Buy Airtime',
            'option_value' => '1',
            'next_flow_id' => $airtimeServices->id,
            'action_type' => 'navigate',
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Buy Data',
            'option_value' => '2',
            'next_flow_id' => $dataServices->id,
            'action_type' => 'navigate',
            'sort_order' => 2,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Pay Bills',
            'option_value' => '3',
            'next_flow_id' => $billPayment->id,
            'action_type' => 'navigate',
            'sort_order' => 3,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Bank Services',
            'option_value' => '4',
            'next_flow_id' => $bankServices->id,
            'action_type' => 'navigate',
            'sort_order' => 4,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Help',
            'option_value' => '5',
            'action_type' => 'message',
            'action_data' => ['message' => 'Need Help?\n\n• Dial 555# to access marketplace services\n• Select your desired service\n• Enter phone number and amount\n• Confirm transaction\n\nFor support: 0800-123-4567\n\nThank you for using our service.'],
            'sort_order' => 5,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $mainMenu->id,
            'option_text' => 'Exit',
            'option_value' => '0',
            'action_type' => 'end_session',
            'action_data' => ['message' => 'Thank you for using our marketplace services. Goodbye!'],
            'sort_order' => 6,
            'is_active' => true
        ]);

        // Airtime Services Options
        $this->createServiceOptions($airtimeServices, $phoneInput, $mainMenu, 'airtime');

        // Data Services Options
        $this->createServiceOptions($dataServices, $phoneInput, $mainMenu, 'data');

        // Bill Payment Options
        $this->createServiceOptions($billPayment, $phoneInput, $mainMenu, 'bill');

        // Bank Services Options
        $this->createServiceOptions($bankServices, $phoneInput, $mainMenu, 'bank');

        // Phone Input Options
        USSDFlowOption::create([
            'flow_id' => $phoneInput->id,
            'option_text' => 'Enter Phone Number',
            'option_value' => '*',
            'next_flow_id' => $amountInput->id,
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
            'next_flow_id' => $mainMenu->id,
            'action_type' => 'navigate',
            'sort_order' => 2,
            'is_active' => true
        ]);

        // Amount Input Options
        USSDFlowOption::create([
            'flow_id' => $amountInput->id,
            'option_text' => 'Enter Amount',
            'option_value' => '*',
            'next_flow_id' => $confirmation->id,
            'action_type' => 'input_amount',
            'action_data' => [
                'prompt' => 'Enter amount:',
                'store_as' => 'amount',
                'min_amount' => 50,
                'max_amount' => 50000,
                'error_message' => 'Amount must be between N50 and N50,000.'
            ],
            'sort_order' => 1,
            'is_active' => true
        ]);

        USSDFlowOption::create([
            'flow_id' => $amountInput->id,
            'option_text' => 'Back',
            'option_value' => '0',
            'next_flow_id' => $phoneInput->id,
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
            'next_flow_id' => $amountInput->id,
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
                'api_configuration_id' => '{{session.selected_api_id}}',
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
            'option_text' => 'Buy More',
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
            'action_data' => ['message' => 'Thank you for using our marketplace services. Goodbye!'],
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
            'action_data' => ['message' => 'Contact Support:\n\nPhone: 0800-123-4567\nEmail: support@marketplace.com\nWhatsApp: +234-800-123-4567\n\nWe apologize for the inconvenience.'],
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

        $this->command->info('Marketplace Integration Demo USSD service created successfully!');
        $this->command->info("USSD Pattern: {$ussd->pattern}");
        $this->command->info('Flows created: ' . count($flows));
        
        $this->command->info("\n📱 Real-World Usage Flow:");
        $this->command->info("1. User dials 555#");
        $this->command->info("2. Selects service category (Airtime, Data, Bills, Banking)");
        $this->command->info("3. Chooses specific service (MTN, Airtel, etc.)");
        $this->command->info("4. Enters phone number");
        $this->command->info("5. Enters amount");
        $this->command->info("6. Confirms transaction");
        $this->command->info("7. System calls marketplace API to process transaction");
        $this->command->info("8. Shows success/error message");
        $this->command->info("9. User can buy more or exit");
        
        $this->command->info("\n🔗 Available Marketplace APIs:");
        foreach ($marketplaceApis as $api) {
            $this->command->info("   • {$api->name} ({$api->provider_name})");
        }
    }

    /**
     * Create service options for different categories
     */
    private function createServiceOptions($flow, $nextFlow, $backFlow, $category)
    {
        $services = [
            'airtime' => [
                ['text' => 'MTN Airtime', 'value' => '1', 'api_name' => 'MTN Airtime'],
                ['text' => 'Airtel Airtime', 'value' => '2', 'api_name' => 'Airtel Airtime'],
                ['text' => 'Glo Airtime', 'value' => '3', 'api_name' => 'Glo Airtime'],
                ['text' => '9mobile Airtime', 'value' => '4', 'api_name' => '9mobile Airtime'],
            ],
            'data' => [
                ['text' => 'MTN Data', 'value' => '1', 'api_name' => 'MTN Data'],
                ['text' => 'Airtel Data', 'value' => '2', 'api_name' => 'Airtel Data'],
                ['text' => 'Glo Data', 'value' => '3', 'api_name' => 'Glo Data'],
                ['text' => '9mobile Data', 'value' => '4', 'api_name' => '9mobile Data'],
            ],
            'bill' => [
                ['text' => 'Ikeja Electric', 'value' => '1', 'api_name' => 'Ikeja Electric'],
                ['text' => 'Eko Electricity', 'value' => '2', 'api_name' => 'Eko Electricity'],
                ['text' => 'Water Bill', 'value' => '3', 'api_name' => 'Water Bill'],
                ['text' => 'Cable TV', 'value' => '4', 'api_name' => 'Cable TV'],
            ],
            'bank' => [
                ['text' => 'GT Bank Transfer', 'value' => '1', 'api_name' => 'GT Bank'],
                ['text' => 'Paystack Payment', 'value' => '2', 'api_name' => 'Paystack'],
                ['text' => 'Account Balance', 'value' => '3', 'api_name' => 'Account Balance'],
                ['text' => 'Transaction History', 'value' => '4', 'api_name' => 'Transaction History'],
            ]
        ];

        $categoryServices = $services[$category] ?? [];

        foreach ($categoryServices as $index => $service) {
            USSDFlowOption::create([
                'flow_id' => $flow->id,
                'option_text' => $service['text'],
                'option_value' => $service['value'],
                'next_flow_id' => $nextFlow->id,
                'action_type' => 'navigate',
                'action_data' => [
                    'store_as' => 'service',
                    'value' => $service['text'],
                    'api_name' => $service['api_name']
                ],
                'sort_order' => $index + 1,
                'is_active' => true
            ]);
        }

        // Add back option
        USSDFlowOption::create([
            'flow_id' => $flow->id,
            'option_text' => 'Back to Main Menu',
            'option_value' => '0',
            'next_flow_id' => $backFlow->id,
            'action_type' => 'navigate',
            'sort_order' => count($categoryServices) + 1,
            'is_active' => true
        ]);
    }
}
