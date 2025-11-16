<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFeature extends Model
{
    protected $fillable = [
        'product_id',
        'feature_name',
        'feature_value',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the product that owns the feature
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get formatted feature display
     */
    public function getFormattedAttribute(): string
    {
        $display = "{$this->feature_name}: {$this->feature_value}";
        if ($this->unit) {
            $display .= " {$this->unit}";
        }
        return $display;
    }
}