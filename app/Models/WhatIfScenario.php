<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatIfScenario extends Model
{
    protected $fillable = [
        'product_id',
        'created_by',
        'name',
        'description',
        'component_cost_adjustments',
        'quantity_adjustments',
        'labor_cost_adjustment',
        'overhead_cost_adjustment',
        'markup_adjustment',
        'scenario_bom_cost',
        'scenario_total_cost',
        'scenario_selling_price',
        'cost_difference',
        'cost_difference_percentage',
    ];

    protected $casts = [
        'component_cost_adjustments' => 'array',
        'quantity_adjustments' => 'array',
        'labor_cost_adjustment' => 'integer',
        'overhead_cost_adjustment' => 'integer',
        'markup_adjustment' => 'decimal:2',
        'scenario_bom_cost' => 'integer',
        'scenario_total_cost' => 'integer',
        'scenario_selling_price' => 'integer',
        'cost_difference' => 'integer',
        'cost_difference_percentage' => 'decimal:2',
    ];

    /**
     * Get the product this scenario belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created this scenario
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate scenario costs
     */
    public function calculate(): void
    {
        $product = $this->product;
        $scenarioBomCost = 0;

        // Calculate BOM cost with adjustments
        foreach ($product->bomItems as $bomItem) {
            $componentId = $bomItem->component_id;

            // Get adjusted cost (if any)
            $unitCost = $this->component_cost_adjustments[$componentId] ?? $bomItem->unit_cost;

            // Get adjusted quantity (if any)
            $quantity = $this->quantity_adjustments[$componentId] ?? $bomItem->quantity;

            // Calculate with waste
            $actualQuantity = $quantity * (1 + ($bomItem->waste_factor / 100));

            // Add to total
            $scenarioBomCost += (int) round($actualQuantity * $unitCost);
        }

        // Use adjusted or current labor/overhead
        $laborCost = $this->labor_cost_adjustment ?? $product->direct_labor_cost;
        $overheadCost = $this->overhead_cost_adjustment ?? $product->direct_overhead_cost;

        // Calculate total
        $scenarioTotalCost = $scenarioBomCost + $laborCost + $overheadCost;

        // Calculate selling price with markup
        $markup = $this->markup_adjustment ?? $product->markup_percentage;
        $scenarioSellingPrice = $markup > 0
            ? (int) round($scenarioTotalCost * (1 + ($markup / 100)))
            : $scenarioTotalCost;

        // Calculate difference from current
        $costDifference = $scenarioTotalCost - $product->total_manufacturing_cost;
        $costDifferencePercentage = $product->total_manufacturing_cost > 0
            ? (($costDifference / $product->total_manufacturing_cost) * 100)
            : 0;

        // Update scenario
        $this->update([
            'scenario_bom_cost' => $scenarioBomCost,
            'scenario_total_cost' => $scenarioTotalCost,
            'scenario_selling_price' => $scenarioSellingPrice,
            'cost_difference' => $costDifference,
            'cost_difference_percentage' => $costDifferencePercentage,
        ]);
    }

    /**
     * Get costs in dollars
     */
    public function getScenarioBomCostDollarsAttribute(): float
    {
        return $this->scenario_bom_cost / 100;
    }

    public function getScenarioTotalCostDollarsAttribute(): float
    {
        return $this->scenario_total_cost / 100;
    }

    public function getScenarioSellingPriceDollarsAttribute(): float
    {
        return $this->scenario_selling_price / 100;
    }

    public function getCostDifferenceDollarsAttribute(): float
    {
        return $this->cost_difference / 100;
    }

    /**
     * Check if scenario reduces cost
     */
    public function reducesCost(): bool
    {
        return $this->cost_difference < 0;
    }

    /**
     * Check if scenario increases cost
     */
    public function increasesCost(): bool
    {
        return $this->cost_difference > 0;
    }
}
