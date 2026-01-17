<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'africastalking' => [
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'username' => env('AFRICASTALKING_USERNAME'),
        'environment' => env('AFRICASTALKING_ENVIRONMENT', 'sandbox'),
        
        'cost_per_session' => [
            // Default cost (used if network-specific cost not found)
            'default' => env('AFRICASTALKING_COST_PER_SESSION', 3.0), // NGN per session
            
            // Network-specific costs (optional, more accurate)
            'mtn' => env('AFRICASTALKING_COST_MTN', 3.0),
            'airtel' => env('AFRICASTALKING_COST_AIRTEL', 3.0),
            'glo' => env('AFRICASTALKING_COST_GLO', 3.0),
            '9mobile' => env('AFRICASTALKING_COST_9MOBILE', 3.0),
            'etisalat' => env('AFRICASTALKING_COST_ETISALAT', 3.0),
        ],
        
        // Currency for gateway costs
        'cost_currency' => env('AFRICASTALKING_COST_CURRENCY', 'NGN'),
        
        // Monthly setup/maintenance fee (if applicable)
        'monthly_fee' => env('AFRICASTALKING_MONTHLY_FEE', 20000.0), // NGN per month for dedicated code
        
        // Network code mapping: AfricasTalking numeric codes to network names
        'network_codes' => [
            // Nigeria
            '62130' => 'MTN',
            '62120' => 'Airtel',
            '62150' => 'Glo',
            '62160' => '9mobile',
            
            '99999' => null, // Athena sandbox -
        ],
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'enabled' => env('PAYSTACK_ENABLED', true),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'key' => env('STRIPE_KEY'),
        'enabled' => env('STRIPE_ENABLED', false),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'enabled' => env('PAYPAL_ENABLED', false),
    ],

    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'enabled' => env('FLUTTERWAVE_ENABLED', false),
    ],

    'manual' => [
        'bank_name' => env('MANUAL_BANK_NAME', ''),
        'account_number' => env('MANUAL_ACCOUNT_NUMBER', ''),
        'account_name' => env('MANUAL_ACCOUNT_NAME', ''),
    ],

];
