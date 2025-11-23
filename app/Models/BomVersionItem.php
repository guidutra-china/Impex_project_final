<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomVersionItem extends Model
{
    protected $fillable = [
        'bom_version_id',
        'component_id',
        'quantity',
        'unit_of_measure',
        'waste_factor',
        'actual_quantity',
        'unit_cost_snapshot',
        'total_cost_snapshot',
        'sort_order',
        'notes',
        'is_optional',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'waste_factor' => 'decimal:2',
        'actual_quantity' => 'decimal:4',
        'unit_cost_snapshot' => 'integer',
        'total_cost_snapshot' => 'integer',
        'sort_order' => 'integer',
        'is_optional' => 'boolean',
    ];

    /**
     * Get the BOM version this item belongs to
     */
    public function bomVersion(): BelongsTo
    {
        return $this->belongsTo(BomVersion::class);
    }

    /**
     * Get the component for this item (legacy)
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_id');
    }

    /**
     * Get the component product for this item
     */
    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_id');
    }

    /**
     * Get unit cost in dollars
     */
    public function getUnitCostDollarsAttribute(): float
    {
        return $this->unit_cost_snapshot / 100;
    }

    /**
     * Get total cost in dollars
     */
    public function getTotalCostDollarsAttribute(): float
    {
        return $this->total_cost_snapshot / 100;
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
}

