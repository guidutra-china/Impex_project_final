<?php

namespace App\Services;

class ProductImportConfig
{
    /**
     * Processing timeout in seconds
     */
    public const PROCESSING_TIMEOUT = 300; // 5 minutes

    /**
     * Maximum file size in MB
     */
    public const MAX_FILE_SIZE_MB = 10;

    /**
     * Starting row for data (after headers)
     */
    public const DATA_START_ROW = 2;

    /**
     * Column mappings (Excel column => Database field)
     */
    public const COLUMN_MAPPINGS = [
        // Basic Information
        'A' => 'name',                    // Product Name (Required)
        'B' => 'sku',                     // Product Code/SKU
        'C' => 'description',             // Description
        'D' => 'status',                  // Status (active/inactive)
        'E' => 'price',                   // Price (in cents)
        'F' => 'currency_code',           // Currency Code (USD, CNY, etc.)
        
        // Photo Options
        'G' => 'photo_url',               // Photo URL (Option 1)
        'H' => 'photo_embedded',          // Photo Embedded (Option 2 - cell reference for image)
        
        // Supplier & Customer
        'I' => 'supplier_name',           // Supplier Name
        'J' => 'supplier_code',           // Supplier Code
        'K' => 'customer_name',           // Customer Name
        'L' => 'customer_code',           // Customer Code
        
        // International Trade
        'M' => 'hs_code',                 // HS Code
        'N' => 'origin_country',          // Country of Origin
        'O' => 'brand',                   // Brand
        'P' => 'model_number',            // Model Number
        
        // Order Information
        'Q' => 'moq',                     // MOQ
        'R' => 'moq_unit',                // MOQ Unit
        'S' => 'lead_time_days',          // Lead Time (days)
        'T' => 'certifications',          // Certifications
        
        // Product Dimensions
        'U' => 'net_weight',              // Net Weight (kg)
        'V' => 'gross_weight',            // Gross Weight (kg)
        'W' => 'product_length',          // Length (cm)
        'X' => 'product_width',           // Width (cm)
        'Y' => 'product_height',          // Height (cm)
        
        // Inner Box Packing
        'Z' => 'pcs_per_inner_box',       // Pcs per Inner Box
        'AA' => 'inner_box_length',       // Inner Box Length (cm)
        'AB' => 'inner_box_width',        // Inner Box Width (cm)
        'AC' => 'inner_box_height',       // Inner Box Height (cm)
        'AD' => 'inner_box_weight',       // Inner Box Weight (kg)
        
        // Master Carton Packing
        'AE' => 'pcs_per_carton',         // Pcs per Carton
        'AF' => 'inner_boxes_per_carton', // Inner Boxes per Carton
        'AG' => 'carton_length',          // Carton Length (cm)
        'AH' => 'carton_width',           // Carton Width (cm)
        'AI' => 'carton_height',          // Carton Height (cm)
        'AJ' => 'carton_weight',          // Carton Weight (kg)
        'AK' => 'carton_cbm',             // Carton CBM
        
        // Container Loading
        'AL' => 'cartons_per_20ft',       // Cartons per 20ft
        'AM' => 'cartons_per_40ft',       // Cartons per 40ft
        'AN' => 'cartons_per_40hq',       // Cartons per 40HQ
        
        // Notes
        'AO' => 'packing_notes',          // Packing Notes
        'AP' => 'internal_notes',         // Internal Notes
        
        // Tags
        'AQ' => 'tags',                   // Tags (comma-separated)
    ];

    /**
     * Required fields that must have values
     */
    public const REQUIRED_FIELDS = [
        'name',
        'tags', // At least one tag is required
    ];

    /**
     * Numeric fields that should be converted to integers (stored in cents)
     */
    public const PRICE_FIELDS = [
        'price',
    ];

    /**
     * Numeric fields (integers)
     */
    public const INTEGER_FIELDS = [
        'moq',
        'lead_time_days',
        'pcs_per_inner_box',
        'pcs_per_carton',
        'inner_boxes_per_carton',
        'cartons_per_20ft',
        'cartons_per_40ft',
        'cartons_per_40hq',
    ];

    /**
     * Decimal fields
     */
    public const DECIMAL_FIELDS = [
        'net_weight',
        'gross_weight',
        'product_length',
        'product_width',
        'product_height',
        'inner_box_length',
        'inner_box_width',
        'inner_box_height',
        'inner_box_weight',
        'carton_length',
        'carton_width',
        'carton_height',
        'carton_weight',
        'carton_cbm',
    ];

    /**
     * Valid status values
     */
    public const VALID_STATUSES = ['active', 'inactive'];

    /**
     * Default values for optional fields
     */
    public const DEFAULT_VALUES = [
        'status' => 'active',
        'currency_code' => 'USD',
    ];

    /**
     * Supported image formats for URL download
     */
    public const SUPPORTED_IMAGE_FORMATS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Maximum image size in MB
     */
    public const MAX_IMAGE_SIZE_MB = 5;

    /**
     * Image storage directory
     */
    public const IMAGE_STORAGE_PATH = 'products/avatars';
}
