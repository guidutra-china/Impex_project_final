<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierQuote extends Model
{
    use SoftDeletes;

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

        // Lock exchange rate when quote is created
        static::created(function ($quote) {
            $quote->lockExchangeRate();
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
     *
     * @return string
     */
    public function generateQuoteNumber(): string
    {
        $year = date('Y');
        $count = SupplierQuote::whereYear('created_at', $year)->count() + 1;
        return "QUO-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Lock exchange rate for this quote
     */
    public function lockExchangeRate()
    {
        $orderCurrencyId = $this->order->currency_id;
        $quoteCurrencyId = $this->currency_id;
        $quoteDate = $this->created_at->toDateString();

        if ($orderCurrencyId === $quoteCurrencyId) {
            $lockedRate = 1.0;
        } else {
            $lockedRate = ExchangeRate::getConversionRate($quoteCurrencyId, $orderCurrencyId, $quoteDate);
            
            if (!$lockedRate) {
                throw new \Exception("Missing exchange rate for conversion from currency {$quoteCurrencyId} to {$orderCurrencyId} on {$quoteDate}");
            }
        }

        $this->locked_exchange_rate = $lockedRate;
        $this->locked_exchange_rate_date = $quoteDate;
        $this->save();

        // Convert prices on all quote items
        foreach ($this->items as $item) {
            $item->convertPrice($lockedRate);
        }
    }

    /**
     * Calculate and apply commission to this quote
     */
    public function calculateCommission()
    {
        $order = $this->order;
        $commissionPercent = $order->commission_percent / 100;
        $commissionType = $this->commission_type ?? $order->commission_type;

        $totalBefore = 0;
        $totalAfter = 0;

        foreach ($this->items as $item) {
            $totalBefore += $item->total_price_before_commission;

            if ($commissionType === 'embedded') {
                $unitAfter = (int)($item->unit_price_before_commission * (1 + $commissionPercent));
                $item->update([
                    'unit_price_after_commission' => $unitAfter,
                    'total_price_after_commission' => $unitAfter * $item->quantity,
                ]);
                $totalAfter += $unitAfter * $item->quantity;
            } else {
                // Separate commission - prices stay same
                $item->update([
                    'unit_price_after_commission' => $item->unit_price_before_commission,
                    'total_price_after_commission' => $item->total_price_before_commission,
                ]);
                $totalAfter += $item->total_price_before_commission;
            }
        }

        if ($commissionType === 'separate') {
            $commissionAmount = (int)($totalBefore * $commissionPercent);
            $totalAfter += $commissionAmount;
        } else {
            $commissionAmount = $totalAfter - $totalBefore;
        }

        $this->update([
            'total_price_before_commission' => $totalBefore,
            'total_price_after_commission' => $totalAfter,
            'commission_amount' => $commissionAmount,
            'commission_type' => $commissionType,
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
}
