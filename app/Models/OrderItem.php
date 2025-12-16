<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    /**
     * All of the relationships to be touched.
     * This will update the parent Order's updated_at timestamp
     * whenever an OrderItem is created, updated, or deleted.
     */
    protected $touches = ['order'];

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'requested_unit_price',
        'commission_percent',
        'commission_type',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'commission_percent' => 'decimal:2',
    ];

    /**
     * Get requested_unit_price in decimal format for display
     */
    protected function requestedUnitPrice(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

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
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // When order item is saved, update order's average commission
        static::saved(function ($item) {
            $item->order->updateCommissionAverage();
        });

        static::deleted(function ($item) {
            $item->order->updateCommissionAverage();
        });

        // Set default commission from order if not specified
        static::creating(function ($item) {
            if ($item->commission_percent === null && $item->order) {
                $item->commission_percent = $item->order->commission_percent ?? 0;
            }
            if ($item->commission_type === null && $item->order) {
                $item->commission_type = $item->order->commission_type ?? 'embedded';
            }
        });
    }
}
