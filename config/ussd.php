<?php

return [
    /*
    |--------------------------------------------------------------------------
    | USSD Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for USSD security features including rate limiting,
    | webhook authentication, and input validation.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    |
    | Configure webhook security settings. IP addresses can be specified as:
    | - Single IP: 192.168.1.1
    | - CIDR range: 192.168.1.0/24
    | - Multiple: comma-separated in .env or array in config
    |
    | Example .env:
    | USSD_WEBHOOK_ALLOWED_IPS="54.75.0.0/16,52.47.0.0/16,192.168.1.1"
    |
    | IP verification is OFF by default. Set USSD_ENABLE_IP_VERIFICATION=true to enable.
    |
    */
    'enable_ip_verification' => env('USSD_ENABLE_IP_VERIFICATION', false),
    'webhook_signature_required' => env('USSD_WEBHOOK_SIGNATURE_REQUIRED', false),
    
    /*
    |--------------------------------------------------------------------------
    | Webhook Allowed IP Addresses
    |--------------------------------------------------------------------------
    |
    | Specify allowed IP addresses or CIDR ranges for webhook requests.
    | Can be set as comma-separated string in .env or array here.
    |
    | Examples:
    | - Single IP: "192.168.1.1"
    | - CIDR range: "192.168.1.0/24"
    | - Multiple: "54.75.0.0/16,52.47.0.0/16,192.168.1.1"
    |
    | Set in .env: USSD_WEBHOOK_ALLOWED_IPS="54.75.0.0/16,52.47.0.0/16"
    |
    */
    'webhook_allowed_ips' => env('USSD_WEBHOOK_ALLOWED_IPS', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Webhook Secret Key
    |--------------------------------------------------------------------------
    |
    | Secret key for webhook signature verification (if supported by gateway).
    | Set this in your .env file: USSD_WEBHOOK_SECRET_KEY=your_secret_key_here
    |
    */
    'webhook_secret_key' => env('USSD_WEBHOOK_SECRET_KEY', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Webhook Rate Limiting
    |--------------------------------------------------------------------------
    */
    'webhook_rate_limit_max' => env('USSD_WEBHOOK_RATE_LIMIT_MAX', 60),
    'webhook_rate_limit_window' => env('USSD_WEBHOOK_RATE_LIMIT_WINDOW', 60), // seconds

    /*
    |--------------------------------------------------------------------------
    | Phone Number Rate Limiting
    |--------------------------------------------------------------------------
    */
    'phone_rate_limit_max' => env('USSD_PHONE_RATE_LIMIT_MAX', 30),
    'phone_rate_limit_window' => env('USSD_PHONE_RATE_LIMIT_WINDOW', 60), // seconds

    /*
    |--------------------------------------------------------------------------
    | Session Rate Limiting
    |--------------------------------------------------------------------------
    */
    'session_rate_limit_max' => env('USSD_SESSION_RATE_LIMIT_MAX', 50),
    'session_rate_limit_window' => env('USSD_SESSION_RATE_LIMIT_WINDOW', 60), // seconds

    /*
    |--------------------------------------------------------------------------
    | New Session Creation Rate Limiting
    |--------------------------------------------------------------------------
    */
    'new_session_rate_limit_max' => env('USSD_NEW_SESSION_RATE_LIMIT_MAX', 5),
    'new_session_rate_limit_window' => env('USSD_NEW_SESSION_RATE_LIMIT_WINDOW', 300), // seconds

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session_timeout' => env('USSD_SESSION_TIMEOUT', 30), // minutes
    'validate_session_ownership' => env('USSD_VALIDATE_SESSION_OWNERSHIP', true),
];
