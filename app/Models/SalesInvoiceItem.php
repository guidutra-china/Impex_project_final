<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'purchase_order_id',
        'purchase_order_item_id',
        'quote_item_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'commission',
        'total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    /**
     * Get unit_price in decimal format for display
     */
    protected function unitPrice(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100), // Always multiply by 100
        );
    }

    /**
     * Get commission in decimal format for display
     */
    protected function commission(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100), // Always multiply by 100
        );
    }

    /**
     * Get total in decimal format for display
     */
    protected function total(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100), // Always multiply by 100
        );
    }

    // Relationships
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        // Auto-fill product name and SKU
        static::creating(function ($item) {
            if ($item->product_id && !$item->product_name) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $item->product_name = $product->name;
                    $item->product_sku = $product->sku;
                }
            }
            // Note: Conversion now handled by Attribute casts (unitPrice, commission, total)
        });

        // Recalculate invoice totals after save/delete
        static::saved(function ($item) {
            $item->salesInvoice->recalculateTotals();
        });

        static::deleted(function ($item) {
            $item->salesInvoice->recalculateTotals();
        });
    }
}
