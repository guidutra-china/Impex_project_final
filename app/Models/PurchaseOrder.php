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

    /**
     * Money fields stored in cents
     */
    protected function subtotal(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    protected function total(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    protected function totalBaseCurrency(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    protected function shippingCost(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    protected function insuranceCost(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    protected function otherCosts(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    protected function discount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    protected function tax(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

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

    // Methods
    public function recalculateTotals(): void
    {
        // Calculate subtotal from items using RAW SQL to bypass Attribute getters
        // CRITICAL: $this->items()->sum('total_cost') applies getters and divides by 100!
        // We need the raw database value in cents
        $subtotalCents = \DB::table('purchase_order_items')
            ->where('purchase_order_id', $this->id)
            ->whereNull('deleted_at')
            ->sum('total_cost');
        
        // DEBUG: Log what we're getting
        \Log::info('=== PurchaseOrder.recalculateTotals DEBUG ===', [
            'po_id' => $this->id,
            'items_count' => $this->items()->count(),
            'sum_total_cost' => $subtotalCents,
            'items_raw' => $this->items()->get(['id', 'quantity', 'unit_cost', 'total_cost'])->toArray(),
        ]);
        
        // Get other costs in cents (raw values from database)
        $shippingCents = $this->getRawOriginal('shipping_cost') ?? 0;
        $insuranceCents = $this->getRawOriginal('insurance_cost') ?? 0;
        $otherCostsCents = $this->getRawOriginal('other_costs') ?? 0;
        $discountCents = $this->getRawOriginal('discount') ?? 0;
        $taxCents = $this->getRawOriginal('tax') ?? 0;
        
        // Calculate total in cents
        $totalCents = $subtotalCents 
            + $shippingCents
            + $insuranceCents
            + $otherCostsCents
            - $discountCents
            + $taxCents;
        
        // Calculate total in base currency (in cents)
        $totalBaseCurrencyCents = $totalCents * ($this->exchange_rate ?? 1);
        
        \Log::info('=== Before DB update ===', [
            'subtotal_to_save' => $subtotalCents,
            'total_to_save' => $totalCents,
            'total_base_to_save' => $totalBaseCurrencyCents,
        ]);
        
        // Use raw SQL update to completely bypass Eloquent casting
        // This prevents any Attribute setters from being triggered
        \DB::table('purchase_orders')
            ->where('id', $this->id)
            ->update([
                'subtotal' => $subtotalCents,
                'total' => $totalCents,
                'total_base_currency' => $totalBaseCurrencyCents,
                'updated_at' => now(),
            ]);
        
        // Check what was actually saved
        $saved = \DB::table('purchase_orders')
            ->where('id', $this->id)
            ->first(['subtotal', 'total', 'total_base_currency']);
        
        \Log::info('=== After DB update (raw query) ===', [
            'saved_subtotal' => $saved->subtotal,
            'saved_total' => $saved->total,
            'saved_total_base' => $saved->total_base_currency,
        ]);
        
        // Refresh the model to get the updated values
        $this->refresh();
        
        \Log::info('=== After refresh (via model) ===', [
            'model_subtotal' => $this->subtotal,
            'model_total' => $this->total,
            'raw_subtotal' => $this->getRawOriginal('subtotal'),
            'raw_total' => $this->getRawOriginal('total'),
        ]);
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