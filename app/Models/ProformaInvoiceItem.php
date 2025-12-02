<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaInvoiceItem extends Model
{
    protected $fillable = [
        'proforma_invoice_id',
        'supplier_quote_id',
        'quote_item_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'commission_amount',
        'commission_percent',
        'commission_type',
        'total',
        'notes',
        'delivery_days',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'delivery_days' => 'integer',
        'commission_percent' => 'decimal:2',
    ];

    /**
     * Attribute accessors for amounts (convert cents to dollars)
     */
    protected function unitPrice(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    protected function commissionAmount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    protected function total(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    /**
     * Boot the model
     */
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
        });

        // Recalculate proforma totals after save/delete
        static::saved(function ($item) {
            $item->proformaInvoice->recalculateTotals();
        });

        static::deleted(function ($item) {
            $item->proformaInvoice->recalculateTotals();
        });
    }

    /**
     * Relationships
     */
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
    }

    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class);
    }
}
