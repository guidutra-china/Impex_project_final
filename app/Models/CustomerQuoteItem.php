<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQuoteItem extends Model
{
    protected $fillable = [
        'customer_quote_id',
        'supplier_quote_id',
        'display_name',
        'price_before_commission',
        'commission_amount',
        'price_after_commission',
        'delivery_time',
        'moq',
        'highlights',
        'notes',
        'is_selected_by_customer',
        'selected_at',
        'display_order',
    ];

    protected $casts = [
        'price_before_commission' => 'integer',
        'commission_amount' => 'integer',
        'price_after_commission' => 'integer',
        'moq' => 'integer',
        'is_selected_by_customer' => 'boolean',
        'selected_at' => 'datetime',
        'display_order' => 'integer',
    ];

    /**
     * Get the customer quote
     */
    public function customerQuote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class);
    }

    /**
     * Get the supplier quote
     */
    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
    }

    /**
     * Mark this item as selected by customer
     */
    public function markAsSelected(): void
    {
        // Unmark all other items in this quote
        $this->customerQuote->items()
            ->where('id', '!=', $this->id)
            ->update([
                'is_selected_by_customer' => false,
                'selected_at' => null,
            ]);

        // Mark this item as selected
        $this->update([
            'is_selected_by_customer' => true,
            'selected_at' => now(),
        ]);
    }

    /**
     * Get formatted price before commission
     */
    public function getFormattedPriceBeforeCommission(): string
    {
        return number_format($this->price_before_commission / 100, 2);
    }

    /**
     * Get formatted commission amount
     */
    public function getFormattedCommissionAmount(): string
    {
        return number_format($this->commission_amount / 100, 2);
    }

    /**
     * Get formatted price after commission
     */
    public function getFormattedPriceAfterCommission(): string
    {
        return number_format($this->price_after_commission / 100, 2);
    }

    /**
     * Get commission percentage
     */
    public function getCommissionPercentage(): float
    {
        if ($this->price_before_commission == 0) {
            return 0;
        }

        return ($this->commission_amount / $this->price_before_commission) * 100;
    }
}
