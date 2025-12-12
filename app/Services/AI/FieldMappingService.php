<?php

namespace App\Services\AI;

class FieldMappingService
{
    /**
     * Get all available target fields for mapping
     */
    public static function getAvailableFields(): array
    {
        return [
            'basic' => [
                'sku' => [
                    'label' => 'SKU (Product Code)',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Unique product identifier',
                ],
                'supplier_code' => [
                    'label' => 'Supplier Code',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Supplier\'s product code',
                ],
                'model_number' => [
                    'label' => 'Model Number',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Manufacturer model number',
                ],
                'name' => [
                    'label' => 'Product Name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Product name/title',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'text',
                    'required' => false,
                    'description' => 'Detailed product description',
                ],
                'brand' => [
                    'label' => 'Brand',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Brand name',
                ],
            ],
            'pricing' => [
                'price' => [
                    'label' => 'Price (Selling Price)',
                    'type' => 'money',
                    'required' => false,
                    'description' => 'Selling price to customers',
                ],
                'cost' => [
                    'label' => 'Cost Price',
                    'type' => 'money',
                    'required' => false,
                    'description' => 'Cost from supplier',
                ],
                'msrp' => [
                    'label' => 'MSRP (Suggested Retail Price)',
                    'type' => 'money',
                    'required' => false,
                    'description' => 'Manufacturer suggested retail price',
                ],
            ],
            'physical' => [
                'gross_weight' => [
                    'label' => 'Gross Weight (kg)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Total weight including packaging',
                ],
                'net_weight' => [
                    'label' => 'Net Weight (kg)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Product weight only',
                ],
                'product_length' => [
                    'label' => 'Product Length (cm)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Product length',
                ],
                'product_width' => [
                    'label' => 'Product Width (cm)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Product width',
                ],
                'product_height' => [
                    'label' => 'Product Height (cm)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Product height',
                ],
            ],
            'packaging' => [
                'carton_length' => [
                    'label' => 'Carton Length (cm)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Carton box length',
                ],
                'carton_width' => [
                    'label' => 'Carton Width (cm)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Carton box width',
                ],
                'carton_height' => [
                    'label' => 'Carton Height (cm)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Carton box height',
                ],
                'carton_weight' => [
                    'label' => 'Carton Weight (kg)',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Carton box weight',
                ],
                'carton_cbm' => [
                    'label' => 'Carton CBM',
                    'type' => 'decimal',
                    'required' => false,
                    'description' => 'Carton volume in cubic meters',
                ],
                'pcs_per_carton' => [
                    'label' => 'Pieces per Carton',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Number of units per carton',
                ],
                'pcs_per_inner_box' => [
                    'label' => 'Pieces per Inner Box',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Number of units per inner box',
                ],
            ],
            'logistics' => [
                'moq' => [
                    'label' => 'MOQ (Minimum Order Quantity)',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Minimum order quantity',
                ],
                'lead_time_days' => [
                    'label' => 'Lead Time (days)',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Production/delivery lead time',
                ],
                'hs_code' => [
                    'label' => 'HS Code',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Harmonized System code for customs',
                ],
            ],
            'additional' => [
                'certifications' => [
                    'label' => 'Certifications',
                    'type' => 'text',
                    'required' => false,
                    'description' => 'Product certifications (CE, FCC, etc.)',
                ],
                'features' => [
                    'label' => 'Features',
                    'type' => 'text',
                    'required' => false,
                    'description' => 'Product features (will be parsed)',
                    'parse_options' => [
                        'parse_as_list' => true,
                        'separator' => [',', ';', '\n', '•', '-'],
                    ],
                ],
                'photo' => [
                    'label' => 'Photo (Embedded or URL)',
                    'type' => 'photo',
                    'required' => false,
                    'description' => 'Product photo - can be embedded image or URL',
                ],
                'photo_url' => [
                    'label' => 'Photo URL',
                    'type' => 'url',
                    'required' => false,
                    'description' => 'External photo URL',
                ],
            ],
            'special' => [
                '_skip' => [
                    'label' => '(Skip this column)',
                    'type' => 'skip',
                    'required' => false,
                    'description' => 'Do not import this column',
                ],
            ],
        ];
    }

    /**
     * Get flat list of fields for dropdown
     */
    public static function getFieldOptions(): array
    {
        $options = [];
        $fields = self::getAvailableFields();

        foreach ($fields as $group => $groupFields) {
            foreach ($groupFields as $key => $field) {
                $options[$key] = $field['label'];
            }
        }

        return $options;
    }

    /**
     * Get field info
     */
    public static function getFieldInfo(string $field): ?array
    {
        $allFields = self::getAvailableFields();
        
        foreach ($allFields as $group => $fields) {
            if (isset($fields[$field])) {
                return $fields[$field];
            }
        }

        return null;
    }

    /**
     * Apply mapping to raw data row
     */
    public static function applyMapping(array $rawData, array $mapping): array
    {
        $mapped = [];

        foreach ($mapping as $column => $fieldConfig) {
            $targetField = $fieldConfig['field'] ?? null;
            
            if (!$targetField || $targetField === '_skip') {
                continue;
            }

            $value = $rawData[$column] ?? null;
            
            if ($value === null || $value === '') {
                continue;
            }

            // Apply transformations based on field type
            $fieldInfo = self::getFieldInfo($targetField);
            if ($fieldInfo) {
                $value = self::transformValue($value, $fieldInfo);
            }

            $mapped[$targetField] = $value;
        }

        return $mapped;
    }

    /**
     * Transform value based on field type
     */
    protected static function transformValue($value, array $fieldInfo)
    {
        $type = $fieldInfo['type'] ?? 'string';

        return match($type) {
            'money' => self::transformMoney($value),
            'decimal' => self::transformDecimal($value),
            'integer' => self::transformInteger($value),
            'text' => self::transformText($value),
            'url' => self::transformUrl($value),
            default => (string) $value,
        };
    }

    /**
     * Transform money value to cents
     */
    protected static function transformMoney($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        // Remove currency symbols and spaces
        $value = preg_replace('/[^0-9.,]/', '', $value);
        
        // Replace comma with dot for decimal
        $value = str_replace(',', '.', $value);
        
        // Convert to float then to cents
        $float = floatval($value);
        return (int) round($float * 100);
    }

    /**
     * Transform decimal value
     */
    protected static function transformDecimal($value): ?float
    {
        if (empty($value)) {
            return null;
        }

        $value = preg_replace('/[^0-9.,]/', '', $value);
        $value = str_replace(',', '.', $value);
        
        return floatval($value);
    }

    /**
     * Transform integer value
     */
    protected static function transformInteger($value): ?int
    {
        if (empty($value)) {
            return null;
        }

        $value = preg_replace('/[^0-9]/', '', $value);
        
        return intval($value);
    }

    /**
     * Transform text value
     */
    protected static function transformText($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return trim($value);
    }

    /**
     * Transform URL value
     */
    protected static function transformUrl($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        
        // Add http:// if no protocol
        if (!preg_match('/^https?:\/\//', $value)) {
            $value = 'http://' . $value;
        }

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    /**
     * Parse features from text
     */
    public static function parseFeatures(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $features = [];
        
        // Try different separators
        $separators = ["\n", ';', '•', '-', ','];
        
        foreach ($separators as $separator) {
            if (strpos($text, $separator) !== false) {
                $parts = explode($separator, $text);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (!empty($part) && strlen($part) > 2) {
                        $features[] = $part;
                    }
                }
                break;
            }
        }

        // If no separator found, treat as single feature
        if (empty($features)) {
            $features[] = trim($text);
        }

        return array_unique($features);
    }
}
