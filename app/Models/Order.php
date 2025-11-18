<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'currency_id',
        'order_number',
        'customer_nr_rfq',
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
    /**
     * Generate order number (RFQ number)
     * Format: [CLIENT_CODE]-[YY]-[NNNN]
     * Example: AMA-25-0001
     */
    public function generateOrderNumber(): string
    {
        // Get client code
        $client = $this->customer ?? Client::find($this->customer_id);
        $clientCode = $client && $client->code ? $client->code : 'XXX';
        
        // Get 2-digit year
        $year = now()->format('y');
        
        // Find next sequential number for this client
        $sequentialNumber = 1;
        $orderNumber = "";
        
        // Loop until we find an order number that doesn't exist
        do {
            $orderNumber = "{$clientCode}-{$year}-" . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);
            
            $exists = Order::withTrashed()
                ->where('order_number', $orderNumber)
                ->exists();
            
            if ($exists) {
                $sequentialNumber++;
            }
        } while ($exists);
        
        return $orderNumber;
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

    // ========================================
    // RFQ (Request for Quotation) Methods
    // ========================================

    /**
     * Get the tags for this order (polymorphic relationship)
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Get the categories for this order
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_order')
            ->withTimestamps();
    }

    /**
     * Get supplier statuses for this RFQ
     */
    public function supplierStatuses(): HasMany
    {
        return $this->hasMany(RFQSupplierStatus::class);
    }

    /**
     * Get suppliers that match this RFQ's categories
     */
    public function matchingSuppliers(): Collection
    {
        $categoryIds = $this->categories()->pluck('categories.id')->toArray();

        if (empty($categoryIds)) {
            return collect();
        }

        return Supplier::whereHas('categories', function($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        })->with('categories')->get();
    }

    /**
     * Check if RFQ has been sent to a specific supplier
     */
    public function isSentToSupplier(int $supplierId): bool
    {
        return $this->supplierStatuses()
            ->where('supplier_id', $supplierId)
            ->where('sent', true)
            ->exists();
    }

    /**
     * Get send status for a specific supplier
     */
    public function getSupplierStatus(int $supplierId): ?RFQSupplierStatus
    {
        return $this->supplierStatuses()
            ->where('supplier_id', $supplierId)
            ->first();
    }

    /**
     * Mark RFQ as sent to a supplier
     */
    public function markSentToSupplier(int $supplierId, string $method = 'email'): RFQSupplierStatus
    {
        $status = RFQSupplierStatus::updateOrCreate(
            [
                'order_id' => $this->id,
                'supplier_id' => $supplierId,
            ],
            [
                'sent' => true,
                'sent_at' => now(),
                'sent_method' => $method,
                'sent_by' => auth()->id(),
            ]
        );

        return $status;
    }

    /**
     * Get count of suppliers this RFQ has been sent to
     */
    public function getSentSuppliersCount(): int
    {
        return $this->supplierStatuses()->where('sent', true)->count();
    }

    /**
     * Get count of matching suppliers for this RFQ
     */
    public function getMatchingSuppliersCount(): int
    {
        return $this->matchingSuppliers()->count();
    }
}
