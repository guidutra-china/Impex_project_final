<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReceiptAllocation extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'customer_receipt_id',
        'sales_order_id',
        'allocated_amount',
        'allocation_type',
        'notes',
    ];

    // Relationships
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(CustomerReceipt::class, 'customer_receipt_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sales_order_id');
    }
}