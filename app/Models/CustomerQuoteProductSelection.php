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
        'display_order',
        'custom_notes',
    ];

    protected $casts = [
        'is_visible_to_customer' => 'boolean',
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
