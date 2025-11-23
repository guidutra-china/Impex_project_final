<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Basic Information
        'name',
        'sku',
        'description',
        'price',
        'currency_id',
        'status',
        'category_id',

        // Manufacturing Costs
        'bom_material_cost',
        'direct_labor_cost',
        'direct_overhead_cost',
        'total_manufacturing_cost',
        'markup_percentage',
        'calculated_selling_price',

        // Relationships
        'supplier_id',
        'supplier_code',
        'client_id',
        'customer_code',

        // International Trade
        'hs_code',
        'origin_country',
        'brand',
        'model_number',

        // Order Information
        'moq',
        'moq_unit',
        'lead_time_days',
        'certifications',

        // Product Dimensions & Weight
        'net_weight',
        'gross_weight',
        'product_length',
        'product_width',
        'product_height',

        // Inner Box Packing
        'pcs_per_inner_box',
        'inner_box_length',
        'inner_box_width',
        'inner_box_height',
        'inner_box_weight',

        // Master Carton Packing
        'pcs_per_carton',
        'inner_boxes_per_carton',
        'carton_length',
        'carton_width',
        'carton_height',
        'carton_weight',
        'carton_cbm',

        // Container Loading
        'cartons_per_20ft',
        'cartons_per_40ft',
        'cartons_per_40hq',

        // Notes
        'packing_notes',
        'internal_notes',
    ];

    protected $casts = [
        'price' => 'integer',
        'moq' => 'integer',
        'lead_time_days' => 'integer',
        'net_weight' => 'decimal:3',
        'gross_weight' => 'decimal:3',
        'product_length' => 'decimal:2',
        'product_width' => 'decimal:2',
        'product_height' => 'decimal:2',
        'pcs_per_inner_box' => 'integer',
        'inner_box_length' => 'decimal:2',
        'inner_box_width' => 'decimal:2',
        'inner_box_height' => 'decimal:2',
        'inner_box_weight' => 'decimal:3',
        'pcs_per_carton' => 'integer',
        'inner_boxes_per_carton' => 'integer',
        'carton_length' => 'decimal:2',
        'carton_width' => 'decimal:2',
        'carton_height' => 'decimal:2',
        'carton_weight' => 'decimal:3',
        'carton_cbm' => 'decimal:4',
        'cartons_per_20ft' => 'integer',
        'cartons_per_40ft' => 'integer',
        'cartons_per_40hq' => 'integer',
    ];

    /**
     * Get the category that owns the product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the supplier that owns the product
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the client/customer that owns the product
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the tags for the product
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Get all files for the product
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProductFile::class);
    }

    /**
     * Get only photos for the product
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProductFile::class)->where('file_type', 'photo');
    }

    /**
     * Get only documents for the product
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProductFile::class)->where('file_type', 'document');
    }

    /**
     * Get the features for this product
     */
    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order');
    }

    /**
     * Get the BOM items for this product
     */
    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class)->orderBy('sort_order');
    }

    /**
     * Get the BOM versions for this product
     */
    public function bomVersions(): HasMany
    {
        return $this->hasMany(BomVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Get the active BOM version
     */
    public function activeBomVersion()
    {
        return $this->hasOne(BomVersion::class)->where('status', 'active')->latestOfMany();
    }

    /**
     * Get the what-if scenarios for this product
     */
    public function whatIfScenarios(): HasMany
    {
        return $this->hasMany(WhatIfScenario::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the cost history for this product
     */
    public function costHistory()
    {
        return $this->morphMany(CostHistory::class, 'costable')->orderBy('created_at', 'desc');
    }

    /**
     * Get the component products used in this product (BOM)
     */
    public function componentProducts()
    {
        return $this->belongsToMany(Product::class, 'bom_items', 'product_id', 'component_product_id')
            ->withPivot(['quantity', 'unit_of_measure', 'waste_factor', 'actual_quantity', 'unit_cost', 'total_cost', 'sort_order', 'notes', 'is_optional'])
            ->withTimestamps();
    }

    /**
     * Get products that use this product as a component (reverse BOM)
     */
    public function usedInProducts()
    {
        return $this->belongsToMany(Product::class, 'bom_items', 'component_product_id', 'product_id')
            ->withPivot(['quantity', 'unit_of_measure', 'waste_factor', 'actual_quantity', 'unit_cost', 'total_cost', 'sort_order', 'notes', 'is_optional'])
            ->withTimestamps();
    }

    /**
     * Calculate total BOM material cost
     */
    public function calculateBomMaterialCost(): int
    {
        return $this->bomItems()->sum('total_cost');
    }

    /**
     * Calculate total manufacturing cost
     */
    public function calculateManufacturingCost(): void
    {
        // Calculate BOM material cost
        $this->bom_material_cost = $this->calculateBomMaterialCost();

        // Calculate total manufacturing cost
        $this->total_manufacturing_cost = $this->bom_material_cost
            + $this->direct_labor_cost
            + $this->direct_overhead_cost;

        // Calculate selling price with markup
        if ($this->markup_percentage > 0) {
            $markupMultiplier = 1 + ($this->markup_percentage / 100);
            $this->calculated_selling_price = (int) round($this->total_manufacturing_cost * $markupMultiplier);
        } else {
            $this->calculated_selling_price = $this->total_manufacturing_cost;
        }

        $this->saveQuietly(); // Save without triggering events
    }

    /**
     * Calculate and update all costs (alias for calculateManufacturingCost)
     */
    public function calculateAndUpdateCosts(): void
    {
        $this->calculateManufacturingCost();
    }

    /**
     * Get BOM material cost in dollars
     */
    public function getBomMaterialCostDollarsAttribute(): float
    {
        return $this->bom_material_cost / 100;
    }

    /**
     * Get direct labor cost in dollars
     */
    public function getDirectLaborCostDollarsAttribute(): float
    {
        return $this->direct_labor_cost / 100;
    }

    /**
     * Get direct overhead cost in dollars
     */
    public function getDirectOverheadCostDollarsAttribute(): float
    {
        return $this->direct_overhead_cost / 100;
    }

    /**
     * Get total manufacturing cost in dollars
     */
    public function getTotalManufacturingCostDollarsAttribute(): float
    {
        return $this->total_manufacturing_cost / 100;
    }

    /**
     * Get calculated selling price in dollars
     */
    public function getCalculatedSellingPriceDollarsAttribute(): float
    {
        return $this->calculated_selling_price / 100;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price === null) {
            return '-';
        }
        return '$' . number_format($this->price / 100, 2);
    }

    /**
     * Calculate product CBM (cubic meters)
     */
    public function getProductCbmAttribute(): ?float
    {
        if ($this->product_length && $this->product_width && $this->product_height) {
            return round(($this->product_length * $this->product_width * $this->product_height) / 1000000, 4);
        }
        return null;
    }

    /**
     * Calculate inner box CBM
     */
    public function getInnerBoxCbmAttribute(): ?float
    {
        if ($this->inner_box_length && $this->inner_box_width && $this->inner_box_height) {
            return round(($this->inner_box_length * $this->inner_box_width * $this->inner_box_height) / 1000000, 4);
        }
        return null;
    }

    /**
     * Auto-calculate carton CBM if dimensions are provided
     */
    protected static function booted()
    {
        static::saving(function ($product) {
            if ($product->carton_length && $product->carton_width && $product->carton_height) {
                $product->carton_cbm = round(
                    ($product->carton_length * $product->carton_width * $product->carton_height) / 1000000,
                    4
                );
            }
        });
    }
}
