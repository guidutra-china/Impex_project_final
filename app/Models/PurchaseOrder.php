<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'revision_number',
        'order_id',
        'supplier_quote_id',
        'supplier_id',
        'currency_id',
        'exchange_rate',
        'base_currency_id',
        'subtotal',
        'shipping_cost',
        'insurance_cost',
        'other_costs',
        'discount',
        'tax',
        'total',
        'total_base_currency',
        'incoterm',
        'incoterm_location',
        'shipping_included_in_price',
        'insurance_included_in_price',
        'payment_term_id',
        'payment_terms_text',
        'delivery_address',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'po_date',
        'sent_at',
        'confirmed_at',
        'notes',
        'terms_and_conditions',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipping_included_in_price' => 'boolean',
        'insurance_included_in_price' => 'boolean',
    ];

    // Relationships
//    public function order(): BelongsTo
//    {
//        return $this->belongsTo(Order::class)->comment('RFQ relacionado');
//    }

    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'related');
    }

    public function qualityInspections(): MorphMany
    {
        return $this->morphMany(QualityInspection::class, 'inspectable');
    }

    public function supplierIssues(): HasMany
    {
        return $this->hasMany(SupplierIssue::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getTotalPaidAttribute(): int
    {
        return $this->payments()->sum('allocated_amount');
    }

    public function getBalanceAttribute(): int
    {
        return $this->total - $this->getTotalPaidAttribute();
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->getBalanceAttribute() <= 0;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'sent', 'confirmed', 'partially_received']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_delivery_date', '<', now())
            ->whereNull('actual_delivery_date')
            ->whereIn('status', ['sent', 'confirmed']);
    }
}