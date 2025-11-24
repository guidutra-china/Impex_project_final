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
            
            // Convert decimal to cents if values are too small (< 100)
            // This handles cases where Filament passes decimal values
            if ($item->unit_price < 100 && $item->unit_price > 0) {
                $item->unit_price = (int) round($item->unit_price * 100);
            }
            if ($item->commission < 100 && $item->commission > 0) {
                $item->commission = (int) round($item->commission * 100);
            }
            if ($item->total < 100 && $item->total > 0) {
                $item->total = (int) round($item->total * 100);
            }
        });
        
        static::updating(function ($item) {
            // Convert decimal to cents if values are too small (< 100)
            if ($item->isDirty('unit_price') && $item->unit_price < 100 && $item->unit_price > 0) {
                $item->unit_price = (int) round($item->unit_price * 100);
            }
            if ($item->isDirty('commission') && $item->commission < 100 && $item->commission > 0) {
                $item->commission = (int) round($item->commission * 100);
            }
            if ($item->isDirty('total') && $item->total < 100 && $item->total > 0) {
                $item->total = (int) round($item->total * 100);
            }
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
