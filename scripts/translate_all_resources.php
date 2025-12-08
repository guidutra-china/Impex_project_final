<?php

/**
 * Comprehensive Translation Automation Script
 * 
 * This script processes ALL Filament Resources and their related files (Schemas, Forms, Tables)
 * to replace hardcoded labels with __() translation calls.
 */

// Define the base path
$basePath = '/home/ubuntu/Impex_project_final';

// Define comprehensive field mappings
$fieldMappings = [
    // Common fields
    "'Name'" => "__('fields.name')",
    "'Email'" => "__('fields.email')",
    "'Phone'" => "__('fields.phone')",
    "'Address'" => "__('fields.address')",
    "'City'" => "__('fields.city')",
    "'State'" => "__('fields.state')",
    "'Country'" => "__('fields.country')",
    "'ZIP Code'" => "__('fields.zip')",
    "'Tax ID'" => "__('fields.tax_id')",
    "'Code'" => "__('fields.code')",
    "'Status'" => "__('fields.status')",
    "'Notes'" => "__('fields.notes')",
    "'Description'" => "__('fields.description')",
    "'Type'" => "__('fields.type')",
    "'Reference'" => "__('fields.reference')",
    "'Website'" => "__('fields.website')",
    "'Industry'" => "__('fields.industry')",
    "'Credit Limit'" => "__('fields.credit_limit')",
    "'Brand'" => "__('fields.brand')",
    "'Model'" => "__('fields.model')",
    "'Specification'" => "__('fields.specification')",
    "'Material'" => "__('fields.material')",
    "'Color'" => "__('fields.color')",
    "'Size'" => "__('fields.size')",
    "'Barcode'" => "__('fields.barcode')",
    "'Stock'" => "__('fields.stock')",
    "'Thickness'" => "__('fields.thickness')",
    
    // Customer/Client fields
    "'Customer'" => "__('fields.customer')",
    "'Customer Name'" => "__('fields.customer_name')",
    "'Customer Code'" => "__('fields.customer_code')",
    "'Company Name'" => "__('fields.company_name')",
    "'Contact Person'" => "__('fields.contact_person')",
    "'Contact Email'" => "__('fields.contact_email')",
    "'Contact Phone'" => "__('fields.contact_phone')",
    
    // Product fields
    "'Product'" => "__('fields.product')",
    "'Product Name'" => "__('fields.product_name')",
    "'Product Code'" => "__('fields.product_code')",
    "'Supplier Code'" => "__('fields.supplier_code')",
    "'HS Code'" => "__('fields.hs_code')",
    "'Unit Price'" => "__('fields.unit_price')",
    "'Cost Price'" => "__('fields.cost_price')",
    "'Net Weight'" => "__('fields.net_weight')",
    "'Gross Weight'" => "__('fields.gross_weight')",
    "'Volume'" => "__('fields.volume')",
    "'CBM'" => "__('fields.cbm')",
    "'Pcs per Carton'" => "__('fields.pcs_per_carton')",
    "'Cartons'" => "__('fields.cartons')",
    "'Country of Origin'" => "__('fields.country_of_origin')",
    "'Category'" => "__('fields.category')",
    
    // Shipment fields
    "'Shipment'" => "__('fields.shipment')",
    "'Shipment Number'" => "__('fields.shipment_number')",
    "'Shipment Date'" => "__('fields.shipment_date')",
    "'Port of Loading'" => "__('fields.origin_port')",
    "'Port of Discharge'" => "__('fields.destination_port')",
    "'Final Destination'" => "__('fields.final_destination')",
    "'B/L Number'" => "__('fields.bl_number')",
    "'Container Numbers'" => "__('fields.container_numbers')",
    "'Vessel Name'" => "__('fields.vessel_name')",
    "'Voyage Number'" => "__('fields.voyage_number')",
    "'Shipping Method'" => "__('fields.shipping_method')",
    "'Carrier'" => "__('fields.carrier')",
    "'Tracking Number'" => "__('fields.tracking_number')",
    "'Origin Address'" => "__('fields.origin_address')",
    "'Destination Address'" => "__('fields.destination_address')",
    "'ETD'" => "__('fields.etd')",
    "'ETA'" => "__('fields.eta')",
    "'Actual Departure'" => "__('fields.actual_departure')",
    "'Actual Arrival'" => "__('fields.actual_arrival')",
    "'Freight Cost'" => "__('fields.freight_cost')",
    "'Insurance Cost'" => "__('fields.insurance_cost')",
    "'Customs Value'" => "__('fields.customs_value')",
    
    // Supplier fields
    "'Supplier'" => "__('fields.supplier')",
    "'Supplier Name'" => "__('fields.supplier_name')",
    "'Lead Time'" => "__('fields.lead_time')",
    "'MOQ'" => "__('fields.moq')",
    "'Rating'" => "__('fields.rating')",
    "'Performance'" => "__('fields.performance')",
    
    // Measurement fields
    "'Quantity'" => "__('fields.quantity')",
    "'Qty'" => "__('fields.qty')",
    "'Unit'" => "__('fields.unit')",
    "'Weight'" => "__('fields.weight')",
    "'Dimensions'" => "__('fields.dimensions')",
    "'Length'" => "__('fields.length')",
    "'Width'" => "__('fields.width')",
    "'Height'" => "__('fields.height')",
    
    // Financial fields
    "'Price'" => "__('fields.price')",
    "'Total'" => "__('fields.total')",
    "'Subtotal'" => "__('fields.subtotal')",
    "'Discount'" => "__('fields.discount')",
    "'Discount %'" => "__('fields.discount_percent')",
    "'Discount Amount'" => "__('fields.discount_amount')",
    "'Tax'" => "__('fields.tax')",
    "'Tax %'" => "__('fields.tax_percent')",
    "'Tax Amount'" => "__('fields.tax_amount')",
    "'Grand Total'" => "__('fields.grand_total')",
    "'Currency'" => "__('fields.currency')",
    "'Amount'" => "__('fields.amount')",
    "'Balance'" => "__('fields.balance')",
    "'Paid Amount'" => "__('fields.paid_amount')",
    "'Exchange Rate'" => "__('fields.exchange_rate')",
    "'Payment Terms'" => "__('fields.payment_terms')",
    
    // Invoice fields
    "'Invoice'" => "__('fields.invoice')",
    "'Invoice Number'" => "__('fields.invoice_number')",
    "'Invoice Date'" => "__('fields.invoice_date')",
    "'Due Date'" => "__('fields.due_date')",
    "'Proforma Invoice'" => "__('fields.proforma_invoice')",
    "'Commercial Invoice'" => "__('fields.commercial_invoice')",
    "'Packing List'" => "__('fields.packing_list')",
    
    // Bank fields
    "'Bank'" => "__('fields.bank')",
    "'Bank Name'" => "__('fields.bank_name')",
    "'Account Number'" => "__('fields.account_number')",
    "'Account Name'" => "__('fields.account_name')",
    "'SWIFT Code'" => "__('fields.swift_code')",
    "'SWIFT'" => "__('fields.swift_code')",
    "'IBAN'" => "__('fields.iban')",
    "'Bank Address'" => "__('fields.bank_address')",
    "'Branch'" => "__('fields.branch')",
    "'Routing Number'" => "__('fields.routing_number')",
    
    // Date fields
    "'Date'" => "__('fields.date')",
    "'Start Date'" => "__('fields.start_date')",
    "'End Date'" => "__('fields.end_date')",
    "'Order Date'" => "__('fields.order_date')",
    "'Delivery Date'" => "__('fields.delivery_date')",
    "'Expected Date'" => "__('fields.expected_date')",
    "'Confirmed Date'" => "__('fields.confirmed_date')",
    
    // Order fields
    "'Order'" => "__('fields.order')",
    "'Order Number'" => "__('fields.order_number')",
    "'PO Number'" => "__('fields.po_number')",
    "'Purchase Order'" => "__('fields.purchase_order')",
    "'RFQ'" => "__('fields.rfq')",
    "'Quote'" => "__('fields.quote')",
    "'Quote Number'" => "__('fields.quote_number')",
    "'Reference Number'" => "__('fields.reference_number')",
    
    // Warehouse fields
    "'Warehouse'" => "__('fields.warehouse')",
    "'Location'" => "__('fields.location')",
    "'Bin'" => "__('fields.bin')",
    "'Rack'" => "__('fields.rack')",
    "'Shelf'" => "__('fields.shelf')",
    "'Zone'" => "__('fields.zone')",
    "'Aisle'" => "__('fields.aisle')",
    
    // User fields
    "'User'" => "__('fields.user')",
    "'Username'" => "__('fields.username')",
    "'Password'" => "__('fields.password')",
    "'Role'" => "__('fields.role')",
    "'Permission'" => "__('fields.permission')",
    "'Permissions'" => "__('fields.permissions')",
    "'Department'" => "__('fields.department')",
    "'Position'" => "__('fields.position')",
    "'Title'" => "__('fields.title')",
    
    // Quality fields
    "'Quality'" => "__('fields.quality')",
    "'Inspection'" => "__('fields.inspection')",
    "'Inspection Date'" => "__('fields.inspection_date')",
    "'Inspector'" => "__('fields.inspector')",
    "'Defect'" => "__('fields.defect')",
    "'Defects'" => "__('fields.defects')",
    "'Passed'" => "__('fields.passed')",
    "'Failed'" => "__('fields.failed')",
    "'Result'" => "__('fields.result')",
    
    // Document fields
    "'Document'" => "__('fields.document')",
    "'Document Type'" => "__('fields.document_type')",
    "'File'" => "__('fields.file')",
    "'Attachment'" => "__('fields.attachment')",
    "'Attachments'" => "__('fields.attachments')",
    "'Upload'" => "__('fields.upload')",
    "'Download'" => "__('fields.download')",
    
    // Packing fields
    "'Packing List Number'" => "__('fields.packing_list_number')",
    "'Packing Date'" => "__('fields.packing_date')",
    "'Qty/Carton'" => "__('fields.qty_carton')",
    "'Packing Method'" => "__('fields.packing_method')",
    "'Box Type'" => "__('fields.box_type')",
    "'Packing Unit'" => "__('fields.packing_unit')",
    
    // Exporter/Importer
    "'Exporter'" => "__('fields.exporter')",
    "'Exporter Name'" => "__('fields.exporter_name')",
    "'Exporter Address'" => "__('fields.exporter_address')",
    "'Exporter Tax ID'" => "__('fields.exporter_tax_id')",
    "'Exporter Country'" => "__('fields.exporter_country')",
    "'Importer'" => "__('fields.importer')",
    "'Importer Name'" => "__('fields.importer_name')",
    "'Importer Address'" => "__('fields.importer_address')",
    "'Importer Tax ID'" => "__('fields.importer_tax_id')",
    "'Importer Country'" => "__('fields.importer_country')",
    
    // Additional fields
    "'Priority'" => "__('fields.priority')",
    "'Urgency'" => "__('fields.urgency')",
    "'Source'" => "__('fields.source')",
    "'Destination'" => "__('fields.destination')",
    "'Origin'" => "__('fields.origin')",
    "'Target'" => "__('fields.target')",
    "'Goal'" => "__('fields.goal')",
    "'Objective'" => "__('fields.objective')",
    "'Milestone'" => "__('fields.milestone')",
    "'Phase'" => "__('fields.phase')",
    "'Stage'" => "__('fields.stage')",
    "'Step'" => "__('fields.step')",
    "'Version'" => "__('fields.version')",
    "'Revision'" => "__('fields.revision')",
    "'Sequence'" => "__('fields.sequence')",
    "'Sort Order'" => "__('fields.sort_order')",
    "'Display Order'" => "__('fields.display_order')",
    "'Is Active'" => "__('fields.is_active')",
    "'Is Default'" => "__('fields.is_default')",
    "'Is Primary'" => "__('fields.is_primary')",
    "'Is Visible'" => "__('fields.is_visible')",
    "'Is Featured'" => "__('fields.is_featured')",
    "'Visibility'" => "__('fields.visibility')",
    
    // Section headings
    "'Basic Information'" => "__('common.basic_information')",
    "'Contact Information'" => "__('common.contact_information')",
    "'Address Information'" => "__('common.address_information')",
    "'Financial Information'" => "__('common.financial_information')",
    "'Shipping Information'" => "__('common.shipping_information')",
    "'Additional Information'" => "__('common.additional_information')",
    "'Settings'" => "__('common.settings')",
    "'Details'" => "__('common.details')",
    "'General'" => "__('common.general')",
    "'Advanced'" => "__('common.advanced')",
    "'Options'" => "__('common.options')",
    
    // Common actions
    "'Create'" => "__('common.create')",
    "'Edit'" => "__('common.edit')",
    "'Delete'" => "__('common.delete')",
    "'Save'" => "__('common.save')",
    "'Cancel'" => "__('common.cancel')",
    "'Confirm'" => "__('common.confirm')",
    "'Submit'" => "__('common.submit')",
    "'Search'" => "__('common.search')",
    "'Filter'" => "__('common.filter')",
    "'Export'" => "__('common.export')",
    "'Import'" => "__('common.import')",
    "'Download'" => "__('common.download')",
    "'Upload'" => "__('common.upload')",
    "'View'" => "__('common.view')",
    "'Print'" => "__('common.print')",
    "'Add Item'" => "__('common.add_item')",
    
    // Status values
    "'Active'" => "__('common.active')",
    "'Inactive'" => "__('common.inactive')",
    "'Pending'" => "__('common.pending')",
    "'Approved'" => "__('common.approved')",
    "'Rejected'" => "__('common.rejected')",
    "'Completed'" => "__('common.completed')",
    "'Cancelled'" => "__('common.cancelled')",
    "'Draft'" => "__('common.draft')",
    "'Published'" => "__('common.published')",
    "'Archived'" => "__('common.archived')",
    "'Confirmed'" => "__('common.confirmed')",
    "'Processing'" => "__('common.processing')",
    "'Shipped'" => "__('common.shipped')",
    "'Delivered'" => "__('common.delivered')",
    "'Received'" => "__('common.received')",
    "'On Hold'" => "__('common.on_hold')",
    "'In Transit'" => "__('common.in_transit')",
    "'In Progress'" => "__('common.in_progress')",
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
    
    foreach ($fieldMappings as $search => $replace) {
        // Only replace in ->label() contexts
        $patterns = [
            "/->label\(\s*{$search}\s*\)/",
            "/->placeholder\(\s*{$search}\s*\)/",
            "/->helperText\(\s*{$search}\s*\)/",
            "/->hint\(\s*{$search}\s*\)/",
        ];
        
        foreach ($patterns as $pattern) {
            $newContent = preg_replace($pattern, str_replace("'", '', $pattern) . '(' . $replace . ')', $content);
            if ($newContent !== $content) {
                $count = 0;
                $content = preg_replace($pattern, str_replace("->label(", '', str_replace("->placeholder(", '', str_replace("->helperText(", '', str_replace("->hint(", '', $pattern)))) . $replace . ')', $content, -1, $count);
                $fileSubstitutions += $count;
            }
        }
        
        // Also handle Section::make() and Fieldset::make()
        $sectionPatterns = [
            "/Section::make\(\s*{$search}\s*\)/",
            "/Fieldset::make\(\s*{$search}\s*\)/",
            "/Tabs\\\\Tab::make\(\s*{$search}\s*\)/",
        ];
        
        foreach ($sectionPatterns as $pattern) {
            $count = 0;
            $newContent = preg_replace($pattern, str_replace('::make(', '::make(' . $replace, $pattern), $content, -1, $count);
            if ($newContent !== $content) {
                $content = $newContent;
                $fileSubstitutions += $count;
            }
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $modifiedFiles++;
        $totalSubstitutions += $fileSubstitutions;
        echo "✓ " . basename(dirname($file)) . "/" . basename($file) . " - {$fileSubstitutions} substitutions\n";
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
