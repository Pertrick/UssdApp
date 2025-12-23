<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\USSDFlowOption;

echo "🔍 Checking Flow Options for Main Menu (ID: 155)\n\n";

$options = USSDFlowOption::where('flow_id', 155)->get();

echo "📝 Options found: {$options->count()}\n\n";

foreach ($options as $option) {
    echo "Option: {$option->option_text}\n";
    echo "  • Value: {$option->option_value}\n";
    echo "  • Next Flow ID: {$option->next_flow_id}\n";
    echo "  • Action Type: {$option->action_type}\n";
    echo "  • Action Data: " . json_encode($option->action_data) . "\n";
    echo "  • Active: " . ($option->is_active ? 'Yes' : 'No') . "\n";
    echo "  • Sort Order: {$option->sort_order}\n\n";
}

// Check if Payment Menu flow exists
$paymentFlow = \App\Models\USSDFlow::find(156);
if ($paymentFlow) {
    echo "✅ Payment Menu Flow Found:\n";
    echo "  • Name: {$paymentFlow->name}\n";
    echo "  • ID: {$paymentFlow->id}\n";
    echo "  • Type: {$paymentFlow->flow_type}\n";
    echo "  • Active: " . ($paymentFlow->is_active ? 'Yes' : 'No') . "\n\n";
} else {
    echo "❌ Payment Menu Flow not found!\n\n";
}
