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
                        'menu_text' => "Balance Check\n\n1. Check Balance\n0. Back to main menu",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Check Balance',
                                'option_value' => '1',
                                'action_type' => 'input_pin',
                                'action_data' => [
                                    'prompt' => 'Enter your 4-digit PIN:',
                                    'length' => '4',
                                    'error_message' => 'Invalid PIN. Please enter a 4-digit number.',
                                    'success_message' => 'PIN verified successfully!'
                                ],
                                'next_flow_id' => 3, // This will be the Balance Result flow
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Back to main menu',
                                'option_value' => '0',
                                'action_type' => 'navigate',
                                'sort_order' => 2,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Balance Result',
                        'description' => 'Show balance after PIN verification',
                        'menu_text' => "Balance Information\n\nAccount Balance: N50,000.00\nAvailable Balance: N45,000.00\n\n1. Back to main menu\n0. Exit",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Back to main menu',
                                'option_value' => '1',
                                'action_type' => 'navigate',
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Exit',
                                'option_value' => '0',
                                'action_type' => 'end_session',
                                'action_data' => ['message' => 'Thank you for using our service.'],
                                'sort_order' => 2,
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
            [
                'name' => 'Data Collection Demo',
                'description' => 'Learn and test various input text collection scenarios.',
                'pattern' => '789#',
                'is_active' => true,
                'flows' => [
                    [
                        'name' => 'Main Menu',
                        'description' => 'Main menu for data collection demo',
                        'menu_text' => "Data Collection Demo\n\n1. Customer Registration\n2. Feedback Collection\n3. Survey Form\n4. Contact Information\n0. Exit",
                        'is_root' => true,
                        'options' => [
                            [
                                'option_text' => 'Customer Registration',
                                'option_value' => '1',
                                'action_type' => 'navigate',
                                'next_flow_id' => 2,
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Feedback Collection',
                                'option_value' => '2',
                                'action_type' => 'navigate',
                                'next_flow_id' => 3,
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Survey Form',
                                'option_value' => '3',
                                'action_type' => 'navigate',
                                'next_flow_id' => 4,
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'Contact Information',
                                'option_value' => '4',
                                'action_type' => 'navigate',
                                'next_flow_id' => 5,
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
                        'name' => 'Customer Registration',
                        'description' => 'Collect customer registration information',
                        'menu_text' => "Customer Registration\n\n1. Enter Full Name\n2. Enter Email Address\n3. Enter Phone Number\n4. Enter Address\n5. Complete Registration\n0. Back to Main Menu",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Enter Full Name',
                                'option_value' => '1',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your full name (first and last name):',
                                    'validation' => '^[a-zA-Z\s]+$',
                                    'error_message' => 'Please enter a valid name (letters and spaces only)',
                                    'store_as' => 'customer_name',
                                    'success_message' => 'Name saved successfully!'
                                ],
                                'next_flow_id' => 6, // Success flow
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Enter Email Address',
                                'option_value' => '2',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your email address:',
                                    'validation' => '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
                                    'error_message' => 'Please enter a valid email address',
                                    'store_as' => 'customer_email',
                                    'success_message' => 'Email saved successfully!'
                                ],
                                'next_flow_id' => 6,
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Enter Phone Number',
                                'option_value' => '3',
                                'action_type' => 'input_phone',
                                'action_data' => [
                                    'prompt' => 'Enter your phone number:',
                                    'country_code' => '+234',
                                    'error_message' => 'Please enter a valid phone number',
                                    'store_as' => 'customer_phone',
                                    'success_message' => 'Phone number saved successfully!'
                                ],
                                'next_flow_id' => 6,
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'Enter Address',
                                'option_value' => '4',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your street address:',
                                    'validation' => '^[a-zA-Z0-9\s\.\,\-]+$',
                                    'error_message' => 'Please enter a valid address',
                                    'store_as' => 'customer_address',
                                    'success_message' => 'Address saved successfully!'
                                ],
                                'next_flow_id' => 6,
                                'sort_order' => 4,
                            ],
                            [
                                'option_text' => 'Complete Registration',
                                'option_value' => '5',
                                'action_type' => 'process_registration',
                                'action_data' => [
                                    'message' => 'Processing your registration...',
                                    'required_fields' => ['customer_name', 'customer_email', 'customer_phone'],
                                    'success_flow' => 10, // Registration Summary flow
                                    'error_message' => 'Please complete all required fields first'
                                ],
                                'next_flow_id' => 10,
                                'sort_order' => 5,
                            ],
                            [
                                'option_text' => 'Back to Main Menu',
                                'option_value' => '0',
                                'action_type' => 'navigate',
                                'sort_order' => 6,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Feedback Collection',
                        'description' => 'Collect customer feedback',
                        'menu_text' => "Feedback Collection\n\n1. Rate our service (1-5)\n2. Enter feedback comment\n3. Enter your name\n4. Enter email for follow-up\n5. Submit Feedback\n0. Back to Main Menu",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Rate our service (1-5)',
                                'option_value' => '1',
                                'action_type' => 'input_number',
                                'action_data' => [
                                    'prompt' => 'Rate our service from 1 to 5 (1=Poor, 5=Excellent):',
                                    'min' => '1',
                                    'max' => '5',
                                    'error_message' => 'Please enter a number between 1 and 5',
                                    'store_as' => 'service_rating',
                                    'success_message' => 'Rating saved successfully!'
                                ],
                                'next_flow_id' => 7, // Feedback success flow
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Enter feedback comment',
                                'option_value' => '2',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your feedback (tell us what you think):',
                                    'validation' => '^[a-zA-Z0-9\s\.\,\!\?]+$',
                                    'error_message' => 'Please enter valid feedback text',
                                    'store_as' => 'feedback_comment',
                                    'success_message' => 'Feedback comment saved successfully!'
                                ],
                                'next_flow_id' => 7,
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Enter your name',
                                'option_value' => '3',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your name (optional):',
                                    'validation' => '^[a-zA-Z\s]+$',
                                    'error_message' => 'Please enter a valid name',
                                    'store_as' => 'feedback_name',
                                    'success_message' => 'Name saved successfully!'
                                ],
                                'next_flow_id' => 7,
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'Enter email for follow-up',
                                'option_value' => '4',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your email for follow-up (optional):',
                                    'validation' => '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
                                    'error_message' => 'Please enter a valid email address',
                                    'store_as' => 'feedback_email',
                                    'success_message' => 'Email saved successfully!'
                                ],
                                'next_flow_id' => 7,
                                'sort_order' => 4,
                            ],
                            [
                                'option_text' => 'Submit Feedback',
                                'option_value' => '5',
                                'action_type' => 'process_feedback',
                                'action_data' => [
                                    'message' => 'Submitting your feedback...',
                                    'required_fields' => ['service_rating', 'feedback_comment'],
                                    'success_flow' => 11, // Feedback Summary flow
                                    'error_message' => 'Please provide a rating and comment first'
                                ],
                                'next_flow_id' => 11,
                                'sort_order' => 5,
                            ],
                            [
                                'option_text' => 'Back to Main Menu',
                                'option_value' => '0',
                                'action_type' => 'navigate',
                                'sort_order' => 6,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Survey Form',
                        'description' => 'Complete a survey with various input types',
                        'menu_text' => "Survey Form\n\n1. Enter your age\n2. Enter your occupation\n3. Enter your city\n4. Enter your income range\n5. Complete Survey\n0. Back to Main Menu",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Enter your age',
                                'option_value' => '1',
                                'action_type' => 'input_number',
                                'action_data' => [
                                    'prompt' => 'Enter your age:',
                                    'min' => '18',
                                    'max' => '100',
                                    'error_message' => 'Please enter a valid age between 18 and 100',
                                    'store_as' => 'survey_age',
                                    'success_message' => 'Age saved successfully!'
                                ],
                                'next_flow_id' => 8, // Survey success flow
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Enter your occupation',
                                'option_value' => '2',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your occupation (e.g., Teacher, Engineer, Student):',
                                    'validation' => '^[a-zA-Z\s]+$',
                                    'error_message' => 'Please enter a valid occupation',
                                    'store_as' => 'survey_occupation',
                                    'success_message' => 'Occupation saved successfully!'
                                ],
                                'next_flow_id' => 8,
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Enter your city',
                                'option_value' => '3',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your city of residence:',
                                    'validation' => '^[a-zA-Z\s]+$',
                                    'error_message' => 'Please enter a valid city name',
                                    'store_as' => 'survey_city',
                                    'success_message' => 'City saved successfully!'
                                ],
                                'next_flow_id' => 8,
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'Enter your income range',
                                'option_value' => '4',
                                'action_type' => 'input_selection',
                                'action_data' => [
                                    'prompt' => 'Select your income range:',
                                    'options' => "1. Below ₦50,000\n2. ₦50,000 - ₦100,000\n3. ₦100,000 - ₦200,000\n4. Above ₦200,000",
                                    'error_message' => 'Please select a valid option (1-4)',
                                    'store_as' => 'survey_income',
                                    'success_message' => 'Income range saved successfully!'
                                ],
                                'next_flow_id' => 8,
                                'sort_order' => 4,
                            ],
                            [
                                'option_text' => 'Complete Survey',
                                'option_value' => '5',
                                'action_type' => 'process_survey',
                                'action_data' => [
                                    'message' => 'Processing your survey...',
                                    'required_fields' => ['survey_age', 'survey_occupation', 'survey_city', 'survey_income'],
                                    'success_flow' => 12, // Survey Summary flow
                                    'error_message' => 'Please complete all survey questions first'
                                ],
                                'next_flow_id' => 12,
                                'sort_order' => 5,
                            ],
                            [
                                'option_text' => 'Back to Main Menu',
                                'option_value' => '0',
                                'action_type' => 'navigate',
                                'sort_order' => 6,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Contact Information',
                        'description' => 'Collect contact information',
                        'menu_text' => "Contact Information\n\n1. Enter your name\n2. Enter your phone\n3. Enter your email\n4. Enter your WhatsApp\n5. Save Contact Info\n0. Back to Main Menu",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Enter your name',
                                'option_value' => '1',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your full name:',
                                    'validation' => '^[a-zA-Z\s]+$',
                                    'error_message' => 'Please enter a valid name',
                                    'store_as' => 'contact_name',
                                    'success_message' => 'Name saved successfully!'
                                ],
                                'next_flow_id' => 9, // Contact success flow
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Enter your phone',
                                'option_value' => '2',
                                'action_type' => 'input_phone',
                                'action_data' => [
                                    'prompt' => 'Enter your phone number:',
                                    'country_code' => '+234',
                                    'error_message' => 'Please enter a valid phone number',
                                    'store_as' => 'contact_phone',
                                    'success_message' => 'Phone number saved successfully!'
                                ],
                                'next_flow_id' => 9,
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Enter your email',
                                'option_value' => '3',
                                'action_type' => 'input_text',
                                'action_data' => [
                                    'prompt' => 'Enter your email address:',
                                    'validation' => '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
                                    'error_message' => 'Please enter a valid email address',
                                    'store_as' => 'contact_email',
                                    'success_message' => 'Email saved successfully!'
                                ],
                                'next_flow_id' => 9,
                                'sort_order' => 3,
                            ],
                            [
                                'option_text' => 'Enter your WhatsApp',
                                'option_value' => '4',
                                'action_type' => 'input_phone',
                                'action_data' => [
                                    'prompt' => 'Enter your WhatsApp number:',
                                    'country_code' => '+234',
                                    'error_message' => 'Please enter a valid WhatsApp number',
                                    'store_as' => 'contact_whatsapp',
                                    'success_message' => 'WhatsApp number saved successfully!'
                                ],
                                'next_flow_id' => 9,
                                'sort_order' => 4,
                            ],
                            [
                                'option_text' => 'Save Contact Info',
                                'option_value' => '5',
                                'action_type' => 'process_contact',
                                'action_data' => [
                                    'message' => 'Saving your contact information...',
                                    'required_fields' => ['contact_name', 'contact_phone'],
                                    'success_flow' => 13, // Contact Summary flow
                                    'error_message' => 'Please provide at least your name and phone number'
                                ],
                                'next_flow_id' => 13,
                                'sort_order' => 5,
                            ],
                            [
                                'option_text' => 'Back to Main Menu',
                                'option_value' => '0',
                                'action_type' => 'navigate',
                                'sort_order' => 6,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Registration Summary',
                        'description' => 'Show personalized registration summary',
                        'menu_text' => "Registration Summary\n\nWelcome {customer_name}!\n\nYour registration details:\nEmail: {customer_email}\nPhone: {customer_phone}\nAddress: {customer_address}\n\nRegistration ID: REG-{timestamp}\n\n1. Back to Main Menu\n2. Edit Information\n0. Exit",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Back to Main Menu',
                                'option_value' => '1',
                                'action_type' => 'navigate',
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Edit Information',
                                'option_value' => '2',
                                'action_type' => 'navigate',
                                'next_flow_id' => 1, // Back to Customer Registration
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Exit',
                                'option_value' => '0',
                                'action_type' => 'end_session',
                                'action_data' => ['message' => 'Thank you for registering with us!'],
                                'sort_order' => 3,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Feedback Summary',
                        'description' => 'Show personalized feedback summary',
                        'menu_text' => "Feedback Summary\n\nThank you {feedback_name}!\n\nYour feedback:\nRating: {service_rating}/5 stars\nComment: {feedback_comment}\n\nFeedback ID: FB-{timestamp}\n\nWe will review your feedback and improve our services.\n\n1. Back to Main Menu\n2. Submit Another Feedback\n0. Exit",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Back to Main Menu',
                                'option_value' => '1',
                                'action_type' => 'navigate',
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Submit Another Feedback',
                                'option_value' => '2',
                                'action_type' => 'navigate',
                                'next_flow_id' => 2, // Back to Feedback Collection
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Exit',
                                'option_value' => '0',
                                'action_type' => 'end_session',
                                'action_data' => ['message' => 'Thank you for your feedback!'],
                                'sort_order' => 3,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Survey Summary',
                        'description' => 'Show personalized survey summary',
                        'menu_text' => "Survey Summary\n\nThank you for completing our survey!\n\nYour profile:\nAge: {survey_age} years\nOccupation: {survey_occupation}\nCity: {survey_city}\nIncome Range: {survey_income_text}\n\nSurvey ID: SUR-{timestamp}\n\nBased on your profile, we recommend:\n{recommendations}\n\n1. Back to Main Menu\n2. Take Another Survey\n0. Exit",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Back to Main Menu',
                                'option_value' => '1',
                                'action_type' => 'navigate',
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Take Another Survey',
                                'option_value' => '2',
                                'action_type' => 'navigate',
                                'next_flow_id' => 3, // Back to Survey Form
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Exit',
                                'option_value' => '0',
                                'action_type' => 'end_session',
                                'action_data' => ['message' => 'Thank you for participating in our survey!'],
                                'sort_order' => 3,
                            ],
                        ]
                    ],
                    [
                        'name' => 'Contact Summary',
                        'description' => 'Show personalized contact summary',
                        'menu_text' => "Contact Summary\n\nThank you {contact_name}!\n\nYour contact information:\nPhone: {contact_phone}\nEmail: {contact_email}\nWhatsApp: {contact_whatsapp}\n\nContact ID: CON-{timestamp}\n\nWe will contact you via your preferred method.\n\n1. Back to Main Menu\n2. Update Contact Info\n0. Exit",
                        'is_root' => false,
                        'options' => [
                            [
                                'option_text' => 'Back to Main Menu',
                                'option_value' => '1',
                                'action_type' => 'navigate',
                                'sort_order' => 1,
                            ],
                            [
                                'option_text' => 'Update Contact Info',
                                'option_value' => '2',
                                'action_type' => 'navigate',
                                'next_flow_id' => 4, // Back to Contact Information
                                'sort_order' => 2,
                            ],
                            [
                                'option_text' => 'Exit',
                                'option_value' => '0',
                                'action_type' => 'end_session',
                                'action_data' => ['message' => 'Thank you for providing your contact information!'],
                                'sort_order' => 3,
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
                        elseif ($ussdData['name'] === 'Customer Support') {
                            $nextFlowId = $flows[0]->id; // Default to root
                        }
                        // For Data Collection Demo USSD
                        elseif ($ussdData['name'] === 'Data Collection Demo') {
                            if ($currentFlow->name === 'Main Menu') {
                                // Main menu navigation logic
                                switch ($optionData['option_value']) {
                                    case '1': // Customer Registration
                                        $nextFlowId = $flows[1]->id; // Customer Registration flow
                                        break;
                                    case '2': // Feedback Collection
                                        $nextFlowId = $flows[2]->id; // Feedback Collection flow
                                        break;
                                    case '3': // Survey Form
                                        $nextFlowId = $flows[3]->id; // Survey Form flow
                                        break;
                                    case '4': // Contact Information
                                        $nextFlowId = $flows[4]->id; // Contact Information flow
                                        break;
                                    default:
                                        $nextFlowId = $flows[0]->id; // Default to root
                                }
                            } elseif (in_array($currentFlow->name, ['Customer Registration', 'Feedback Collection', 'Survey Form', 'Contact Information'])) {
                                // Back to main menu for sub-flows
                                $nextFlowId = $flows[0]->id;
                            } elseif (in_array($currentFlow->name, ['Registration Summary', 'Feedback Summary', 'Survey Summary', 'Contact Summary'])) {
                                // Success flows can go back to main menu
                                $nextFlowId = $flows[0]->id;
                            }
                        }
                        // For other USSD services
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
