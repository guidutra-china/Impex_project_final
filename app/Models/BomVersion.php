<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomVersion extends Model
{
    protected $fillable = [
        'product_id',
        'created_by',
        'version_number',
        'version_name',
        'change_notes',
        'status',
        'activated_at',
        'archived_at',
        'bom_material_cost_snapshot',
        'direct_labor_cost_snapshot',
        'direct_overhead_cost_snapshot',
        'total_manufacturing_cost_snapshot',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'status' => 'string',
        'activated_at' => 'datetime',
        'archived_at' => 'datetime',
        'bom_material_cost_snapshot' => 'integer',
        'direct_labor_cost_snapshot' => 'integer',
        'direct_overhead_cost_snapshot' => 'integer',
        'total_manufacturing_cost_snapshot' => 'integer',
    ];

    /**
     * Get the product this version belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created this version
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the BOM items for this version
     */
    public function bomVersionItems(): HasMany
    {
        return $this->hasMany(BomVersionItem::class)->orderBy('sort_order');
    }

    /**
     * Create a new version from current BOM
     */
    public static function createFromCurrentBom(Product $product, ?string $changNotes = null, ?int $userId = null): self
    {
        // Get next version number
        $nextVersion = $product->bomVersions()->max('version_number') + 1;

        // Create version
        $version = self::create([
            'product_id' => $product->id,
            'created_by' => $userId ?? auth()->id(),
            'version_number' => $nextVersion,
            'version_name' => "v{$nextVersion}.0",
            'change_notes' => $changeNotes,
            'status' => 'draft',
            'bom_material_cost_snapshot' => $product->bom_material_cost,
            'direct_labor_cost_snapshot' => $product->direct_labor_cost,
            'direct_overhead_cost_snapshot' => $product->direct_overhead_cost,
            'total_manufacturing_cost_snapshot' => $product->total_manufacturing_cost,
        ]);

        // Copy current BOM items to version
        foreach ($product->bomItems as $bomItem) {
            BomVersionItem::create([
                'bom_version_id' => $version->id,
                'component_id' => $bomItem->component_id,
                'quantity' => $bomItem->quantity,
                'unit_of_measure' => $bomItem->unit_of_measure,
                'waste_factor' => $bomItem->waste_factor,
                'actual_quantity' => $bomItem->actual_quantity,
                'unit_cost_snapshot' => $bomItem->unit_cost,
                'total_cost_snapshot' => $bomItem->total_cost,
                'sort_order' => $bomItem->sort_order,
                'notes' => $bomItem->notes,
                'is_optional' => $bomItem->is_optional,
            ]);
        }

        return $version;
    }

    /**
     * Activate this version
     */
    public function activate(): void
    {
        // Archive all other active versions for this product
        self::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->update([
                'status' => 'archived',
                'archived_at' => now(),
            ]);

        // Activate this version
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    /**
     * Get cost snapshots in dollars
     */
    public function getBomMaterialCostDollarsAttribute(): float
    {
        return $this->bom_material_cost_snapshot / 100;
    }

    public function getDirectLaborCostDollarsAttribute(): float
    {
        return $this->direct_labor_cost_snapshot / 100;
    }

    public function getDirectOverheadCostDollarsAttribute(): float
    {
        return $this->direct_overhead_cost_snapshot / 100;
    }

    public function getTotalManufacturingCostDollarsAttribute(): float
    {
        return $this->total_manufacturing_cost_snapshot / 100;
    }

    /**
     * Get formatted version display
     */
    public function getVersionDisplayAttribute(): string
    {
        return $this->version_name ?? "Version {$this->version_number}";
    }

    /**
     * Scope: Active versions only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Draft versions only
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Archived versions only
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }
}
