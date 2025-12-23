<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExternalAPIConfiguration;
use Illuminate\Support\Facades\DB;

class MarketplaceAPISeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marketplaceApis = [
            // Airtime & Data APIs
            [
                'name' => 'MTN Airtime Top-Up',
                'description' => 'Recharge MTN mobile airtime directly from USSD',
                'category' => 'marketplace',
                'marketplace_category' => 'airtime',
                'provider_name' => 'MTN Nigeria',
                'endpoint_url' => 'https://api.mtn.com/v1/airtime/recharge',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => [
                    'api_key' => '{{API_KEY}}',
                    'api_secret' => '{{API_SECRET}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'request_mapping' => [
                    'phone_number' => '{{session.phone_number}}',
                    'amount' => '{{input.amount}}',
                    'reference' => '{{session.session_id}}'
                ],
                'response_mapping' => [
                    'success' => 'data.status',
                    'message' => 'data.message',
                    'transaction_id' => 'data.transaction_id',
                    'balance' => 'data.new_balance'
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
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            [
                'name' => 'Airtel Data Bundle',
                'description' => 'Purchase Airtel data bundles via USSD',
                'category' => 'marketplace',
                'marketplace_category' => 'airtime',
                'provider_name' => 'Airtel Nigeria',
                'endpoint_url' => 'https://api.airtel.com/data/bundle/purchase',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'bearer_token',
                'auth_config' => [
                    'bearer_token' => '{{BEARER_TOKEN}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {{auth_config.bearer_token}}'
                ],
                'request_mapping' => [
                    'msisdn' => '{{session.phone_number}}',
                    'bundle_id' => '{{input.bundle_id}}',
                    'payment_method' => 'airtime'
                ],
                'response_mapping' => [
                    'success' => 'result.success',
                    'message' => 'result.message',
                    'bundle_name' => 'result.bundle_name',
                    'validity' => 'result.validity_days'
                ],
                'success_criteria' => [
                    [
                        'field' => 'result.success',
                        'operator' => 'equals',
                        'value' => true
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.result.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            // Banking APIs
            [
                'name' => 'GT Bank Balance Check',
                'description' => 'Check GT Bank account balance via USSD',
                'category' => 'marketplace',
                'marketplace_category' => 'banking',
                'provider_name' => 'GT Bank',
                'endpoint_url' => 'https://api.gtbank.com/account/balance',
                'method' => 'GET',
                'timeout' => 20,
                'retry_attempts' => 2,
                'auth_type' => 'oauth',
                'auth_config' => [
                    'client_id' => '{{CLIENT_ID}}',
                    'client_secret' => '{{CLIENT_SECRET}}',
                    'redirect_uri' => '{{REDIRECT_URI}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {{auth_config.access_token}}'
                ],
                'request_mapping' => [
                    'account_number' => '{{input.account_number}}'
                ],
                'response_mapping' => [
                    'success' => 'status',
                    'balance' => 'data.balance',
                    'currency' => 'data.currency',
                    'account_name' => 'data.account_name'
                ],
                'success_criteria' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => 'success'
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            // Payment Gateway APIs
            [
                'name' => 'Paystack Payment',
                'description' => 'Process payments via Paystack gateway',
                'category' => 'marketplace',
                'marketplace_category' => 'payment',
                'provider_name' => 'Paystack',
                'endpoint_url' => 'https://api.paystack.co/transaction/initialize',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => [
                    'secret_key' => '{{SECRET_KEY}}',
                    'public_key' => '{{PUBLIC_KEY}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {{auth_config.secret_key}}'
                ],
                'request_mapping' => [
                    'amount' => '{{input.amount}}',
                    'email' => '{{input.email}}',
                    'reference' => '{{session.session_id}}',
                    'callback_url' => '{{config.app_url}}/payment/callback'
                ],
                'response_mapping' => [
                    'success' => 'status',
                    'authorization_url' => 'data.authorization_url',
                    'reference' => 'data.reference',
                    'access_code' => 'data.access_code'
                ],
                'success_criteria' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => true
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Payment service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            // Paystack USSD Payment APIs
            [
                'name' => 'Paystack USSD Transaction Initialize',
                'description' => 'Initialize Paystack transaction with USSD payment channel',
                'category' => 'marketplace',
                'marketplace_category' => 'payment',
                'provider_name' => 'Paystack',
                'endpoint_url' => 'https://api.paystack.co/transaction/initialize',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => [
                    'secret_key' => '{{SECRET_KEY}}',
                    'public_key' => '{{PUBLIC_KEY}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {{auth_config.secret_key}}'
                ],
                'request_mapping' => [
                    'amount' => '{{input.amount}}',
                    'email' => '{{input.email}}',
                    'reference' => '{{session.session_id}}',
                    'channels' => '["ussd"]',
                    'callback_url' => '{{config.app_url}}/payment/callback'
                ],
                'response_mapping' => [
                    'success' => 'status',
                    'authorization_url' => 'data.authorization_url',
                    'reference' => 'data.reference',
                    'access_code' => 'data.access_code',
                    'ussd_code' => 'data.ussd_code'
                ],
                'success_criteria' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => true
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Payment service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            [
                'name' => 'Paystack USSD Charge',
                'description' => 'Direct USSD payment charge via Paystack',
                'category' => 'marketplace',
                'marketplace_category' => 'payment',
                'provider_name' => 'Paystack',
                'endpoint_url' => 'https://api.paystack.co/charge',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => [
                    'secret_key' => '{{SECRET_KEY}}',
                    'public_key' => '{{PUBLIC_KEY}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {{auth_config.secret_key}}'
                ],
                'request_mapping' => [
                    'amount' => '{{input.amount}}',
                    'email' => '{{input.email}}',
                    'reference' => '{{session.session_id}}',
                    'ussd' => '{"type": "{{input.ussd_type}}"}'
                ],
                'response_mapping' => [
                    'success' => 'status',
                    'reference' => 'data.reference',
                    'status' => 'data.status',
                    'display_text' => 'data.display_text',
                    'ussd_code' => 'data.ussd_code'
                ],
                'success_criteria' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => true
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Payment service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            [
                'name' => 'Paystack Create Customer',
                'description' => 'Create customer account in Paystack',
                'category' => 'marketplace',
                'marketplace_category' => 'payment',
                'provider_name' => 'Paystack',
                'endpoint_url' => 'https://api.paystack.co/customer',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => [
                    'secret_key' => '{{SECRET_KEY}}',
                    'public_key' => '{{PUBLIC_KEY}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {{auth_config.secret_key}}'
                ],
                'request_mapping' => [
                    'email' => '{{input.email}}',
                    'first_name' => '{{input.first_name}}',
                    'last_name' => '{{input.last_name}}',
                    'phone' => '{{session.phone_number}}'
                ],
                'response_mapping' => [
                    'success' => 'status',
                    'customer_code' => 'data.customer_code',
                    'customer_id' => 'data.id',
                    'email' => 'data.email',
                    'phone' => 'data.phone'
                ],
                'success_criteria' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => true
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Customer service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            [
                'name' => 'Paystack Dedicated Virtual Account',
                'description' => 'Create dedicated virtual account for customer',
                'category' => 'marketplace',
                'marketplace_category' => 'payment',
                'provider_name' => 'Paystack',
                'endpoint_url' => 'https://api.paystack.co/dedicated_account/assign',
                'method' => 'POST',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => [
                    'secret_key' => '{{SECRET_KEY}}',
                    'public_key' => '{{PUBLIC_KEY}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {{auth_config.secret_key}}'
                ],
                'request_mapping' => [
                    'customer' => '{{input.customer_code}}',
                    'preferred_bank' => '{{input.preferred_bank}}'
                ],
                'response_mapping' => [
                    'success' => 'status',
                    'account_number' => 'data.account_number',
                    'bank_name' => 'data.bank.name',
                    'bank_code' => 'data.bank.code',
                    'customer_code' => 'data.customer'
                ],
                'success_criteria' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => true
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Banking service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            [
                'name' => 'Paystack Transaction Verify',
                'description' => 'Verify Paystack transaction status',
                'category' => 'marketplace',
                'marketplace_category' => 'payment',
                'provider_name' => 'Paystack',
                'endpoint_url' => 'https://api.paystack.co/transaction/verify/{{input.reference}}',
                'method' => 'GET',
                'timeout' => 30,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => [
                    'secret_key' => '{{SECRET_KEY}}',
                    'public_key' => '{{PUBLIC_KEY}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {{auth_config.secret_key}}'
                ],
                'request_mapping' => [
                    'reference' => '{{input.reference}}'
                ],
                'response_mapping' => [
                    'success' => 'status',
                    'transaction_status' => 'data.status',
                    'amount' => 'data.amount',
                    'currency' => 'data.currency',
                    'reference' => 'data.reference',
                    'gateway_response' => 'data.gateway_response'
                ],
                'success_criteria' => [
                    [
                        'field' => 'data.status',
                        'operator' => 'equals',
                        'value' => 'success'
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Verification service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ],
            // Utility APIs
            [
                'name' => 'Ikeja Electric Bill Payment',
                'description' => 'Pay electricity bills for Ikeja Electric customers',
                'category' => 'marketplace',
                'marketplace_category' => 'utility',
                'provider_name' => 'Ikeja Electric',
                'endpoint_url' => 'https://api.ikejaelectric.com/bill/payment',
                'method' => 'POST',
                'timeout' => 45,
                'retry_attempts' => 3,
                'auth_type' => 'api_key',
                'auth_config' => [
                    'api_key' => '{{API_KEY}}',
                    'merchant_id' => '{{MERCHANT_ID}}'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key' => '{{auth_config.api_key}}'
                ],
                'request_mapping' => [
                    'meter_number' => '{{input.meter_number}}',
                    'amount' => '{{input.amount}}',
                    'customer_phone' => '{{session.phone_number}}',
                    'payment_reference' => '{{session.session_id}}'
                ],
                'response_mapping' => [
                    'success' => 'response.status',
                    'message' => 'response.message',
                    'transaction_id' => 'response.transaction_id',
                    'units' => 'response.units_credited'
                ],
                'success_criteria' => [
                    [
                        'field' => 'response.status',
                        'operator' => 'equals',
                        'value' => 'success'
                    ]
                ],
                'error_handling' => [
                    'timeout_message' => 'Electricity service temporarily unavailable. Please try again.',
                    'network_error_message' => 'Network error. Please check your connection.',
                    'api_error_message' => '{{response.message}}'
                ],
                'is_marketplace_template' => true,
                'is_active' => true,
                'is_verified' => true,
                'test_status' => 'success'
            ]
        ];

        foreach ($marketplaceApis as $api) {
            // Use direct database insert to avoid model encryption
            $data = $api;
            
            // Set user_id to null for marketplace templates
            $data['user_id'] = null;
            
            // Add environment field
            $data['environment'] = 'production';
            
            // JSON encode all array fields
            $data['auth_config'] = json_encode($api['auth_config']);
            $data['headers'] = json_encode($api['headers']);
            $data['request_mapping'] = json_encode($api['request_mapping']);
            $data['response_mapping'] = json_encode($api['response_mapping']);
            $data['success_criteria'] = json_encode($api['success_criteria']);
            $data['error_handling'] = json_encode($api['error_handling']);
            
            $data['created_at'] = now();
            $data['updated_at'] = now();
            
            DB::table('external_api_configurations')->insert($data);
        }
    }
}
