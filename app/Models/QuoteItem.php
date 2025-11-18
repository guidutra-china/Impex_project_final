<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_quote_id',
        'order_item_id',
        'product_id',
        'quantity',
        'unit_price_before_commission',
        'unit_price_after_commission',
        'total_price_before_commission',
        'total_price_after_commission',
        'converted_price_cents',
        'delivery_days',
        'supplier_part_number',
        'supplier_notes',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_before_commission' => 'integer',
        'unit_price_after_commission' => 'integer',
        'total_price_before_commission' => 'integer',
        'total_price_after_commission' => 'integer',
        'converted_price_cents' => 'integer',
        'delivery_days' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate totals
        static::saving(function ($item) {
            $item->total_price_before_commission = $item->unit_price_before_commission * $item->quantity;
            $item->total_price_after_commission = $item->unit_price_after_commission * $item->quantity;
        });
    }

    /**
     * Get the supplier quote this item belongs to
     */
    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
    }

    /**
     * Get the product for this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the order item this quote item is for
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Convert price to order currency
     *
     * @param float $exchangeRate
     */
    public function convertPrice(float $exchangeRate)
    {
        $this->converted_price_cents = (int) round($this->total_price_after_commission * $exchangeRate);
        $this->save();
    }

    /**
     * Get unit price before commission in dollars
     */
    public function getUnitPriceBeforeDollarsAttribute(): float
    {
        return $this->unit_price_before_commission / 100;
    }

    /**
     * Get unit price after commission in dollars
     */
    public function getUnitPriceAfterDollarsAttribute(): float
    {
        return $this->unit_price_after_commission / 100;
    }

    /**
     * Get total price before commission in dollars
     */
    public function getTotalPriceBeforeDollarsAttribute(): float
    {
        return $this->total_price_before_commission / 100;
    }

    /**
     * Get total price after commission in dollars
     */
    public function getTotalPriceAfterDollarsAttribute(): float
    {
        return $this->total_price_after_commission / 100;
    }

    /**
     * Get converted price in dollars
     */
    public function getConvertedPriceDollarsAttribute(): ?float
    {
        return $this->converted_price_cents ? $this->converted_price_cents / 100 : null;
    }
}