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