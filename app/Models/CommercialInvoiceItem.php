<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'commercial_invoice_items';

    protected $fillable = [
        'commercial_invoice_id',
        'product_id',
        'shipment_item_id',
        'proforma_invoice_item_id',
        'description',
        'hs_code',
        'country_of_origin',
        'quantity',
        'unit',
        'unit_price',
        'commission',
        'total',
        'weight',
        'volume',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'weight' => 'decimal:3',
        'volume' => 'decimal:4',
    ];

    /**
     * Get unit_price in decimal format for display
     */
    protected function unitPrice(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    /**
     * Get commission in decimal format for display
     */
    protected function commission(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    /**
     * Get total in decimal format for display
     */
    protected function total(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    // Customs calculation methods
    
    /**
     * Get unit price with customs discount applied (in cents)
     */
    public function getCustomsUnitPriceCents(): int
    {
        $invoice = $this->commercialInvoice;
        if (!$invoice || !$invoice->hasCustomsDiscount()) {
            return $this->getRawOriginal('unit_price') ?? 0;
        }
        
        $originalCents = $this->getRawOriginal('unit_price') ?? 0;
        $discountMultiplier = 1 - ($invoice->customs_discount_percentage / 100);
        return (int) round($originalCents * $discountMultiplier);
    }

    /**
     * Get unit price with customs discount applied (in decimal)
     */
    public function getCustomsUnitPrice(): float
    {
        return $this->getCustomsUnitPriceCents() / 100;
    }

    /**
     * Get total with customs discount applied (in cents)
     */
    public function getCustomsTotalCents(): int
    {
        $invoice = $this->commercialInvoice;
        if (!$invoice || !$invoice->hasCustomsDiscount()) {
            return $this->getRawOriginal('total') ?? 0;
        }
        
        $originalCents = $this->getRawOriginal('total') ?? 0;
        $discountMultiplier = 1 - ($invoice->customs_discount_percentage / 100);
        return (int) round($originalCents * $discountMultiplier);
    }

    /**
     * Get total with customs discount applied (in decimal)
     */
    public function getCustomsTotal(): float
    {
        return $this->getCustomsTotalCents() / 100;
    }

    // Relationships
    public function commercialInvoice(): BelongsTo
    {
        return $this->belongsTo(CommercialInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function proformaInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoiceItem::class);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        // Auto-fill product details
        static::creating(function ($item) {
            if ($item->product_id && !$item->description) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $item->description = $product->name;
                    $item->hs_code = $product->hs_code ?? '';
                    $item->country_of_origin = $product->country_of_origin ?? '';
                }
            }
        });

        // Recalculate invoice totals after save/delete
        static::saved(function ($item) {
            if ($item->commercialInvoice) {
                $item->commercialInvoice->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            if ($item->commercialInvoice) {
                $item->commercialInvoice->recalculateTotals();
            }
        });
    }
}
