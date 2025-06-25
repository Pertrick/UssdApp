<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\USSD;
use App\Models\USSDFlow;
use App\Models\USSDFlowOption;
use App\Models\User;
use App\Models\Business;

class USSDSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing USSD data to ensure fresh seeding
        $this->clearExistingUSSDData();
        
        // Get existing users and businesses
        $users = User::all();
        $businesses = Business::all();

        if ($users->isEmpty() || $businesses->isEmpty()) {
            $this->command->info('No users or businesses found. Please run UserSeeder and BusinessSeeder first.');
            return;
        }

        // Create sample USSD services with flows
        $sampleUSSDs = [
            [
                'name' => 'Bank Balance Check',
                'description' => 'Check your account balance and recent transactions via USSD.',
                'pattern' => '123#',
                'is_active' => true,
                'flows' => [
                    [
                        'name' => 'Main Menu',
                        'description' => 'Main menu for banking services',
                        'menu_text' => "Welcome to Bank USSD\n\n1. Check Balance\n2. Mini Statement\n3. Transfer Money\n4. Buy Airtime\n5. Customer Care\n0. Exit",
                        'is_root' => true,
                        'options' => [
                            [
                                'option_text' => 'Check Balance',
                                'option_value' => '1',
                                'action_type' => 'navigate',
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Mini Statement',
                                'option_value' => '2',
                                'action_type' => 'navigate',
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Transfer Money',
                                'option_value' => '3',
                                'action_type' => 'navigate',
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'Buy Airtime',
                                'option_value' => '4',
                                'action_type' => 'navigate',
                                'sort_order' => 4,
                            ],
                            [
                                'option_text' => 'Customer Care',
                                'option_value' => '5',
                                'action_type' => 'navigate',
                                'sort_order' => 5,
                            ],
                            [
                                'option_text' => 'Exit',
                                'option_value' => '0',
                                'action_type' => 'end_session',
                                'action_data' => ['message' => 'Thank you for using our service.'],
                                'sort_order' => 6,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Balance Check',
                        'description' => 'Check account balance',
                        'menu_text' => "Balance Check\n\nEnter your PIN to continue:",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Submit PIN',
                                'option_value' => '*',
                                'action_type' => 'message',
                                'action_data' => ['message' => "Your account balance is:\nN50,000.00\n\nAvailable balance: N45,000.00\n\nThank you for using our service."],
                                'sort_order' => 1,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Mini Statement',
                        'description' => 'Get mini statement',
                        'menu_text' => "Mini Statement\n\n1. Last 5 transactions\n2. Last 10 transactions\n0. Back to main menu",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Last 5 transactions',
                                'option_value' => '1',
                                'action_type' => 'message',
                                'action_data' => ['message' => "Last 5 Transactions:\n\n1. N5,000 - Transfer to John\n2. N2,000 - ATM Withdrawal\n3. N10,000 - Salary Credit\n4. N500 - Airtime Purchase\n5. N1,000 - Bill Payment\n\nThank you for using our service."],
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Last 10 transactions',
                                'option_value' => '2',
                                'action_type' => 'message',
                                'action_data' => ['message' => "Last 10 Transactions:\n\n1. N5,000 - Transfer to John\n2. N2,000 - ATM Withdrawal\n3. N10,000 - Salary Credit\n4. N500 - Airtime Purchase\n5. N1,000 - Bill Payment\n6. N3,000 - POS Payment\n7. N1,500 - Online Purchase\n8. N800 - Utility Bill\n9. N2,500 - Transfer to Mary\n10. N1,200 - ATM Withdrawal\n\nThank you for using our service."],
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Back to main menu',
                                'option_value' => '0',
                                'action_type' => 'navigate',
                                'sort_order' => 3,
                            ],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Airtime Purchase',
                'description' => 'Purchase airtime for yourself or others using USSD.',
                'pattern' => '456#',
                'is_active' => true,
                'flows' => [
                    [
                        'name' => 'Main Menu',
                        'description' => 'Main menu for airtime purchase',
                        'menu_text' => "Airtime Purchase\n\n1. Buy for Self\n2. Buy for Others\n3. Data Plans\n4. Check Balance\n0. Exit",
                        'is_root' => true,
                        'options' => [
                            [
                                'option_text' => 'Buy for Self',
                                'option_value' => '1',
                                'action_type' => 'navigate',
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Buy for Others',
                                'option_value' => '2',
                                'action_type' => 'navigate',
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Data Plans',
                                'option_value' => '3',
                                'action_type' => 'navigate',
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'Check Balance',
                                'option_value' => '4',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Your airtime balance is: N1,250.00\n\nThank you for using our service.'],
                                'sort_order' => 4,
                            ],
                            [
                                'option_text' => 'Exit',
                                'option_value' => '0',
                                'action_type' => 'end_session',
                                'action_data' => ['message' => 'Thank you for using our service.'],
                                'sort_order' => 5,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Buy for Self',
                        'description' => 'Purchase airtime for self',
                        'menu_text' => "Buy Airtime for Self\n\nSelect amount:\n1. N50\n2. N100\n3. N200\n4. N500\n5. N1000\n0. Back",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'N50',
                                'option_value' => '1',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Airtime purchase successful!\n\nAmount: N50\nBalance: N1,300.00\n\nThank you for using our service.'],
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'N100',
                                'option_value' => '2',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Airtime purchase successful!\n\nAmount: N100\nBalance: N1,350.00\n\nThank you for using our service.'],
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'N200',
                                'option_value' => '3',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Airtime purchase successful!\n\nAmount: N200\nBalance: N1,450.00\n\nThank you for using our service.'],
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'N500',
                                'option_value' => '4',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Airtime purchase successful!\n\nAmount: N500\nBalance: N1,750.00\n\nThank you for using our service.'],
                                'sort_order' => 4,
                            ],
                            [
                                'option_text' => 'N1000',
                                'option_value' => '5',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Airtime purchase successful!\n\nAmount: N1000\nBalance: N2,250.00\n\nThank you for using our service.'],
                                'sort_order' => 5,
                            ],
                            [
                                'option_text' => 'Back',
                                'option_value' => '0',
                                'action_type' => 'navigate',
                                'sort_order' => 6,
                            ],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Customer Support',
                'description' => 'Access customer support and help services through USSD.',
                'pattern' => '654#',
                'is_active' => true,
                'flows' => [
                    [
                        'name' => 'Main Menu',
                        'description' => 'Main menu for customer support',
                        'menu_text' => "Customer Support\n\n1. Speak to Agent\n2. Report Issue\n3. FAQ\n4. Contact Info\n0. Exit",
                        'is_root' => true,
                        'options' => [
                            [
                                'option_text' => 'Speak to Agent',
                                'option_value' => '1',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Connecting you to an agent...\n\nPlease wait while we transfer your call.\n\nThank you for your patience.'],
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Report Issue',
                                'option_value' => '2',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Issue reported successfully!\n\nYour ticket number is: TKT-2024-001\n\nWe will contact you within 24 hours.\n\nThank you for using our service.'],
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'FAQ',
                                'option_value' => '3',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Frequently Asked Questions:\n\nQ: How do I check my balance?\nA: Dial *123# and select option 1\n\nQ: How do I transfer money?\nA: Dial *123# and select option 3\n\nQ: How do I buy airtime?\nA: Dial *456# and select option 1\n\nThank you for using our service.'],
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'Contact Info',
                                'option_value' => '4',
                                'action_type' => 'message',
                                'action_data' => ['message' => 'Contact Information:\n\nPhone: 0800-123-4567\nEmail: support@bank.com\nWhatsApp: +234-800-123-4567\n\nAvailable 24/7\n\nThank you for using our service.'],
                                'sort_order' => 4,
                            ],
                            [
                                'option_text' => 'Exit',
                                'option_value' => '0',
                                'action_type' => 'end_session',
                                'action_data' => ['message' => 'Thank you for using our service.'],
                                'sort_order' => 5,
                            ],
                        ]
                    ],
                ]
            ],
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($sampleUSSDs as $ussdData) {
            // Check if USSD pattern already exists
            $existingUSSD = USSD::where('pattern', $ussdData['pattern'])->first();
            
            if ($existingUSSD) {
                $this->command->info("USSD with pattern '{$ussdData['pattern']}' already exists. Skipping...");
                $skippedCount++;
                continue;
            }

            // Assign to a random user and business
            $user = $users->random();
            $business = $businesses->where('user_id', $user->id)->first() ?? $businesses->random();

            // Create USSD service
            $ussd = USSD::create([
                'name' => $ussdData['name'],
                'description' => $ussdData['description'],
                'pattern' => $ussdData['pattern'],
                'user_id' => $user->id,
                'business_id' => $business->id,
                'is_active' => $ussdData['is_active'],
            ]);

            // Create flows for this USSD
            $flows = [];
            foreach ($ussdData['flows'] as $flowData) {
                $flow = USSDFlow::create([
                    'ussd_id' => $ussd->id,
                    'name' => $flowData['name'],
                    'description' => $flowData['description'],
                    'menu_text' => $flowData['menu_text'],
                    'is_root' => $flowData['is_root'],
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
                $flows[] = $flow;
            }

            // Create options for each flow
            foreach ($ussdData['flows'] as $index => $flowData) {
                $currentFlow = $flows[$index];
                
                foreach ($flowData['options'] as $optionData) {
                    $nextFlowId = null;
                    
                    // If this is a navigation action, find the next flow
                    if ($optionData['action_type'] === 'navigate') {
                        // For Bank Balance Check USSD
                        if ($ussdData['name'] === 'Bank Balance Check') {
                            if ($currentFlow->name === 'Main Menu') {
                                // Main menu navigation logic
                                switch ($optionData['option_value']) {
                                    case '1': // Check Balance
                                        $nextFlowId = $flows[1]->id; // Balance Check flow
                                        break;
                                    case '2': // Mini Statement
                                        $nextFlowId = $flows[2]->id; // Mini Statement flow
                                        break;
                                    case '3': // Transfer Money
                                        $nextFlowId = $flows[0]->id; // Back to main menu (for now)
                                        break;
                                    case '4': // Buy Airtime
                                        $nextFlowId = $flows[0]->id; // Back to main menu (for now)
                                        break;
                                    case '5': // Customer Care
                                        $nextFlowId = $flows[0]->id; // Back to main menu (for now)
                                        break;
                                    default:
                                        $nextFlowId = $flows[0]->id; // Default to root
                                }
                            } elseif ($currentFlow->name === 'Balance Check' || $currentFlow->name === 'Mini Statement') {
                                // Back to main menu for sub-flows
                                $nextFlowId = $flows[0]->id;
                            }
                        }
                        // For Airtime Purchase USSD
                        elseif ($ussdData['name'] === 'Airtime Purchase') {
                            if ($currentFlow->name === 'Main Menu') {
                                // Main menu navigation logic
                                switch ($optionData['option_value']) {
                                    case '1': // Buy for Self
                                        $nextFlowId = $flows[1]->id; // Buy for Self flow
                                        break;
                                    case '2': // Buy for Others
                                        $nextFlowId = $flows[0]->id; // Back to main menu (for now)
                                        break;
                                    case '3': // Data Plans
                                        $nextFlowId = $flows[0]->id; // Back to main menu (for now)
                                        break;
                                    default:
                                        $nextFlowId = $flows[0]->id; // Default to root
                                }
                            } elseif ($currentFlow->name === 'Buy for Self') {
                                // Back to main menu for sub-flows
                        $nextFlowId = $flows[0]->id;
                            }
                        }
                        // For Customer Support USSD (all options are messages, no navigation needed)
                        else {
                            $nextFlowId = $flows[0]->id; // Default to root
                        }
                    }
                    
                    USSDFlowOption::create([
                        'flow_id' => $currentFlow->id,
                        'option_text' => $optionData['option_text'],
                        'option_value' => $optionData['option_value'],
                        'next_flow_id' => $nextFlowId,
                        'action_type' => $optionData['action_type'],
                        'action_data' => $optionData['action_data'] ?? null,
                        'requires_input' => false,
                        'sort_order' => $optionData['sort_order'],
                        'is_active' => true,
                    ]);
                }
            }

            $createdCount++;
        }

        $this->command->info("USSD seeding completed!");
        $this->command->info("Created: {$createdCount} USSD services");
        $this->command->info("Skipped: {$skippedCount} USSD services (patterns already exist)");
    }

    /**
     * Clear existing USSD data
     */
    private function clearExistingUSSDData(): void
    {
        $this->command->info('Clearing existing USSD data...');
        
        // Delete in correct order to avoid foreign key constraints
        USSDFlowOption::query()->delete();
        USSDFlow::query()->delete();
        USSD::query()->delete();
        
        $this->command->info('Existing USSD data cleared.');
    }
}
