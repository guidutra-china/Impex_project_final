<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'bank_account_id',
        'payment_method_id',
        'currency_id',
        'amount',
        'fee',
        'net_amount',
        'exchange_rate',
        'amount_base_currency',
        'payment_date',
        'reference_number',
        'transaction_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getTotalAllocatedAttribute(): int
    {
        return $this->allocations()->sum('allocated_amount');
    }

    public function getUnallocatedAmountAttribute(): int
    {
        return $this->amount - $this->getTotalAllocatedAttribute();
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUnallocated($query)
    {
        return $query->whereHas('allocations', function($q) {
            $q->havingRaw('SUM(allocated_amount) < amount');
        });
    }
}