<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CostHistory extends Model
{
    protected $table = 'cost_history';

    protected $fillable = [
        'costable_type',
        'costable_id',
        'changed_by',
        'cost_field',
        'old_value',
        'new_value',
        'difference',
        'percentage_change',
        'change_reason',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'old_value' => 'integer',
        'new_value' => 'integer',
        'difference' => 'integer',
        'percentage_change' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the owning costable model (Component or Product)
     */
    public function costable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the change
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Create a cost history record
     */
    public static function recordChange(
        Model $model,
        string $costField,
        int $oldValue,
        int $newValue,
        ?string $reason = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        $difference = $newValue - $oldValue;
        $percentageChange = $oldValue > 0 ? (($difference / $oldValue) * 100) : 0;

        return self::create([
            'costable_type' => get_class($model),
            'costable_id' => $model->id,
            'changed_by' => auth()->id(),
            'cost_field' => $costField,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'difference' => $difference,
            'percentage_change' => $percentageChange,
            'change_reason' => $reason,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get old value in dollars
     */
    public function getOldValueDollarsAttribute(): float
    {
        return $this->old_value / 100;
    }

    /**
     * Get new value in dollars
     */
    public function getNewValueDollarsAttribute(): float
    {
        return $this->new_value / 100;
    }

    /**
     * Get difference in dollars
     */
    public function getDifferenceDollarsAttribute(): float
    {
        return $this->difference / 100;
    }

    /**
     * Get formatted cost field name
     */
    public function getCostFieldNameAttribute(): string
    {
        return match($this->cost_field) {
            'unit_cost' => 'Unit Cost',
            'labor_cost_per_unit' => 'Labor Cost',
            'overhead_cost_per_unit' => 'Overhead Cost',
            'total_cost_per_unit' => 'Total Cost',
            'bom_material_cost' => 'BOM Material Cost',
            'direct_labor_cost' => 'Direct Labor Cost',
            'direct_overhead_cost' => 'Direct Overhead Cost',
            'total_manufacturing_cost' => 'Total Manufacturing Cost',
            default => ucwords(str_replace('_', ' ', $this->cost_field)),
        };
    }

    /**
     * Check if cost increased
     */
    public function isIncrease(): bool
    {
        return $this->difference > 0;
    }

    /**
     * Check if cost decreased
     */
    public function isDecrease(): bool
    {
        return $this->difference < 0;
    }

    /**
     * Scope: Recent changes
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: By cost field
     */
    public function scopeForField($query, string $field)
    {
        return $query->where('cost_field', $field);
    }

    /**
     * Scope: Increases only
     */
    public function scopeIncreases($query)
    {
        return $query->where('difference', '>', 0);
    }

    /**
     * Scope: Decreases only
     */
    public function scopeDecreases($query)
    {
        return $query->where('difference', '<', 0);
    }
}

