<?php

namespace App\Models;

use App\Models\Scopes\ClientOwnershipScope;
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

    protected static function booted(): void
    {
        static::addGlobalScope(new ClientOwnershipScope());
    }

    protected $fillable = [
        'customer_id',
        'currency_id',
        'category_id',
        'order_number',
        'customer_nr_rfq',
        'status',
        'commission_percent',
        'commission_type',
        'commission_percent_average',
        'incoterm',
        'incoterm_location',
        'customer_notes',
        'notes',
        'total_amount',
        'selected_quote_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'commission_percent_average' => 'decimal:2',
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
     * Get the purchase orders for this RFQ
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'order_id');
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
     * Get the category for this order
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get supplier statuses for this RFQ
     */
    public function supplierStatuses(): HasMany
    {
        return $this->hasMany(RFQSupplierStatus::class);
    }

    /**
     * Get all financial transactions linked to this RFQ via transactable
     */
    public function financialTransactions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(FinancialTransaction::class, 'transactable');
    }

    /**
     * Get all project expenses (transactions with this RFQ as project)
     * This includes expenses not directly linked via transactable
     */
    public function projectExpenses(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'project_id')
            ->where('type', 'payable');
    }

    /**
     * Get total project expenses amount in cents (base currency USD)
     */
    public function getTotalProjectExpensesAttribute(): int
    {
        return $this->projectExpenses()->sum('amount_base_currency');
    }

    /**
     * Get total project expenses in dollars
     */
    public function getTotalProjectExpensesDollarsAttribute(): float
    {
        return $this->total_project_expenses / 100;
    }

    /**
     * Get real margin considering project expenses
     * Formula: Revenue - Purchase Costs - Project Expenses
     */
    public function getRealMarginAttribute(): float
    {
        // Revenue from selected quote or total amount
        $revenue = $this->selectedQuote ? $this->selectedQuote->total_price_after_commission : ($this->total_amount ?? 0);
        
        // Purchase costs from purchase orders
        $purchaseCosts = $this->purchaseOrders()->sum('total');
        
        // Project expenses
        $projectExpenses = $this->total_project_expenses;
        
        return ($revenue - $purchaseCosts - $projectExpenses) / 100;
    }

    /**
     * Get real margin percentage
     */
    public function getRealMarginPercentAttribute(): float
    {
        $revenue = $this->selectedQuote ? $this->selectedQuote->total_price_after_commission : ($this->total_amount ?? 0);
        
        if ($revenue == 0) {
            return 0;
        }
        
        return ($this->real_margin / ($revenue / 100)) * 100;
    }

    /**
     * Get suppliers that match this RFQ's category
     */
    public function matchingSuppliers(): Collection
    {
        if (!$this->category_id) {
            return collect();
        }

        return Supplier::whereHas('categories', function($q) {
            $q->where('categories.id', $this->category_id);
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

    /**
     * Update the average commission percentage based on order items
     */
    public function updateCommissionAverage(): void
    {
        $items = $this->items;
        
        if ($items->isEmpty()) {
            $this->commission_percent_average = null;
            $this->save();
            return;
        }
        
        // Calculate weighted average based on quantity
        $totalQuantity = $items->sum('quantity');
        $weightedSum = 0;
        
        foreach ($items as $item) {
            $weightedSum += ($item->commission_percent ?? 0) * $item->quantity;
        }
        
        $this->commission_percent_average = $totalQuantity > 0 ? $weightedSum / $totalQuantity : 0;
        $this->save();
    }
}
