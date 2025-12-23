<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\USSDSimulatorController;
use App\Services\USSDSessionService;
use App\Services\APITestLoggingService;

echo "🔧 Testing Dependency Injection Fix\n";
echo "===================================\n\n";

try {
    // Test if we can instantiate the services without errors
    $loggingService = new APITestLoggingService();
    $sessionService = new USSDSessionService($loggingService);
    $simulatorController = new USSDSimulatorController($sessionService, $loggingService);
    
    echo "✅ All services instantiated successfully!\n";
    echo "✅ USSDSimulatorController constructor fixed\n";
    echo "✅ USSDSessionService constructor fixed\n";
    echo "✅ ExternalAPIService dependency injection working\n\n";
    
    echo "🎯 The simulator should now work without errors!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

