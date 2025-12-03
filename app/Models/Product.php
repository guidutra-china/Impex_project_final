<?php

namespace App\Models;

use App\Services\Product\ProductDuplicator;
use App\Services\Product\ProductFormatter;
use App\Traits\HasProductCosts;
use App\Traits\HasProductDimensions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasProductCosts, HasProductDimensions;

    protected $fillable = [
        // Basic Information
        'name',
        'sku',
        'avatar',
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
     * Duplicate this product with all related data
     * 
     * @param array $options Options for duplication
     *   - 'bom_items' (bool): Duplicate BOM items (default: true)
     *   - 'features' (bool): Duplicate features (default: true)
     *   - 'tags' (bool): Duplicate tags (default: true)
     *   - 'avatar' (bool): Duplicate avatar image (default: true)
     * 
     * @return Product The newly created duplicate product
     */
    public function duplicate(array $options = []): Product
    {
        return app(ProductDuplicator::class)->duplicate($this, $options);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return app(ProductFormatter::class)->formatPrice($this->price);
    }
}
