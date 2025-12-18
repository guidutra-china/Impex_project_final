<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQuoteProductSelection extends Model
{
    protected $fillable = [
        'customer_quote_id',
        'quote_item_id',
        'is_visible_to_customer',
        'is_selected_by_customer',
        'selected_at',
        'display_order',
        'custom_notes',
    ];

    protected $casts = [
        'is_visible_to_customer' => 'boolean',
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
     * Get the quote item (product from supplier quote)
     */
    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class);
    }
}
