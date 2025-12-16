<?php

namespace App\Models;

use App\Models\Scopes\ClientOwnershipScope;
use App\Services\Order\OrderNumberGenerator;
use App\Services\Order\OrderCalculator;
use App\Traits\HasRFQManagement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class Order extends Model
{
    use SoftDeletes, HasFactory, HasRFQManagement;

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
        'quotation_instructions',
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
                $generator = app(OrderNumberGenerator::class);
                $client = $order->customer ?? Client::withoutGlobalScopes()->find($order->customer_id);
                $order->order_number = $generator->generate($client);
            }
        });

        // Sync commission_type to quotes when Order is updated
        static::updated(function ($order) {
            // Check if commission_type or commission_percent changed
            if ($order->isDirty('commission_type') || $order->isDirty('commission_percent')) {
                $order->syncCommissionToQuotes();
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
     * Get the customer quotes for this order
     */
    public function customerQuotes(): HasMany
    {
        return $this->hasMany(CustomerQuote::class);
    }

    /**
     * Get the latest customer quote
     */
    public function latestCustomerQuote()
    {
        return $this->customerQuotes()->latest()->first();
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
     *
     * @deprecated Use OrderCalculator::updateTotalAmountWithCheapestQuote() instead
     */
    public function updateTotalAmount()
    {
        $calculator = app(OrderCalculator::class);
        $calculator->updateTotalAmountWithCheapestQuote($this);
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
     *
     * @deprecated Use OrderCalculator::getProjectExpenses() instead
     */
    public function getTotalProjectExpensesAttribute(): int
    {
        $calculator = app(OrderCalculator::class);
        return $calculator->getProjectExpenses($this);
    }

    /**
     * Get total project expenses in dollars
     *
     * @deprecated Use OrderCalculator::getProjectExpensesDollars() instead
     */
    public function getTotalProjectExpensesDollarsAttribute(): float
    {
        $calculator = app(OrderCalculator::class);
        return $calculator->getProjectExpensesDollars($this);
    }

    /**
     * Get real margin considering project expenses
     * Formula: Revenue - Purchase Costs - Project Expenses
     *
     * @deprecated Use OrderCalculator::calculateRealMargin() instead
     */
    public function getRealMarginAttribute(): float
    {
        $calculator = app(OrderCalculator::class);
        return $calculator->calculateRealMargin($this);
    }

    /**
     * Get real margin percentage
     *
     * @deprecated Use OrderCalculator::calculateRealMarginPercentage() instead
     */
    public function getRealMarginPercentAttribute(): float
    {
        $calculator = app(OrderCalculator::class);
        return $calculator->calculateRealMarginPercentage($this);
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
     * Update the average commission percentage based on order items
     *
     * @deprecated Use OrderCalculator::calculateCommissionAverage() instead
     */
    public function updateCommissionAverage(): void
    {
        $calculator = app(OrderCalculator::class);
        $average = $calculator->calculateCommissionAverage($this);

        if ($average !== null) {
            $this->commission_percent_average = $average;
            $this->save();
        }
    }

    /**
     * Sync commission_type and commission_percent to all related quotes
     * Called automatically when Order's commission settings change
     */
    public function syncCommissionToQuotes(): void
    {
        $commissionType = $this->commission_type ?? 'embedded';
        $commissionPercent = $this->commission_percent ?? 0;

        \Log::info('Syncing commission to quotes', [
            'order_id' => $this->id,
            'order_number' => $this->order_number,
            'commission_type' => $commissionType,
            'commission_percent' => $commissionPercent,
        ]);

        // Update all OrderItems first
        foreach ($this->items as $orderItem) {
            $orderItem->commission_type = $commissionType;
            $orderItem->commission_percent = $commissionPercent;
            $orderItem->save();
        }

        // Update all SupplierQuotes and their items
        foreach ($this->supplierQuotes as $quote) {
            \Log::info('Processing quote', [
                'quote_id' => $quote->id,
                'supplier' => $quote->supplier->name,
            ]);

            // Update all QuoteItems
            foreach ($quote->items as $item) {
                $item->commission_type = $commissionType;
                $item->commission_percent = $commissionPercent;
                
                // Recalculate prices based on new type
                $item->total_price_before_commission = $item->unit_price_before_commission * $item->quantity;
                
                if ($commissionType === 'embedded') {
                    $commissionMultiplier = 1 + ($commissionPercent / 100);
                    $item->total_price_after_commission = (int) ($item->total_price_before_commission * $commissionMultiplier);
                    $item->unit_price_after_commission = (int) ($item->total_price_after_commission / $item->quantity);
                } else {
                    // Separate - prices stay the same
                    $item->unit_price_after_commission = $item->unit_price_before_commission;
                    $item->total_price_after_commission = $item->total_price_before_commission;
                }
                
                $item->save();
            }

            // Recalculate quote totals
            $quote->calculateCommission();

            \Log::info('Quote updated', [
                'quote_id' => $quote->id,
                'items_updated' => $quote->items->count(),
            ]);
        }

        \Log::info('Commission sync completed', [
            'order_id' => $this->id,
            'quotes_updated' => $this->supplierQuotes->count(),
        ]);
    }
}
