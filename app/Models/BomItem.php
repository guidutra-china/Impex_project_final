<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomItem extends Model
{
    protected $fillable = [
        'product_id',
        'component_product_id',  // Product used as component
        'quantity',
        'unit_of_measure',
        'waste_factor',
        'actual_quantity',
        'unit_cost',
        'total_cost',
        'sort_order',
        'notes',
        'is_optional',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'waste_factor' => 'decimal:2',
        'actual_quantity' => 'decimal:4',
        'unit_cost' => 'integer',
        'total_cost' => 'integer',
        'sort_order' => 'integer',
        'is_optional' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate costs when creating/updating
        static::saving(function ($bomItem) {
            $bomItem->calculateCosts();
        });

        // Recalculate product cost after BOM item changes
        static::saved(function ($bomItem) {
            $bomItem->product->calculateManufacturingCost();
        });

        static::deleted(function ($bomItem) {
            $bomItem->product->calculateManufacturingCost();
        });
    }

    /**
     * Get the product this BOM item belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the component product for this BOM item
     */
    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    /**
     * Calculate actual quantity including waste
     */
    public function calculateActualQuantity(): void
    {
        $wasteFactor = $this->waste_factor / 100; // Convert percentage to decimal
        $this->actual_quantity = $this->quantity * (1 + $wasteFactor);
    }

    /**
     * Calculate costs for this BOM line
     */
    public function calculateCosts(): void
    {
        // Calculate actual quantity with waste
        $this->calculateActualQuantity();

        // Get component product cost
        $componentProduct = null;
        
        // Try to use already loaded relationship first
        if ($this->relationLoaded('componentProduct') && $this->componentProduct) {
            $componentProduct = $this->componentProduct;
        } 
        // Otherwise fetch fresh from database
        elseif ($this->component_product_id) {
            $componentProduct = Product::find($this->component_product_id);
        }

        // Set unit cost from component product price
        if ($componentProduct) {
            // Priority: calculated_selling_price (if > 0) > price > 0
            // Use calculated_selling_price if it exists and is greater than 0
            if ($componentProduct->calculated_selling_price && $componentProduct->calculated_selling_price > 0) {
                $this->unit_cost = $componentProduct->calculated_selling_price;
            }
            // Otherwise use price
            elseif ($componentProduct->price && $componentProduct->price > 0) {
                $this->unit_cost = $componentProduct->price;
            }
            // If both are zero or null, set to 0
            else {
                $this->unit_cost = 0;
            }
            
            // Log for debugging (only in local/development)
            if (config('app.debug')) {
                \Log::info('BomItem calculateCosts', [
                    'bom_item_id' => $this->id,
                    'component_product_id' => $this->component_product_id,
                    'component_name' => $componentProduct->name,
                    'calculated_selling_price' => $componentProduct->calculated_selling_price,
                    'price' => $componentProduct->price,
                    'unit_cost_set' => $this->unit_cost,
                ]);
            }
        } else {
            $this->unit_cost = 0;
            
            if (config('app.debug')) {
                \Log::warning('BomItem: Component product not found', [
                    'bom_item_id' => $this->id,
                    'component_product_id' => $this->component_product_id,
                ]);
            }
        }

        // Calculate total cost for this BOM line
        $this->total_cost = (int) round($this->actual_quantity * $this->unit_cost);
    }

    /**
     * Get unit cost in dollars
     */
    public function getUnitCostDollarsAttribute(): float
    {
        return $this->unit_cost / 100;
    }

    /**
     * Get total cost in dollars
     */
    public function getTotalCostDollarsAttribute(): float
    {
        return $this->total_cost / 100;
    }

    /**
     * Get formatted quantity with UOM
     */
    public function getFormattedQuantityAttribute(): string
    {
        return number_format($this->quantity, 2) . ' ' . $this->unit_of_measure;
    }

    /**
     * Get formatted actual quantity with UOM
     */
    public function getFormattedActualQuantityAttribute(): string
    {
        return number_format($this->actual_quantity, 2) . ' ' . $this->unit_of_measure;
    }

    /**
     * Recalculate costs (useful for manual triggers)
     */
    public function recalculate(): void
    {
        // Refresh the component product relationship to get latest prices
        $this->load('componentProduct');
        
        // Recalculate costs
        $this->calculateCosts();
        
        // Save the updated costs
        $this->save();
        
        if (config('app.debug')) {
            \Log::info('BomItem recalculated', [
                'bom_item_id' => $this->id,
                'unit_cost' => $this->unit_cost,
                'total_cost' => $this->total_cost,
            ]);
        }
    }
}
