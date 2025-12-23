<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ExternalAPIConfiguration;
use Illuminate\Support\Facades\DB;

echo "=== Updating Marketplace API Auth Config ===\n\n";

// Define auth config templates for different auth types
$authConfigs = [
    'api_key' => [
        'api_key' => '{{API_KEY}}',
        'api_secret' => '{{API_SECRET}}'
    ],
    'bearer_token' => [
        'bearer_token' => '{{BEARER_TOKEN}}'
    ],
    'oauth' => [
        'client_id' => '{{CLIENT_ID}}',
        'client_secret' => '{{CLIENT_SECRET}}',
        'redirect_uri' => '{{REDIRECT_URI}}'
    ],
    'basic' => [
        'username' => '{{USERNAME}}',
        'password' => '{{PASSWORD}}'
    ]
];

// Get all marketplace APIs
$marketplaceApis = ExternalAPIConfiguration::where('is_marketplace_template', true)->get();

echo "Found " . $marketplaceApis->count() . " marketplace APIs\n\n";

foreach ($marketplaceApis as $api) {
    echo "Updating API: " . $api->name . " (Auth Type: " . $api->auth_type . ")\n";
    
    // Get the appropriate auth config template
    $authConfig = $authConfigs[$api->auth_type] ?? $authConfigs['api_key'];
    
    // Update directly in database to avoid encryption issues
    DB::table('external_api_configurations')
        ->where('id', $api->id)
        ->update([
            'auth_config' => json_encode($authConfig),
            'updated_at' => now()
        ]);
    
    echo "Updated auth_config: " . json_encode($authConfig) . "\n";
    echo "---\n";
}

echo "\n=== Done ===\n";

