<?php

/**
 * Extended Automated Translation Script
 * 
 * This script adds __() translation helpers to ALL labels
 * 
 * Usage: php translate_labels_extended.php
 */

// COMPREHENSIVE mapping of English labels to translation keys
$translations = [
    // === COMMON FIELDS ===
    'Name' => 'fields.name',
    'Email' => 'fields.email',
    'Phone' => 'fields.phone',
    'Address' => 'fields.address',
    'City' => 'fields.city',
    'State' => 'fields.state',
    'Province' => 'fields.state',
    'Country' => 'fields.country',
    'ZIP Code' => 'fields.zip',
    'Postal Code' => 'fields.zip',
    'Tax ID' => 'fields.tax_id',
    'Code' => 'fields.code',
    'Status' => 'fields.status',
    'Notes' => 'fields.notes',
    'Created At' => 'fields.created_at',
    'Updated At' => 'fields.updated_at',
    'Description' => 'fields.description',
    'Type' => 'fields.type',
    'Reference' => 'fields.reference',
    'Remarks' => 'fields.notes',
    'Comments' => 'fields.notes',
    
    // === CUSTOMER/CLIENT FIELDS ===
    'Customer' => 'fields.customer',
    'Client' => 'fields.customer',
    'Customer Name' => 'fields.customer_name',
    'Client Name' => 'fields.customer_name',
    'Customer Code' => 'fields.customer_code',
    'Client Code' => 'fields.customer_code',
    'Company Name' => 'fields.company_name',
    'Contact Person' => 'fields.contact_person',
    'Contact Name' => 'fields.contact_person',
    'Contact Email' => 'fields.contact_email',
    'Contact Phone' => 'fields.contact_phone',
    'Website' => 'fields.website',
    'Industry' => 'fields.industry',
    'Payment Terms' => 'fields.payment_terms',
    'Credit Limit' => 'fields.credit_limit',
    
    // === PRODUCT FIELDS ===
    'Product' => 'fields.product',
    'Product Name' => 'fields.product_name',
    'Product Code' => 'fields.product_code',
    'SKU' => 'fields.product_code',
    'Supplier Code' => 'fields.supplier_code',
    'HS Code' => 'fields.hs_code',
    'Unit Price' => 'fields.unit_price',
    'Cost Price' => 'fields.cost_price',
    'Selling Price' => 'fields.unit_price',
    'Purchase Price' => 'fields.cost_price',
    'Net Weight' => 'fields.net_weight',
    'Gross Weight' => 'fields.gross_weight',
    'Volume' => 'fields.volume',
    'CBM' => 'fields.cbm',
    'Pcs per Carton' => 'fields.pcs_per_carton',
    'Pieces per Carton' => 'fields.pcs_per_carton',
    'Cartons' => 'fields.cartons',
    'Country of Origin' => 'fields.country_of_origin',
    'Origin Country' => 'fields.country_of_origin',
    'Category' => 'fields.category',
    'Brand' => 'fields.brand',
    'Model' => 'fields.model',
    'Specification' => 'fields.specification',
    'Specs' => 'fields.specification',
    'Material' => 'fields.material',
    'Color' => 'fields.color',
    'Size' => 'fields.size',
    'Barcode' => 'fields.barcode',
    'EAN' => 'fields.barcode',
    'Stock' => 'fields.stock',
    'Stock Quantity' => 'fields.stock',
    'Min Stock' => 'fields.min_stock',
    'Max Stock' => 'fields.max_stock',
    'Reorder Level' => 'fields.reorder_level',
    
    // === SHIPMENT FIELDS ===
    'Shipment' => 'fields.shipment',
    'Shipment Number' => 'fields.shipment_number',
    'Shipment Date' => 'fields.shipment_date',
    'Port of Loading' => 'fields.origin_port',
    'Loading Port' => 'fields.origin_port',
    'Port of Discharge' => 'fields.destination_port',
    'Discharge Port' => 'fields.destination_port',
    'Final Destination' => 'fields.final_destination',
    'B/L Number' => 'fields.bl_number',
    'Bill of Lading' => 'fields.bl_number',
    'Container Number' => 'fields.container_numbers',
    'Container Numbers' => 'fields.container_numbers',
    'Vessel Name' => 'fields.vessel_name',
    'Vessel' => 'fields.vessel_name',
    'Voyage Number' => 'fields.voyage_number',
    'Voyage' => 'fields.voyage_number',
    'Shipping Method' => 'fields.shipping_method',
    'Shipping Mode' => 'fields.shipping_method',
    'Carrier' => 'fields.carrier',
    'Tracking Number' => 'fields.tracking_number',
    'Tracking' => 'fields.tracking_number',
    'Origin Address' => 'fields.origin_address',
    'Destination Address' => 'fields.destination_address',
    'ETD' => 'fields.etd',
    'ETA' => 'fields.eta',
    'Estimated Departure' => 'fields.etd',
    'Estimated Arrival' => 'fields.eta',
    'Actual Departure' => 'fields.actual_departure',
    'Actual Arrival' => 'fields.actual_arrival',
    'Freight Cost' => 'fields.freight_cost',
    'Insurance Cost' => 'fields.insurance_cost',
    'Customs Value' => 'fields.customs_value',
    
    // === SUPPLIER FIELDS ===
    'Supplier' => 'fields.supplier',
    'Supplier Name' => 'fields.supplier_name',
    'Vendor' => 'fields.supplier',
    'Vendor Name' => 'fields.supplier_name',
    'Lead Time' => 'fields.lead_time',
    'MOQ' => 'fields.moq',
    'Minimum Order Quantity' => 'fields.moq',
    'Rating' => 'fields.rating',
    'Performance' => 'fields.performance',
    
    // === MEASUREMENT FIELDS ===
    'Quantity' => 'fields.quantity',
    'Qty' => 'fields.qty',
    'Unit' => 'fields.unit',
    'UOM' => 'fields.unit',
    'Unit of Measure' => 'fields.unit',
    'Weight' => 'fields.weight',
    'Dimensions' => 'fields.dimensions',
    'Length' => 'fields.length',
    'Width' => 'fields.width',
    'Height' => 'fields.height',
    'Depth' => 'fields.height',
    'Thickness' => 'fields.thickness',
    
    // === FINANCIAL FIELDS ===
    'Price' => 'fields.price',
    'Total' => 'fields.total',
    'Total Amount' => 'fields.total',
    'Subtotal' => 'fields.subtotal',
    'Discount' => 'fields.discount',
    'Discount %' => 'fields.discount_percent',
    'Discount Amount' => 'fields.discount_amount',
    'Tax' => 'fields.tax',
    'Tax %' => 'fields.tax_percent',
    'Tax Amount' => 'fields.tax_amount',
    'Grand Total' => 'fields.grand_total',
    'Final Amount' => 'fields.grand_total',
    'Currency' => 'fields.currency',
    'Amount' => 'fields.amount',
    'Value' => 'fields.amount',
    'Balance' => 'fields.balance',
    'Paid Amount' => 'fields.paid_amount',
    'Remaining' => 'fields.balance',
    'Exchange Rate' => 'fields.exchange_rate',
    'Rate' => 'fields.exchange_rate',
    
    // === INVOICE FIELDS ===
    'Invoice' => 'fields.invoice',
    'Invoice Number' => 'fields.invoice_number',
    'Invoice Date' => 'fields.invoice_date',
    'Due Date' => 'fields.due_date',
    'Payment Due' => 'fields.due_date',
    'Proforma Invoice' => 'fields.proforma_invoice',
    'Commercial Invoice' => 'fields.commercial_invoice',
    'Packing List' => 'fields.packing_list',
    
    // === BANK FIELDS ===
    'Bank' => 'fields.bank',
    'Bank Name' => 'fields.bank_name',
    'Account Number' => 'fields.account_number',
    'Account Name' => 'fields.account_name',
    'Account Holder' => 'fields.account_name',
    'SWIFT Code' => 'fields.swift_code',
    'SWIFT/BIC' => 'fields.swift_code',
    'IBAN' => 'fields.iban',
    'Bank Address' => 'fields.bank_address',
    'Branch' => 'fields.branch',
    'Branch Name' => 'fields.branch',
    'Routing Number' => 'fields.routing_number',
    
    // === DATE FIELDS ===
    'Date' => 'fields.date',
    'Start Date' => 'fields.start_date',
    'End Date' => 'fields.end_date',
    'From Date' => 'fields.start_date',
    'To Date' => 'fields.end_date',
    'Order Date' => 'fields.order_date',
    'Delivery Date' => 'fields.delivery_date',
    'Expected Date' => 'fields.expected_date',
    'Confirmed Date' => 'fields.confirmed_date',
    
    // === ORDER FIELDS ===
    'Order' => 'fields.order',
    'Order Number' => 'fields.order_number',
    'PO Number' => 'fields.po_number',
    'Purchase Order' => 'fields.purchase_order',
    'RFQ' => 'fields.rfq',
    'Quote' => 'fields.quote',
    'Quotation' => 'fields.quote',
    'Quote Number' => 'fields.quote_number',
    'Reference Number' => 'fields.reference_number',
    'Ref No' => 'fields.reference_number',
    
    // === WAREHOUSE FIELDS ===
    'Warehouse' => 'fields.warehouse',
    'Location' => 'fields.location',
    'Storage Location' => 'fields.location',
    'Bin' => 'fields.bin',
    'Rack' => 'fields.rack',
    'Shelf' => 'fields.shelf',
    'Zone' => 'fields.zone',
    'Aisle' => 'fields.aisle',
    
    // === USER FIELDS ===
    'User' => 'fields.user',
    'Username' => 'fields.username',
    'Password' => 'fields.password',
    'Role' => 'fields.role',
    'Permission' => 'fields.permission',
    'Permissions' => 'fields.permissions',
    'Department' => 'fields.department',
    'Position' => 'fields.position',
    'Title' => 'fields.title',
    'Job Title' => 'fields.title',
    
    // === QUALITY FIELDS ===
    'Quality' => 'fields.quality',
    'Inspection' => 'fields.inspection',
    'Inspection Date' => 'fields.inspection_date',
    'Inspector' => 'fields.inspector',
    'Defect' => 'fields.defect',
    'Defects' => 'fields.defects',
    'Passed' => 'fields.passed',
    'Failed' => 'fields.failed',
    'Result' => 'fields.result',
    
    // === DOCUMENT FIELDS ===
    'Document' => 'fields.document',
    'Document Type' => 'fields.document_type',
    'File' => 'fields.file',
    'Attachment' => 'fields.attachment',
    'Attachments' => 'fields.attachments',
    'Upload' => 'fields.upload',
    'Download' => 'fields.download',
    
    // === COMMON STATUS VALUES ===
    'Active' => 'common.active',
    'Inactive' => 'common.inactive',
    'Enabled' => 'common.active',
    'Disabled' => 'common.inactive',
    'Pending' => 'common.pending',
    'Approved' => 'common.approved',
    'Rejected' => 'common.rejected',
    'Completed' => 'common.completed',
    'Cancelled' => 'common.cancelled',
    'Canceled' => 'common.cancelled',
    'Draft' => 'common.draft',
    'Published' => 'common.published',
    'Archived' => 'common.archived',
    'Confirmed' => 'common.confirmed',
    'Processing' => 'common.processing',
    'Shipped' => 'common.shipped',
    'Delivered' => 'common.delivered',
    'Received' => 'common.received',
    'On Hold' => 'common.on_hold',
    'In Transit' => 'common.in_transit',
    'In Progress' => 'common.in_progress',
    
    // === COMMON ACTIONS/PLACEHOLDERS ===
    'Select' => 'common.select',
    'Select...' => 'common.select',
    'Choose' => 'common.select',
    'Choose...' => 'common.select',
    'Search' => 'common.search',
    'Search...' => 'common.search',
    'Filter' => 'common.filter',
    'Sort' => 'common.sort',
    'Yes' => 'common.yes',
    'No' => 'common.no',
    'True' => 'common.yes',
    'False' => 'common.no',
    'All' => 'common.all',
    'None' => 'common.none',
    'Other' => 'common.other',
    'N/A' => 'common.na',
    'Not Applicable' => 'common.na',
    'Optional' => 'common.optional',
    'Required' => 'common.required',
    'Default' => 'common.default',
    'Custom' => 'common.custom',
    
    // === PACKING FIELDS ===
    'Packing List Number' => 'fields.packing_list_number',
    'Packing Date' => 'fields.packing_date',
    'Qty/Carton' => 'fields.qty_carton',
    'Packing Method' => 'fields.packing_method',
    'Box Type' => 'fields.box_type',
    'Packing Unit' => 'fields.packing_unit',
    
    // === EXPORTER/IMPORTER ===
    'Exporter' => 'fields.exporter',
    'Exporter Name' => 'fields.exporter_name',
    'Exporter Address' => 'fields.exporter_address',
    'Exporter Tax ID' => 'fields.exporter_tax_id',
    'Exporter Country' => 'fields.exporter_country',
    'Importer' => 'fields.importer',
    'Importer Name' => 'fields.importer_name',
    'Importer Address' => 'fields.importer_address',
    'Importer Tax ID' => 'fields.importer_tax_id',
    'Importer Country' => 'fields.importer_country',
    
    // === ADDITIONAL COMMON FIELDS ===
    'Priority' => 'fields.priority',
    'Urgency' => 'fields.urgency',
    'Source' => 'fields.source',
    'Destination' => 'fields.destination',
    'Origin' => 'fields.origin',
    'Target' => 'fields.target',
    'Goal' => 'fields.goal',
    'Objective' => 'fields.objective',
    'Milestone' => 'fields.milestone',
    'Phase' => 'fields.phase',
    'Stage' => 'fields.stage',
    'Step' => 'fields.step',
    'Version' => 'fields.version',
    'Revision' => 'fields.revision',
    'Sequence' => 'fields.sequence',
    'Order' => 'fields.order',
    'Sort Order' => 'fields.sort_order',
    'Display Order' => 'fields.display_order',
    'Is Active' => 'fields.is_active',
    'Is Default' => 'fields.is_default',
    'Is Primary' => 'fields.is_primary',
    'Is Visible' => 'fields.is_visible',
    'Is Featured' => 'fields.is_featured',
    'Visibility' => 'fields.visibility',
    'Public' => 'common.public',
    'Private' => 'common.private',
    'Internal' => 'common.internal',
    'External' => 'common.external',
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
        // Pattern: ->label('English Text') or ->label("English Text")
        $patterns = [
            '/->label\(\s*\'' . preg_quote($english, '/') . '\'\s*\)/',
            '/->label\(\s*"' . preg_quote($english, '/') . '"\s*\)/',
        ];
        
        foreach ($patterns as $pattern) {
            $replacement = "->label(__('$key'))";
            
            $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
            
            if ($count > 0) {
                $content = $newContent;
                $fileReplacements += $count;
                echo "  ✓ Replaced '$english' → __('$key') ($count times)\n";
            }
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
    echo "\n✅ Extended translation automation complete!\n";
    echo "\nNext steps:\n";
    echo "1. Clear cache: php artisan optimize:clear\n";
    echo "2. Test in browser with both languages\n";
    echo "3. Commit changes\n";
} else {
    echo "\n⊘ No files needed translation\n";
}
