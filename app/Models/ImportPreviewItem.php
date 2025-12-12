<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportPreviewItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_history_id',
        'row_number',
        'raw_data',
        'sku',
        'supplier_code',
        'model_number',
        'name',
        'description',
        'price',
        'cost',
        'msrp',
        'gross_weight',
        'net_weight',
        'product_length',
        'product_width',
        'product_height',
        'carton_length',
        'carton_width',
        'carton_height',
        'carton_weight',
        'carton_cbm',
        'pcs_per_carton',
        'pcs_per_inner_box',
        'moq',
        'lead_time_days',
        'hs_code',
        'brand',
        'certifications',
        'features',
        'photo_path',
        'photo_temp_path',
        'photo_url',
        'photo_status',
        'photo_extracted',
        'photo_error',
        'duplicate_status',
        'existing_product_id',
        'similarity_score',
        'differences',
        'action',
        'selected',
        'notes',
        'validation_errors',
        'validation_warnings',
        'has_errors',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'features' => 'array',
        'differences' => 'array',
        'validation_errors' => 'array',
        'validation_warnings' => 'array',
        'price' => 'integer',
        'cost' => 'integer',
        'msrp' => 'integer',
        'gross_weight' => 'decimal:2',
        'net_weight' => 'decimal:2',
        'product_length' => 'decimal:2',
        'product_width' => 'decimal:2',
        'product_height' => 'decimal:2',
        'carton_length' => 'decimal:2',
        'carton_width' => 'decimal:2',
        'carton_height' => 'decimal:2',
        'carton_weight' => 'decimal:2',
        'carton_cbm' => 'decimal:4',
        'pcs_per_carton' => 'integer',
        'pcs_per_inner_box' => 'integer',
        'moq' => 'integer',
        'lead_time_days' => 'integer',
        'similarity_score' => 'decimal:2',
        'selected' => 'boolean',
        'has_errors' => 'boolean',
        'photo_extracted' => 'boolean',
    ];

    /**
     * Get the import history
     */
    public function importHistory(): BelongsTo
    {
        return $this->belongsTo(ImportHistory::class);
    }

    /**
     * Get the existing product (if duplicate)
     */
    public function existingProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'existing_product_id');
    }

    /**
     * Check if item is a duplicate
     */
    public function isDuplicate(): bool
    {
        return $this->duplicate_status === 'duplicate';
    }

    /**
     * Check if item is similar to existing
     */
    public function isSimilar(): bool
    {
        return $this->duplicate_status === 'similar';
    }

    /**
     * Check if item is new
     */
    public function isNew(): bool
    {
        return $this->duplicate_status === 'new';
    }

    /**
     * Get badge color for duplicate status
     */
    public function getDuplicateStatusColorAttribute(): string
    {
        return match($this->duplicate_status) {
            'duplicate' => 'danger',
            'similar' => 'warning',
            'new' => 'success',
            default => 'gray',
        };
    }

    /**
     * Get badge label for duplicate status
     */
    public function getDuplicateStatusLabelAttribute(): string
    {
        return match($this->duplicate_status) {
            'duplicate' => 'Duplicate',
            'similar' => 'Similar',
            'new' => 'New',
            default => 'Unknown',
        };
    }

    /**
     * Get action color
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'import' => 'success',
            'skip' => 'gray',
            'update' => 'info',
            'merge' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        if (!$this->price) {
            return 'N/A';
        }
        return '$' . number_format($this->price / 100, 2);
    }

    /**
     * Get photo status color
     */
    public function getPhotoStatusColorAttribute(): string
    {
        return match($this->photo_status) {
            'extracted' => 'success',
            'uploaded' => 'info',
            'missing' => 'warning',
            'error' => 'danger',
            'none' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get photo status label
     */
    public function getPhotoStatusLabelAttribute(): string
    {
        return match($this->photo_status) {
            'extracted' => 'Extracted',
            'uploaded' => 'Uploaded',
            'missing' => 'Missing',
            'error' => 'Error',
            'none' => 'No Photo',
            default => 'Unknown',
        };
    }

    /**
     * Detect if this item is a duplicate of an existing product
     */
    public function detectDuplicate(): void
    {
        // Try exact SKU match first
        if (!empty($this->sku)) {
            $existing = Product::where('sku', $this->sku)->first();
            if ($existing) {
                $this->markAsDuplicate($existing, 100);
                return;
            }
        }

        // Try supplier code match
        if (!empty($this->supplier_code)) {
            $existing = Product::where('supplier_code', $this->supplier_code)->first();
            if ($existing) {
                $this->markAsDuplicate($existing, 95);
                return;
            }
        }

        // Try name similarity
        if (!empty($this->name)) {
            $existing = Product::where('name', 'LIKE', '%' . $this->name . '%')->first();
            if ($existing) {
                $similarity = $this->calculateSimilarity($this->name, $existing->name);
                if ($similarity >= 90) {
                    $this->markAsSimilar($existing, $similarity);
                    return;
                }
            }
        }

        // No duplicate found
        $this->duplicate_status = 'new';
        $this->existing_product_id = null;
        $this->similarity_score = null;
        $this->save();
    }

    /**
     * Mark as duplicate
     */
    protected function markAsDuplicate(Product $existing, float $similarity): void
    {
        $this->duplicate_status = 'duplicate';
        $this->existing_product_id = $existing->id;
        $this->similarity_score = $similarity;
        $this->action = 'skip'; // Default action for duplicates
        $this->differences = $this->calculateDifferences($existing);
        $this->save();
    }

    /**
     * Mark as similar
     */
    protected function markAsSimilar(Product $existing, float $similarity): void
    {
        $this->duplicate_status = 'similar';
        $this->existing_product_id = $existing->id;
        $this->similarity_score = $similarity;
        $this->differences = $this->calculateDifferences($existing);
        $this->save();
    }

    /**
     * Calculate similarity between two strings
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        similar_text(strtolower($str1), strtolower($str2), $percent);
        return round($percent, 2);
    }

    /**
     * Calculate differences with existing product
     */
    protected function calculateDifferences(Product $existing): array
    {
        $differences = [];

        $fields = [
            'sku', 'name', 'description', 'price', 'brand',
            'gross_weight', 'net_weight', 'moq', 'lead_time_days'
        ];

        foreach ($fields as $field) {
            $newValue = $this->$field;
            $existingValue = $existing->$field;

            if ($newValue != $existingValue && !empty($newValue)) {
                $differences[$field] = [
                    'new' => $newValue,
                    'existing' => $existingValue,
                ];
            }
        }

        return $differences;
    }

    /**
     * Validate item data
     */
    public function validate(): void
    {
        $errors = [];
        $warnings = [];

        // Required fields
        if (empty($this->name)) {
            $errors[] = 'Product name is required';
        }

        // SKU validation
        if (empty($this->sku) && empty($this->supplier_code)) {
            $warnings[] = 'No SKU or Supplier Code provided';
        }

        // Price validation
        if (!empty($this->price) && $this->price <= 0) {
            $errors[] = 'Price must be greater than 0';
        }

        // Weight validation
        if (!empty($this->gross_weight) && !empty($this->net_weight)) {
            if ($this->net_weight > $this->gross_weight) {
                $warnings[] = 'Net weight is greater than gross weight';
            }
        }

        $this->validation_errors = $errors;
        $this->validation_warnings = $warnings;
        $this->has_errors = count($errors) > 0;
        $this->save();
    }
}
