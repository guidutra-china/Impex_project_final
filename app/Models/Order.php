<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'currency_id',
        'order_number',
        'status',
        'commission_percent',
        'commission_type',
        'customer_notes',
        'notes',
        'total_amount',
        'selected_quote_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'total_amount' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate order number
        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = $order->generateOrderNumber();
            }
        });
    }

    /**
     * Get the customer (client) for this order
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    /**
     * Get the currency for this order
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the supplier quotes for this order
     */
    public function supplierQuotes(): HasMany
    {
        return $this->hasMany(SupplierQuote::class);
    }

    /**
     * Get the selected quote
     */
    public function selectedQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class, 'selected_quote_id');
    }

    /**
     * Get quotes that have been sent to customer
     */
    public function sentQuotes(): HasManyThrough
    {
        return $this->hasManyThrough(
            SupplierQuote::class,
            QuoteSentLog::class,
            'order_id',
            'id',
            'id',
            'supplier_quote_id'
        );
    }

    /**
     * Get the user who created this order
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this order
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Generate order number
     *
     * @return string
     */
    public function generateOrderNumber(): string
    {
        $year = date('Y');
        $count = Order::whereYear('created_at', $year)->count() + 1;
        return "ORD-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the cheapest quote for this order
     */
    public function getCheapestQuote()
    {
        return $this->supplierQuotes()
            ->where('status', '!=', 'draft')
            ->orderBy('total_price_after_commission', 'asc')
            ->first();
    }

    /**
     * Update total amount with cheapest quote
     */
    public function updateTotalAmount()
    {
        $cheapest = $this->getCheapestQuote();
        if ($cheapest) {
            $this->total_amount = $cheapest->total_price_after_commission;
            $this->save();
        }
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
