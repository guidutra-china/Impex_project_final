<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the stages for the payment term.
     */
    public function stages(): HasMany
    {
        return $this->hasMany(PaymentTermStage::class);
    }

    /**
     * Get the orders that use this payment term.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
