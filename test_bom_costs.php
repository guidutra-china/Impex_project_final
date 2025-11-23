<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find products with BOM items
$products = \App\Models\Product::has('bomItems')->with('bomItems.componentProduct')->get();

echo "Products with BOM items: " . $products->count() . "\n\n";

foreach ($products as $product) {
    echo "Product: {$product->name} (ID: {$product->id})\n";
    echo "BOM Material Cost: $" . number_format($product->bom_material_cost / 100, 2) . "\n";
    echo "Total Manufacturing Cost: $" . number_format($product->total_manufacturing_cost / 100, 2) . "\n";
    echo "BOM Items:\n";
    
    foreach ($product->bomItems as $item) {
        echo "  - {$item->componentProduct->name}: ";
        echo "Qty: {$item->quantity}, ";
        echo "Unit Cost: $" . number_format($item->unit_cost / 100, 2) . ", ";
        echo "Total: $" . number_format($item->total_cost / 100, 2) . "\n";
    }
    
    echo "\n";
}
