<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryFeature extends Model
{
    protected $fillable = [
        'category_id',
        'feature_name',
        'default_value',
        'unit',
        'sort_order',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category that owns this feature template
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get formatted feature display
     */
    public function getFormattedAttribute(): string
    {
        $display = $this->feature_name;
        if ($this->default_value) {
            $display .= ": {$this->default_value}";
        }
        if ($this->unit) {
            $display .= " {$this->unit}";
        }
        return $display;
    }
}
