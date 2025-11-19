<?php

namespace App\Services;

class RFQImportConfig
{
    /**
     * Allowed file MIME types for upload
     */
    public const ALLOWED_FILE_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
    ];

    /**
     * Maximum file size in kilobytes (5MB)
     */
    public const MAX_FILE_SIZE = 5120;

    /**
     * Values to skip when parsing product names or prices
     */
    public const SKIP_VALUES = ['n/a', 'na', '-', ''];

    /**
     * Excel column mapping
     */
    public const EXCEL_COLUMNS = [
        'product_name' => 'A',
        'quantity' => 'B',
        'target_price' => 'C',
        'features' => 'D',
    ];

    /**
     * Header row identifier
     */
    public const ORDER_ITEMS_HEADER = 'ORDER ITEMS';

    /**
     * Auto-generated SKU prefix for new products
     */
    public const AUTO_SKU_PREFIX = 'IMP-';

    /**
     * Number of rows to skip after finding ORDER ITEMS header
     */
    public const HEADER_OFFSET = 1;

    /**
     * Maximum number of rows to process (prevent memory leaks)
     */
    public const MAX_ROWS = 10000;

    /**
     * Maximum product name length
     */
    public const MAX_PRODUCT_NAME_LENGTH = 255;

    /**
     * Maximum quantity value
     */
    public const MAX_QUANTITY = 1000000;

    /**
     * Maximum price value in cents
     */
    public const MAX_PRICE = 999999999; // ~$10 million
}
