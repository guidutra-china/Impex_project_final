<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteSentLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'supplier_quote_id',
        'sent_by',
        'sent_at',
        'sent_to_email',
        'notes',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the supplier quote
     */
    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
    }

    /**
     * Get the user who sent the quote
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
