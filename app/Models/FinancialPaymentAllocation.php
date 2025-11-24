<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialPaymentAllocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'financial_payment_id',
        'financial_transaction_id',
        'allocated_amount',
        'gain_loss_on_exchange',
        'allocation_type',
        'notes',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'allocated_amount' => 'integer',
        'gain_loss_on_exchange' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate exchange gain/loss
        static::creating(function ($allocation) {
            if (!isset($allocation->gain_loss_on_exchange)) {
                $allocation->calculateExchangeGainLoss();
            }

            // Set created_at
            $allocation->created_at = now();

            // Update the transaction's paid_amount
            $allocation->updateTransactionPaidAmount();
        });

        static::created(function ($allocation) {
            // Update transaction status
            $allocation->transaction->save();
        });

        static::deleted(function ($allocation) {
            // Reverse the paid_amount update
            $transaction = $allocation->transaction;
            $transaction->paid_amount -= $allocation->allocated_amount;
            $transaction->save();
        });
    }

    /**
     * Get the payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(FinancialPayment::class, 'financial_payment_id');
    }

    /**
     * Get the transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    /**
     * Get the user who created this allocation
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate exchange gain/loss
     */
    protected function calculateExchangeGainLoss(): void
    {
        $payment = $this->payment;
        $transaction = $this->transaction;

        // If same currency, no exchange gain/loss
        if ($payment->currency_id === $transaction->currency_id) {
            $this->gain_loss_on_exchange = 0;
            return;
        }

        // Calculate value in base currency at transaction date
        $transactionValueBase = ($this->allocated_amount / 100) * $transaction->exchange_rate_to_base;

        // Calculate value in base currency at payment date
        $paymentValueBase = ($this->allocated_amount / 100) * $payment->exchange_rate_to_base;

        // Gain/Loss = Payment Value - Transaction Value
        // Positive = Gain, Negative = Loss
        $this->gain_loss_on_exchange = (int) round(($paymentValueBase - $transactionValueBase) * 100);
    }

    /**
     * Update transaction's paid_amount
     */
    protected function updateTransactionPaidAmount(): void
    {
        $transaction = $this->transaction;
        $transaction->paid_amount += $this->allocated_amount;
        // Don't save here, will be saved in the created event
    }

    /**
     * Get exchange gain/loss formatted
     */
    public function getExchangeGainLossFormattedAttribute(): string
    {
        $value = abs($this->gain_loss_on_exchange) / 100;
        $symbol = $this->gain_loss_on_exchange >= 0 ? '+' : '-';
        
        // Get base currency
        $baseCurrency = Currency::where('is_base', true)->first();
        $currencySymbol = $baseCurrency ? $baseCurrency->symbol : 'R$';
        
        return $symbol . $currencySymbol . ' ' . number_format($value, 2);
    }

    /**
     * Check if this allocation resulted in a gain
     */
    public function isGain(): bool
    {
        return $this->gain_loss_on_exchange > 0;
    }

    /**
     * Check if this allocation resulted in a loss
     */
    public function isLoss(): bool
    {
        return $this->gain_loss_on_exchange < 0;
    }
}
