<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'bank_account_id',
        'fee_type',
        'fixed_fee',
        'percentage_fee',
        'processing_time',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function customerReceipts(): HasMany
    {
        return $this->hasMany(CustomerReceipt::class);
    }

    // Methods
    public function calculateFee(int $amount): int
    {
        $fee = 0;

        if ($this->fee_type === 'fixed' || $this->fee_type === 'fixed_plus_percentage') {
            $fee += $this->fixed_fee;
        }

        if ($this->fee_type === 'percentage' || $this->fee_type === 'fixed_plus_percentage') {
            $fee += (int) ($amount * ($this->percentage_fee / 100));
        }

        return $fee;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}