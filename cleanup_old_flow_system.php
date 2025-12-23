<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\USSD;
use App\Models\USSDFlow;
use App\Models\USSDFlowOption;
use App\Models\FlowStep;
use App\Models\FlowConfig;

echo "=== Cleaning Up Old Flow System ===\n\n";

// 1. Check for existing static flows
$staticFlows = USSDFlow::count();
echo "Found {$staticFlows} static flows\n";

if ($staticFlows > 0) {
    echo "⚠️  WARNING: You have existing static flows that may conflict with the new dynamic system.\n";
    echo "Consider migrating them or removing them.\n\n";
    
    // Show existing flows
    $flows = USSDFlow::with('options')->get();
    foreach ($flows as $flow) {
        echo "Static Flow: {$flow->name} (USSD: {$flow->ussd->name})\n";
        echo "  Options: " . $flow->options->count() . "\n";
        echo "  Is Root: " . ($flow->is_root ? 'Yes' : 'No') . "\n\n";
    }
}

// 2. Check for dynamic flows
$dynamicSteps = FlowStep::count();
$dynamicConfigs = FlowConfig::count();

echo "Dynamic Flow System Status:\n";
echo "  Flow Steps: {$dynamicSteps}\n";
echo "  Flow Configs: {$dynamicConfigs}\n\n";

// 3. Recommendations
echo "=== Recommendations ===\n\n";

if ($staticFlows > 0) {
    echo "1. 🚨 MIGRATION NEEDED:\n";
    echo "   - You have existing static flows that need to be migrated\n";
    echo "   - Consider creating a migration script to convert static flows to dynamic steps\n";
    echo "   - Or remove old flows if they're not needed\n\n";
}

echo "2. 🎯 FRONTEND UPDATES NEEDED:\n";
echo "   - Update USSD management pages to use dynamic flow builder\n";
echo "   - Remove old static flow management components\n";
echo "   - Add new dynamic flow builder interface\n\n";

echo "3. 🔧 ROUTE UPDATES:\n";
echo "   - Update USSD routes to use dynamic flow controller\n";
echo "   - Add new routes for dynamic flow management\n";
echo "   - Remove old static flow routes\n\n";

echo "4. 📊 DATABASE CLEANUP:\n";
echo "   - Consider dropping old flow tables if not needed:\n";
echo "     - ussd_flows\n";
echo "     - ussd_flow_options\n";
echo "   - Keep for now if you need to migrate data\n\n";

echo "=== Next Steps ===\n";
echo "1. Decide whether to migrate or remove old static flows\n";
echo "2. Update frontend to use dynamic flow builder\n";
echo "3. Update routes and controllers\n";
echo "4. Test the new system thoroughly\n";
echo "5. Clean up old database tables when ready\n\n";

echo "✅ Cleanup analysis complete!\n";
