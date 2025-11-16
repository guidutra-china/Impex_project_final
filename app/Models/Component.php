<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'code',
        'name',
        'description',
        'supplier_id',
        'currency_id',
        'category_id',
        'unit_cost',
        'labor_cost_per_unit',
        'overhead_cost_per_unit',
        'total_cost_per_unit',
        'unit_of_measure',
        'stock_quantity',
        'reorder_level',
        'lead_time_days',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'integer',
        'labor_cost_per_unit' => 'integer',
        'overhead_cost_per_unit' => 'integer',
        'total_cost_per_unit' => 'integer',
        'stock_quantity' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'lead_time_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Track cost changes before updating
        static::updating(function ($component) {
            if ($component->isDirty('unit_cost')) {
                CostHistory::recordChange(
                    $component,
                    'unit_cost',
                    $component->getOriginal('unit_cost'),
                    $component->unit_cost,
                    'Component cost updated'
                );
            }
            if ($component->isDirty('labor_cost_per_unit')) {
                CostHistory::recordChange(
                    $component,
                    'labor_cost_per_unit',
                    $component->getOriginal('labor_cost_per_unit'),
                    $component->labor_cost_per_unit,
                    'Labor cost updated'
                );
            }
            if ($component->isDirty('overhead_cost_per_unit')) {
                CostHistory::recordChange(
                    $component,
                    'overhead_cost_per_unit',
                    $component->getOriginal('overhead_cost_per_unit'),
                    $component->overhead_cost_per_unit,
                    'Overhead cost updated'
                );
            }
        });

        // Auto-calculate total cost when saving
        static::saving(function ($component) {
            $component->calculateTotalCost();
        });
    }

    /**
     * Get the supplier that provides this component
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the category this component belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the currency for this component
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the BOM items that use this component
     */
    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    /**
     * Get the products that use this component
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'bom_items')
                    ->withPivot(['quantity', 'unit_of_measure', 'waste_factor', 'actual_quantity', 'unit_cost', 'total_cost', 'sort_order', 'notes', 'is_optional'])
                    ->withTimestamps();
    }

    /**
     * Get the cost history for this component
     */
    public function costHistory()
    {
        return $this->morphMany(CostHistory::class, 'costable')->orderBy('created_at', 'desc');
    }

    /**
     * Calculate total cost per unit
     */
    public function calculateTotalCost(): void
    {
        $this->total_cost_per_unit = $this->unit_cost 
                                   + $this->labor_cost_per_unit 
                                   + $this->overhead_cost_per_unit;
    }

    /**
     * Get unit cost in dollars
     */
    public function getUnitCostDollarsAttribute(): float
    {
        return $this->unit_cost / 100;
    }

    /**
     * Get labor cost in dollars
     */
    public function getLaborCostDollarsAttribute(): float
    {
        return $this->labor_cost_per_unit / 100;
    }

    /**
     * Get overhead cost in dollars
     */
    public function getOverheadCostDollarsAttribute(): float
    {
        return $this->overhead_cost_per_unit / 100;
    }

    /**
     * Get total cost in dollars
     */
    public function getTotalCostDollarsAttribute(): float
    {
        return $this->total_cost_per_unit / 100;
    }

    /**
     * Check if component is low on stock
     */
    public function isLowStock(): bool
    {
        if (!$this->reorder_level) {
            return false;
        }
        
        return $this->stock_quantity <= $this->reorder_level;
    }

    /**
     * Get formatted type name
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'raw_material' => 'Raw Material',
            'purchased_part' => 'Purchased Part',
            'sub_assembly' => 'Sub-Assembly',
            'packaging' => 'Packaging',
            default => ucfirst($this->type),
        };
    }

    /**
     * Scope: Active components only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Low stock
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('reorder_level')
                     ->whereColumn('stock_quantity', '<=', 'reorder_level');
    }

    /**
     * Scope: Search
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
