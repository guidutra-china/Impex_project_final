<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTermStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_term_id',
        'percentage',
        'days_from_invoice',
        'sort_order',
    ];

    /**
     * Get the payment term that owns the stage.
     */
    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }
}