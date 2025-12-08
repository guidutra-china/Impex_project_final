<?php

/**
 * Automated Translation Script
 * 
 * This script automatically adds __() translation helpers to all
 * ->label() calls in Filament Resource files.
 * 
 * Usage: php translate_labels.php
 */

// Mapping of common English labels to translation keys
$translations = [
    // Common fields
    'Name' => 'fields.name',
    'Email' => 'fields.email',
    'Phone' => 'fields.phone',
    'Address' => 'fields.address',
    'City' => 'fields.city',
    'State' => 'fields.state',
    'Country' => 'fields.country',
    'ZIP Code' => 'fields.zip',
    'Tax ID' => 'fields.tax_id',
    'Code' => 'fields.code',
    'Status' => 'fields.status',
    'Notes' => 'fields.notes',
    'Created At' => 'fields.created_at',
    'Updated At' => 'fields.updated_at',
    'Description' => 'fields.description',
    
    // Customer fields
    'Customer' => 'fields.customer',
    'Customer Name' => 'fields.customer_name',
    'Customer Code' => 'fields.customer_code',
    'Company Name' => 'fields.company_name',
    'Contact Person' => 'fields.contact_person',
    'Contact Email' => 'fields.contact_email',
    'Contact Phone' => 'fields.contact_phone',
    
    // Product fields
    'Product' => 'fields.product',
    'Product Name' => 'fields.product_name',
    'Product Code' => 'fields.product_code',
    'Supplier Code' => 'fields.supplier_code',
    'HS Code' => 'fields.hs_code',
    'Unit Price' => 'fields.unit_price',
    'Cost Price' => 'fields.cost_price',
    'Net Weight' => 'fields.net_weight',
    'Gross Weight' => 'fields.gross_weight',
    'Volume' => 'fields.volume',
    'CBM' => 'fields.cbm',
    'Pcs per Carton' => 'fields.pcs_per_carton',
    'Cartons' => 'fields.cartons',
    'Country of Origin' => 'fields.country_of_origin',
    'Category' => 'fields.category',
    
    // Shipment fields
    'Shipment Number' => 'fields.shipment_number',
    'Shipment Date' => 'fields.shipment_date',
    'Port of Loading' => 'fields.origin_port',
    'Port of Discharge' => 'fields.destination_port',
    'Final Destination' => 'fields.final_destination',
    'B/L Number' => 'fields.bl_number',
    'Container Numbers' => 'fields.container_numbers',
    'Vessel Name' => 'fields.vessel_name',
    'Voyage Number' => 'fields.voyage_number',
    'Shipping Method' => 'fields.shipping_method',
    'Carrier' => 'fields.carrier',
    'Tracking Number' => 'fields.tracking_number',
    'Origin Address' => 'fields.origin_address',
    'Destination Address' => 'fields.destination_address',
    
    // Supplier fields
    'Supplier' => 'fields.supplier',
    'Supplier Name' => 'fields.supplier_name',
    
    // Measurement
    'Quantity' => 'fields.quantity',
    'Qty' => 'fields.qty',
    'Unit' => 'fields.unit',
    'Weight' => 'fields.weight',
    'Dimensions' => 'fields.dimensions',
    'Length' => 'fields.length',
    'Width' => 'fields.width',
    'Height' => 'fields.height',
    
    // Financial
    'Price' => 'fields.price',
    'Total' => 'fields.total',
    'Subtotal' => 'fields.subtotal',
    'Discount' => 'fields.discount',
    'Tax' => 'fields.tax',
    'Grand Total' => 'fields.grand_total',
    'Currency' => 'fields.currency',
    'Amount' => 'fields.amount',
    
    // Dates
    'Date' => 'fields.date',
    'Start Date' => 'fields.start_date',
    'End Date' => 'fields.end_date',
    'Due Date' => 'fields.due_date',
    
    // Common actions/placeholders
    'Select' => 'common.select',
    'Select...' => 'common.select',
    'Active' => 'common.active',
    'Inactive' => 'common.inactive',
    'Pending' => 'common.pending',
    'Completed' => 'common.completed',
    'Cancelled' => 'common.cancelled',
];

// Files to process
$filesToProcess = [];

// Find all Resource files
$resourceDirs = glob(__DIR__ . '/app/Filament/Resources/*', GLOB_ONLYDIR);

foreach ($resourceDirs as $dir) {
    // Find Schema files
    $schemaFiles = glob($dir . '/Schemas/*.php');
    $filesToProcess = array_merge($filesToProcess, $schemaFiles);
    
    // Find Resource file itself
    $resourceFile = $dir . '/' . basename($dir) . '.php';
    if (file_exists($resourceFile)) {
        $filesToProcess[] = $resourceFile;
    }
}

echo "Found " . count($filesToProcess) . " files to process\n\n";

$totalReplacements = 0;
$filesModified = 0;

foreach ($filesToProcess as $file) {
    echo "Processing: " . basename($file) . "\n";
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileReplacements = 0;
    
    // Replace each label
    foreach ($translations as $english => $key) {
        // Pattern: ->label('English Text')
        $pattern = '/->label\(\s*[\'"]' . preg_quote($english, '/') . '[\'"]\s*\)/';
        $replacement = "->label(__('$key'))";
        
        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        
        if ($count > 0) {
            $content = $newContent;
            $fileReplacements += $count;
            echo "  ✓ Replaced '$english' → __('$key') ($count times)\n";
        }
    }
    
    // Only write if changes were made
    if ($content !== $originalContent) {
        // Backup original
        copy($file, $file . '.bak');
        
        // Write new content
        file_put_contents($file, $content);
        
        // Check syntax
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            echo "  ❌ Syntax error! Restoring backup...\n";
            copy($file . '.bak', $file);
            unlink($file . '.bak');
        } else {
            echo "  ✅ File modified successfully ($fileReplacements replacements)\n";
            unlink($file . '.bak');
            $filesModified++;
            $totalReplacements += $fileReplacements;
        }
    } else {
        echo "  ⊘ No changes needed\n";
    }
    
    echo "\n";
}

echo "=====================================\n";
echo "Summary:\n";
echo "  Files processed: " . count($filesToProcess) . "\n";
echo "  Files modified: $filesModified\n";
echo "  Total replacements: $totalReplacements\n";
echo "=====================================\n";

if ($filesModified > 0) {
    echo "\n✅ Translation automation complete!\n";
    echo "\nNext steps:\n";
    echo "1. Test the changes: php artisan serve\n";
    echo "2. Clear cache: php artisan optimize:clear\n";
    echo "3. Check for any missed translations\n";
    echo "4. Commit changes: git add -A && git commit -m 'feat: Add translations to forms and tables'\n";
} else {
    echo "\n⊘ No files needed translation\n";
}
