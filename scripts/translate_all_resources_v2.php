<?php

/**
 * Comprehensive Translation Automation Script v2
 * Fixed regex escaping issues
 */

$basePath = '/home/ubuntu/Impex_project_final';

// Define comprehensive field mappings with proper escaping
$fieldMappings = [
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
    'Description' => 'fields.description',
    'Type' => 'fields.type',
    'Reference' => 'fields.reference',
    'Website' => 'fields.website',
    'Industry' => 'fields.industry',
    'Credit Limit' => 'fields.credit_limit',
    'Brand' => 'fields.brand',
    'Model' => 'fields.model',
    'Specification' => 'fields.specification',
    'Material' => 'fields.material',
    'Color' => 'fields.color',
    'Size' => 'fields.size',
    'Barcode' => 'fields.barcode',
    'Stock' => 'fields.stock',
    'Thickness' => 'fields.thickness',
    
    // Customer/Client fields
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
    'Shipment' => 'fields.shipment',
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
    'ETD' => 'fields.etd',
    'ETA' => 'fields.eta',
    'Actual Departure' => 'fields.actual_departure',
    'Actual Arrival' => 'fields.actual_arrival',
    'Freight Cost' => 'fields.freight_cost',
    'Insurance Cost' => 'fields.insurance_cost',
    'Customs Value' => 'fields.customs_value',
    
    // Supplier fields
    'Supplier' => 'fields.supplier',
    'Supplier Name' => 'fields.supplier_name',
    'Lead Time' => 'fields.lead_time',
    'MOQ' => 'fields.moq',
    'Rating' => 'fields.rating',
    'Performance' => 'fields.performance',
    
    // Measurement fields
    'Quantity' => 'fields.quantity',
    'Qty' => 'fields.qty',
    'Unit' => 'fields.unit',
    'Weight' => 'fields.weight',
    'Dimensions' => 'fields.dimensions',
    'Length' => 'fields.length',
    'Width' => 'fields.width',
    'Height' => 'fields.height',
    
    // Financial fields
    'Price' => 'fields.price',
    'Total' => 'fields.total',
    'Subtotal' => 'fields.subtotal',
    'Discount' => 'fields.discount',
    'Tax' => 'fields.tax',
    'Grand Total' => 'fields.grand_total',
    'Currency' => 'fields.currency',
    'Amount' => 'fields.amount',
    'Balance' => 'fields.balance',
    'Paid Amount' => 'fields.paid_amount',
    'Exchange Rate' => 'fields.exchange_rate',
    'Payment Terms' => 'fields.payment_terms',
    
    // Invoice fields
    'Invoice' => 'fields.invoice',
    'Invoice Number' => 'fields.invoice_number',
    'Invoice Date' => 'fields.invoice_date',
    'Due Date' => 'fields.due_date',
    'Proforma Invoice' => 'fields.proforma_invoice',
    'Commercial Invoice' => 'fields.commercial_invoice',
    'Packing List' => 'fields.packing_list',
    
    // Bank fields
    'Bank' => 'fields.bank',
    'Bank Name' => 'fields.bank_name',
    'Account Number' => 'fields.account_number',
    'Account Name' => 'fields.account_name',
    'SWIFT Code' => 'fields.swift_code',
    'SWIFT' => 'fields.swift_code',
    'IBAN' => 'fields.iban',
    'Bank Address' => 'fields.bank_address',
    'Branch' => 'fields.branch',
    'Routing Number' => 'fields.routing_number',
    
    // Date fields
    'Date' => 'fields.date',
    'Start Date' => 'fields.start_date',
    'End Date' => 'fields.end_date',
    'Order Date' => 'fields.order_date',
    'Delivery Date' => 'fields.delivery_date',
    'Expected Date' => 'fields.expected_date',
    'Confirmed Date' => 'fields.confirmed_date',
    
    // Order fields
    'Order' => 'fields.order',
    'Order Number' => 'fields.order_number',
    'PO Number' => 'fields.po_number',
    'Purchase Order' => 'fields.purchase_order',
    'RFQ' => 'fields.rfq',
    'Quote' => 'fields.quote',
    'Quote Number' => 'fields.quote_number',
    'Reference Number' => 'fields.reference_number',
    
    // Warehouse fields
    'Warehouse' => 'fields.warehouse',
    'Location' => 'fields.location',
    
    // User fields
    'User' => 'fields.user',
    'Username' => 'fields.username',
    'Password' => 'fields.password',
    'Role' => 'fields.role',
    'Permission' => 'fields.permission',
    'Permissions' => 'fields.permissions',
    'Department' => 'fields.department',
    'Position' => 'fields.position',
    'Title' => 'fields.title',
    
    // Quality fields
    'Quality' => 'fields.quality',
    'Inspection' => 'fields.inspection',
    'Inspection Date' => 'fields.inspection_date',
    'Inspector' => 'fields.inspector',
    
    // Document fields
    'Document' => 'fields.document',
    'Document Type' => 'fields.document_type',
    'File' => 'fields.file',
    'Attachment' => 'fields.attachment',
    'Attachments' => 'fields.attachments',
    
    // Packing fields
    'Packing List Number' => 'fields.packing_list_number',
    'Packing Date' => 'fields.packing_date',
    'Packing Method' => 'fields.packing_method',
    'Box Type' => 'fields.box_type',
    'Packing Unit' => 'fields.packing_unit',
    
    // Exporter/Importer
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
    
    // Section headings
    'Basic Information' => 'common.basic_information',
    'Contact Information' => 'common.contact_information',
    'Address Information' => 'common.address_information',
    'Financial Information' => 'common.financial_information',
    'Shipping Information' => 'common.shipping_information',
    'Additional Information' => 'common.additional_information',
    'Settings' => 'common.settings',
    'Details' => 'common.details',
    'General' => 'common.general',
    'Advanced' => 'common.advanced',
    'Options' => 'common.options',
];

// Find all PHP files in Resources directory
$files = [];
$resourcesDir = $basePath . '/app/Filament/Resources';

function findPhpFiles($dir, &$files) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            findPhpFiles($path, $files);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
}

findPhpFiles($resourcesDir, $files);

echo "Found " . count($files) . " PHP files to process\n\n";

$totalSubstitutions = 0;
$modifiedFiles = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    $fileSubstitutions = 0;
    
    foreach ($fieldMappings as $label => $translationKey) {
        // Escape special regex characters in the label
        $escapedLabel = preg_quote($label, '/');
        
        // Pattern 1: ->label('Label')
        $pattern1 = "/->label\(\s*['\"]" . $escapedLabel . "['\"]\s*\)/";
        $replacement1 = "->label(__('$translationKey'))";
        $count = 0;
        $content = preg_replace($pattern1, $replacement1, $content, -1, $count);
        $fileSubstitutions += $count;
        
        // Pattern 2: ->placeholder('Label')
        $pattern2 = "/->placeholder\(\s*['\"]" . $escapedLabel . "['\"]\s*\)/";
        $replacement2 = "->placeholder(__('$translationKey'))";
        $count = 0;
        $content = preg_replace($pattern2, $replacement2, $content, -1, $count);
        $fileSubstitutions += $count;
        
        // Pattern 3: Section::make('Label')
        $pattern3 = "/Section::make\(\s*['\"]" . $escapedLabel . "['\"]\s*\)/";
        $replacement3 = "Section::make(__('$translationKey'))";
        $count = 0;
        $content = preg_replace($pattern3, $replacement3, $content, -1, $count);
        $fileSubstitutions += $count;
        
        // Pattern 4: Fieldset::make('Label')
        $pattern4 = "/Fieldset::make\(\s*['\"]" . $escapedLabel . "['\"]\s*\)/";
        $replacement4 = "Fieldset::make(__('$translationKey'))";
        $count = 0;
        $content = preg_replace($pattern4, $replacement4, $content, -1, $count);
        $fileSubstitutions += $count;
        
        // Pattern 5: Tab::make('Label')
        $pattern5 = "/Tab::make\(\s*['\"]" . $escapedLabel . "['\"]\s*\)/";
        $replacement5 = "Tab::make(__('$translationKey'))";
        $count = 0;
        $content = preg_replace($pattern5, $replacement5, $content, -1, $count);
        $fileSubstitutions += $count;
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $modifiedFiles++;
        $totalSubstitutions += $fileSubstitutions;
        $relativePath = str_replace($basePath . '/app/Filament/Resources/', '', $file);
        echo "✓ {$relativePath} - {$fileSubstitutions} substitutions\n";
    }
}

echo "\n";
echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Files processed: " . count($files) . "\n";
echo "Files modified: {$modifiedFiles}\n";
echo "Total substitutions: {$totalSubstitutions}\n";
echo "========================================\n";
