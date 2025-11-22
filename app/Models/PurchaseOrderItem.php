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
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'total_price' => 'integer',
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
    public function getUnitPriceFormattedAttribute(): string
    {
        return number_format($this->unit_price / 100, 2);
    }

    public function getTotalPriceFormattedAttribute(): string
    {
        return number_format($this->total_price / 100, 2);
    }

    // Methods
    public function calculateTotal(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            // Snapshot product name and SKU
            if ($item->product_id && !$item->product_name) {
                $product = Product::find($item->product_id);
                $item->product_name = $product->name;
                $item->product_sku = $product->sku;
            }
            
            // Calculate total if not set
            if (!$item->total_price) {
                $item->total_price = $item->quantity * $item->unit_price;
            }
        });
        
        static::saved(function ($item) {
            // Recalculate PO total
            $item->purchaseOrder->calculateTotal();
        });
        
        static::deleted(function ($item) {
            // Recalculate PO total
            $item->purchaseOrder->calculateTotal();
        });
    }
}
