<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class ExchangeRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'base_currency_id',
        'target_currency_id',
        'rate',
        'inverse_rate',
        'date',
        'source',
        'source_name',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'inverse_rate' => 'decimal:8',
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate inverse rate
        static::saving(function ($exchangeRate) {
            if ($exchangeRate->rate) {
                $exchangeRate->inverse_rate = 1 / $exchangeRate->rate;
            }
        });

        // Clear cache when rate changes
        static::saved(function ($exchangeRate) {
            $exchangeRate->clearRateCache();
        });
    }

    /**
     * Get the base currency
     */
    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    /**
     * Get the target currency
     */
    public function targetCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'target_currency_id');
    }

    /**
     * Get the user who created this rate
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this rate
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the latest rate for a currency pair
     *
     * @param int $baseCurrencyId
     * @param int $targetCurrencyId
     * @param string|null $date
     * @return ExchangeRate|null
     */
    public static function getLatestRate($baseCurrencyId, $targetCurrencyId, $date = null)
    {
        $date = $date ?? today()->toDateString();
        
        // Disable cache in testing environment to avoid stale data
        if (app()->environment('testing')) {
            return self::where('base_currency_id', $baseCurrencyId)
                ->where('target_currency_id', $targetCurrencyId)
                ->whereDate('date', '<=', $date)
                ->where('status', 'approved')
                ->orderBy('date', 'desc')
                ->first();
        }
        
        $cacheKey = "exchange_rate_{$baseCurrencyId}_{$targetCurrencyId}_{$date}";

        return Cache::remember($cacheKey, 3600, function () use ($baseCurrencyId, $targetCurrencyId, $date) {
            return self::where('base_currency_id', $baseCurrencyId)
                ->where('target_currency_id', $targetCurrencyId)
                ->whereDate('date', '<=', $date)
                ->where('status', 'approved')
                ->orderBy('date', 'desc')
                ->first();
        });
    }

    /**
     * Get conversion rate between two currencies using triangular conversion
     *
     * @param int $fromCurrencyId
     * @param int $toCurrencyId
     * @param string|null $date
     * @return float|null
     */
    public static function getConversionRate($fromCurrencyId, $toCurrencyId, $date = null)
    {
        // Same currency, no conversion needed
        if ($fromCurrencyId === $toCurrencyId) {
            return 1.0;
        }

        $date = $date ?? today()->toDateString();
        $baseCurrency = Currency::where('is_base', true)->first();

        if (!$baseCurrency) {
            throw new \Exception('No base currency defined in the system');
        }

        // If from currency is base currency
        if ($fromCurrencyId === $baseCurrency->id) {
            $rate = self::getLatestRate($baseCurrency->id, $toCurrencyId, $date);
            return $rate ? (float) $rate->rate : null;
        }

        // If to currency is base currency
        if ($toCurrencyId === $baseCurrency->id) {
            $rate = self::getLatestRate($baseCurrency->id, $fromCurrencyId, $date);
            return $rate ? (float) $rate->inverse_rate : null;
        }

        // Triangular conversion: FROM → BASE → TO
        $rateBaseToFrom = self::getLatestRate($baseCurrency->id, $fromCurrencyId, $date);
        $rateBaseToTo = self::getLatestRate($baseCurrency->id, $toCurrencyId, $date);

        if (!$rateBaseToFrom || !$rateBaseToTo) {
            return null; // Missing rate
        }

        // Conversion rate = (1 / rateBaseToFrom) * rateBaseToTo
        return (1 / (float) $rateBaseToFrom->rate) * (float) $rateBaseToTo->rate;
    }

    /**
     * Clear cached rates for this currency pair
     */
    public function clearRateCache()
    {
        $pattern = "exchange_rate_{$this->base_currency_id}_{$this->target_currency_id}_*";
        Cache::flush(); // Simple approach, could be more targeted
    }

    /**
     * Scope: Only approved rates
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: For a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', '<=', $date)
            ->orderBy('date', 'desc');
    }
}
