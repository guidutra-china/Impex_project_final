<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'allocated_quantity',
        'unit_cost',
        'total_cost',
        'selling_price',
        'selling_total',
        'product_name',
        'product_sku',
        'expected_delivery_date',
        'actual_delivery_date',
        'notes',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-fill product_name and product_sku from product if not provided
        static::saving(function ($item) {
            if ($item->product_id && (!$item->product_name || !$item->product_sku)) {
                $product = Product::find($item->product_id);
                if ($product) {
                    if (!$item->product_name) {
                        $item->product_name = $product->name;
                    }
                    if (!$item->product_sku) {
                        $item->product_sku = $product->sku ?? '';
                    }
                }
            }
        });

        // Recalculate PO totals after item is saved or deleted
        static::saved(function ($item) {
            if ($item->purchaseOrder) {
                $item->purchaseOrder->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            if ($item->purchaseOrder) {
                $item->purchaseOrder->recalculateTotals();
            }
        });
    }

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getAvailableQuantityAttribute(): int
    {
        return $this->received_quantity - $this->allocated_quantity;
    }

    public function getMarginAttribute(): ?float
    {
        if (!$this->selling_price || $this->unit_cost == 0) return null;
        return (($this->selling_price - $this->unit_cost) / $this->unit_cost) * 100;
    }
}