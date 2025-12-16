<?php

namespace App\Models;

use App\Models\Scopes\ClientOwnershipScope;

use App\Exceptions\MissingExchangeRateException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierQuote extends Model
{
    use SoftDeletes;

    
    protected static function booted(): void
    {
        static::addGlobalScope(new ClientOwnershipScope());
    }

    protected $fillable = [
        'order_id',
        'supplier_id',
        'currency_id',
        'quote_number',
        'revision_number',
        'is_latest',
        'status',
        'total_price_before_commission',
        'total_price_after_commission',
        'commission_amount',
        'locked_exchange_rate',
        'locked_exchange_rate_date',
        'commission_type',
        'valid_until',
        'validity_days',
        'supplier_notes',
        'notes',
        'created_by',
        'updated_by',
        'moq',
        'lead_time_days',
        'incoterm',
        'payment_terms',
    ];

    protected $casts = [
        'total_price_before_commission' => 'integer',
        'total_price_after_commission' => 'integer',
        'commission_amount' => 'integer',
        'locked_exchange_rate' => 'decimal:8',
        'locked_exchange_rate_date' => 'date',
        'revision_number' => 'integer',
        'validity_days' => 'integer',
        'is_latest' => 'boolean',
        'valid_until' => 'date',
        'moq' => 'integer',
        'lead_time_days' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate quote number
        static::creating(function ($quote) {
            if (!$quote->quote_number) {
                $quote->quote_number = $quote->generateQuoteNumber();
            }
            
            // Set validity date
            if (!$quote->valid_until && $quote->validity_days) {
                $quote->valid_until = now()->addDays($quote->validity_days);
            }
        });

        // Lock exchange rate and calculate commission when quote is created
        static::created(function ($quote) {
            try {
                \Log::info('SupplierQuote created hook started', ['quote_id' => $quote->id]);
                
                $quote->lockExchangeRate();
                \Log::info('Exchange rate locked', ['quote_id' => $quote->id, 'rate' => $quote->locked_exchange_rate]);
                
                $quote->calculateCommission();
                \Log::info('Commission calculated', ['quote_id' => $quote->id]);
            } catch (\Exception $e) {
                \Log::error('Error in SupplierQuote created hook', [
                    'quote_id' => $quote->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get the order this quote belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the supplier for this quote
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the currency for this quote
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the quote items
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Get the sent log for this quote
     */
    public function sentLog(): HasOne
    {
        return $this->hasOne(QuoteSentLog::class);
    }

    /**
     * Get the customer quote items that reference this supplier quote
     */
    public function customerQuoteItems(): HasMany
    {
        return $this->hasMany(CustomerQuoteItem::class);
    }

    /**
     * Get the user who created this quote
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this quote
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Generate quote number
     * Format: [3 letters of Supplier][2-digit year][sequential number]_Rev[N]
     * Example: TRA250004_Rev1
     *
     * @return string
     */
    public function generateQuoteNumber(): string
    {
        // Get supplier name and extract first 3 letters
        $supplier = $this->supplier ?? Supplier::find($this->supplier_id);
        $supplierName = $supplier ? $supplier->name : 'SUP';
        $supplierPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $supplierName), 0, 3));
        
        // If supplier name has less than 3 letters, pad with 'X'
        $supplierPrefix = str_pad($supplierPrefix, 3, 'X', STR_PAD_RIGHT);
        
        // Get 2-digit year
        $year = now()->format('y');
        
        // Get RFQ number from order and extract only the sequential number
        $order = $this->order ?? Order::find($this->order_id);
        $rfqNumber = $order->order_number ?? '0000';
        
        // Extract sequential number from RFQ format (e.g., "RFQ-2025-0004" -> "0004")
        if (preg_match('/(\d{4})$/', $rfqNumber, $matches)) {
            $sequentialNumber = $matches[1];
        } else {
            $sequentialNumber = '0000';
        }
        
        // Find the next available revision number
        $revisionNumber = 1;
        $quoteNumber = "";
        
        // Loop until we find a quote number that doesn't exist
        do {
            $quoteNumber = "{$supplierPrefix}{$year}{$sequentialNumber}_Rev{$revisionNumber}";
            
            $exists = SupplierQuote::withTrashed()
                ->where('quote_number', $quoteNumber)
                ->exists();
            
            if ($exists) {
                $revisionNumber++;
            }
        } while ($exists);
        
        return $quoteNumber;
    }

    /**
     * Lock exchange rate for this quote
     */
    public function lockExchangeRate()
    {
        $orderCurrencyId = $this->order->currency_id;
        $quoteCurrencyId = $this->currency_id;
        $quoteDate = $this->created_at ? $this->created_at->toDateString() : now()->toDateString();

        if ($orderCurrencyId === $quoteCurrencyId) {
            $lockedRate = 1.0;
        } else {
            $lockedRate = ExchangeRate::getConversionRate($quoteCurrencyId, $orderCurrencyId, $quoteDate);
            
            if (!$lockedRate) {
                // Get currency names for better error message
                $quoteCurrency = Currency::find($quoteCurrencyId);
                $orderCurrency = Currency::find($orderCurrencyId);
                
                $quoteCurrencyName = $quoteCurrency ? $quoteCurrency->code : "Currency {$quoteCurrencyId}";
                $orderCurrencyName = $orderCurrency ? $orderCurrency->code : "Currency {$orderCurrencyId}";
                
                throw new MissingExchangeRateException(
                    $quoteCurrencyId,
                    $orderCurrencyId,
                    $quoteDate,
                    $quoteCurrencyName,
                    $orderCurrencyName
                );
            }
        }

        $this->locked_exchange_rate = $lockedRate;
        $this->locked_exchange_rate_date = $quoteDate;
        $this->save();

        // Convert prices on all quote items (only if items exist)
        if ($this->items()->exists()) {
            foreach ($this->items as $item) {
                $item->convertPrice($lockedRate);
            }
        }
    }

    /**
     * Calculate and apply commission to this quote
     */
    public function calculateCommission()
    {
        $order = $this->order;
        
        // Safety checks
        if (!$order) {
            return;
        }

        $totalBefore = 0;
        $totalAfter = 0;
        $totalCommission = 0;

        // Only process if there are items
        if (!$this->items()->exists()) {
            return;
        }

        // Each item now has its own commission percent and type
        // These are already calculated in QuoteItem::saving()
        // Just sum up the totals
        foreach ($this->items as $item) {
            $totalBefore += $item->total_price_before_commission;
            $totalAfter += $item->total_price_after_commission;
        }

        // Calculate total commission amount
        $totalCommission = $totalAfter - $totalBefore;
        
        // Determine predominant commission type (most common among items)
        $embeddedCount = $this->items->where('commission_type', 'embedded')->count();
        $separateCount = $this->items->where('commission_type', 'separate')->count();
        $predominantType = $embeddedCount >= $separateCount ? 'embedded' : 'separate';

        $this->update([
            'total_price_before_commission' => $totalBefore,
            'total_price_after_commission' => $totalAfter,
            'commission_amount' => $totalCommission,
            'commission_type' => $predominantType,
        ]);
    }

    /**
     * Check if quote is expired
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Scope: Only latest quotes
     */
    public function scopeLatest($query)
    {
        return $query->where('is_latest', true);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get subtotal (price before commission) in dollars
     */
    public function getSubtotalAttribute(): float
    {
        return $this->total_price_before_commission / 100;
    }

    /**
     * Get total (price after commission) in dollars
     */
    public function getTotalAttribute(): float
    {
        return $this->total_price_after_commission / 100;
    }

    /**
     * Get commission amount in dollars
     */
    public function getCommissionAmountDollarsAttribute(): float
    {
        return $this->commission_amount / 100;
    }
}
