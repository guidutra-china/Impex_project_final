<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'description',
        'type',
        'bank_account_id',
        'payment_method_id',
        'payment_date',
        'amount',
        'fee',
        'net_amount',
        'currency_id',
        'exchange_rate_to_base',
        'amount_base_currency',
        'reference_number',
        'transaction_id',
        'status',
        'supplier_id',
        'client_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'net_amount' => 'integer',
        'exchange_rate_to_base' => 'decimal:6',
        'amount_base_currency' => 'integer',
        'payment_date' => 'date',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate payment number
        static::creating(function ($payment) {
            if (!$payment->payment_number) {
                $payment->payment_number = $payment->generatePaymentNumber();
            }

            // Auto-calculate net_amount
            if (!$payment->net_amount) {
                $payment->net_amount = $payment->amount - $payment->fee;
            }
        });
    }

    /**
     * Get the bank account
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the payment method
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the currency
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the supplier (for debits/payments)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the client (for credits/receipts)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created this payment
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all allocations
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(FinancialPaymentAllocation::class);
    }

    /**
     * Scope to get debits (payments/outflows)
     */
    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope to get credits (receipts/inflows)
     */
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope to get completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Generate payment number
     */
    protected function generatePaymentNumber(): string
    {
        $prefix = $this->type === 'debit' ? 'FP-OUT' : 'FP-IN';
        $year = now()->year;
        
        $lastPayment = static::where('payment_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $newNumber);
    }

    /**
     * Get total allocated amount
     */
    public function getTotalAllocatedAttribute(): int
    {
        return $this->allocations()->sum('allocated_amount');
    }

    /**
     * Get unallocated amount
     */
    public function getUnallocatedAmountAttribute(): int
    {
        return $this->amount - $this->total_allocated;
    }

    /**
     * Check if payment is fully allocated
     */
    public function isFullyAllocated(): bool
    {
        return $this->unallocated_amount <= 0;
    }

    /**
     * Get total exchange gain/loss from all allocations
     */
    public function getTotalExchangeGainLossAttribute(): int
    {
        return $this->allocations()->sum('gain_loss_on_exchange');
    }
}
