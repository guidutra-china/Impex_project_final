<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'requested_unit_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'requested_unit_price' => 'integer',
    ];

    /**
     * Get the order this item belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get requested unit price in dollars
     */
    public function getRequestedUnitPriceDollarsAttribute(): ?float
    {
        return $this->requested_unit_price ? $this->requested_unit_price / 100 : null;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Convert decimal to cents if value is too small (< 100)
        static::saving(function ($item) {
            if (isset($item->requested_unit_price) && $item->requested_unit_price < 100 && $item->requested_unit_price > 0) {
                $item->requested_unit_price = (int) round($item->requested_unit_price * 100);
            }
        });
    }
}
