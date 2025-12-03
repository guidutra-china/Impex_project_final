<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'description',
        'type',
        'status',
        'amount',
        'paid_amount',
        'currency_id',
        'exchange_rate_to_base',
        'amount_base_currency',
        'transaction_date',
        'due_date',
        'paid_date',
        'financial_category_id',
        'transactable_id',
        'transactable_type',
        'project_id',
        'supplier_id',
        'client_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'exchange_rate_to_base' => 'decimal:6',
        'amount_base_currency' => 'integer',
        'transaction_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate transaction number
        static::creating(function ($transaction) {
            if (!$transaction->transaction_number) {
                $transaction->transaction_number = $transaction->generateTransactionNumber();
            }
        });

        // Update status based on paid_amount
        static::saving(function ($transaction) {
            $transaction->updateStatus();
        });
    }

    /**
     * Get the currency
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the financial category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'financial_category_id');
    }

    /**
     * Get the originating model (PurchaseOrder, SalesInvoice, etc.)
     */
    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the supplier (for payables)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the client (for receivables)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created this transaction
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the project/RFQ this transaction belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'project_id');
    }

    /**
     * Get all payment allocations
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(FinancialPaymentAllocation::class);
    }

    /**
     * Scope to get payables
     */
    public function scopePayables($query)
    {
        return $query->where('type', 'payable');
    }

    /**
     * Scope to get receivables
     */
    public function scopeReceivables($query)
    {
        return $query->where('type', 'receivable');
    }

    /**
     * Scope to get pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get overdue transactions
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                    ->where('due_date', '<', now());
            });
    }

    /**
     * Scope to get paid transactions
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Generate transaction number
     */
    protected function generateTransactionNumber(): string
    {
        $prefix = $this->type === 'payable' ? 'FT-PAY' : 'FT-REC';
        $year = now()->year;
        
        $lastTransaction = static::where('transaction_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('transaction_number', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $newNumber);
    }

    /**
     * Update status based on paid_amount
     */
    protected function updateStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        if ($this->paid_amount >= $this->amount) {
            $this->status = 'paid';
            $this->paid_date = $this->paid_date ?? now();
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partially_paid';
        } elseif ($this->due_date < now()) {
            $this->status = 'overdue';
        } else {
            $this->status = 'pending';
        }
    }

    /**
     * Get remaining amount to be paid
     */
    public function getRemainingAmountAttribute(): int
    {
        return $this->amount - $this->paid_amount;
    }

    /**
     * Get remaining amount in currency format
     */
    public function getRemainingAmountFormattedAttribute(): string
    {
        return $this->currency->symbol . ' ' . number_format($this->remaining_amount / 100, 2);
    }

    /**
     * Check if transaction is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'paid' 
            && $this->status !== 'cancelled' 
            && $this->due_date < now();
    }

    /**
     * Get days until/past due
     */
    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }
}
