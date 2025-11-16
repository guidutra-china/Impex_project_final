<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomItem extends Model
{
    protected $fillable = [
        'product_id',
        'component_id',
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
     * Get the component for this BOM item
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
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

        // Get component cost (use fresh data if component is loaded)
        if ($this->component) {
            $this->unit_cost = $this->component->total_cost_per_unit;
        } elseif ($this->component_id) {
            $component = Component::find($this->component_id);
            $this->unit_cost = $component ? $component->total_cost_per_unit : 0;
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
        $this->calculateCosts();
        $this->save();
    }
}
