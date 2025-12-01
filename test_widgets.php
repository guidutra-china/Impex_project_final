<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing Widget Discovery\n";
echo "============================\n\n";

// Check if widgets exist
$widgets = [
    'App\Filament\Widgets\RfqStatsWidget',
    'App\Filament\Widgets\PurchaseOrderStatsWidget',
    'App\Filament\Widgets\FinancialOverviewWidget',
];

echo "1. Checking if widget classes exist:\n";
foreach ($widgets as $widget) {
    $exists = class_exists($widget);
    echo ($exists ? "✅" : "❌") . " {$widget}\n";
}

echo "\n2. Checking widget files:\n";
$widgetFiles = [
    'app/Filament/Widgets/RfqStatsWidget.php',
    'app/Filament/Widgets/PurchaseOrderStatsWidget.php',
    'app/Filament/Widgets/FinancialOverviewWidget.php',
];

foreach ($widgetFiles as $file) {
    $exists = file_exists(__DIR__.'/'.$file);
    echo ($exists ? "✅" : "❌") . " {$file}\n";
}

echo "\n3. Checking Shield configuration:\n";
$config = config('filament-shield.widgets');
echo "Prefix: " . ($config['prefix'] ?? 'not set') . "\n";
echo "Subject: " . ($config['subject'] ?? 'not set') . "\n";
echo "Excluded widgets: " . count($config['exclude'] ?? []) . "\n";

echo "\n4. Checking if widgets are registered in panel:\n";
$panel = \Filament\Facades\Filament::getPanel('admin');
$registeredWidgets = $panel->getWidgets();
echo "Total registered widgets: " . count($registeredWidgets) . "\n";
foreach ($registeredWidgets as $widget) {
    $name = is_string($widget) ? $widget : get_class($widget);
    echo "  - {$name}\n";
}

echo "\n5. Checking permissions table:\n";
try {
    $permissions = \Spatie\Permission\Models\Permission::where('name', 'LIKE', '%widget%')->get();
    echo "Widget permissions found: " . $permissions->count() . "\n";
    foreach ($permissions as $perm) {
        echo "  - {$perm->name}\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n6. Checking if Shield tabs are enabled:\n";
$shieldConfig = config('filament-shield.shield_resource.tabs');
echo "Widgets tab enabled: " . ($shieldConfig['widgets'] ? '✅ Yes' : '❌ No') . "\n";
echo "Pages tab enabled: " . ($shieldConfig['pages'] ? '✅ Yes' : '❌ No') . "\n";
echo "Resources tab enabled: " . ($shieldConfig['resources'] ? '✅ Yes' : '❌ No') . "\n";

echo "\n============================\n";
echo "✅ Test completed!\n";
