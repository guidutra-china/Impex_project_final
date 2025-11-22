<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'order_id',
        'supplier_quote_id',
        'supplier_id',
        'currency_id',
        'payment_term_id',
        'status',
        'incoterm',
        'delivery_address',
        'delivery_terms',
        'subtotal',
        'shipping_cost',
        'shipping_included_in_price',
        'insurance_cost',
        'insurance_included_in_price',
        'other_costs',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'locked_exchange_rate',
        'total_in_base_currency',
        'notes',
        'internal_notes',
        'sent_at',
        'confirmed_at',
        'expected_delivery_date',
        'actual_delivery_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'shipping_cost' => 'integer',
        'shipping_included_in_price' => 'boolean',
        'insurance_cost' => 'integer',
        'insurance_included_in_price' => 'boolean',
        'other_costs' => 'integer',
        'tax_amount' => 'integer',
        'discount_amount' => 'integer',
        'total_amount' => 'integer',
        'locked_exchange_rate' => 'decimal:6',
        'total_in_base_currency' => 'integer',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

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

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    // Accessors
    public function getSubtotalFormattedAttribute(): string
    {
        return number_format($this->subtotal / 100, 2);
    }

    public function getTotalAmountFormattedAttribute(): string
    {
        return number_format($this->total_amount / 100, 2);
    }

    // Methods
    public function calculateTotal(): void
    {
        $subtotal = $this->items()->sum('total_price');
        
        $shipping = $this->shipping_included_in_price ? 0 : ($this->shipping_cost ?? 0);
        $insurance = $this->insurance_included_in_price ? 0 : ($this->insurance_cost ?? 0);
        $other = $this->other_costs ?? 0;
        $tax = $this->tax_amount ?? 0;
        $discount = $this->discount_amount ?? 0;
        
        $total = $subtotal + $shipping + $insurance + $other + $tax - $discount;
        
        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $total,
        ]);
    }

    public function lockExchangeRate(): void
    {
        if (!$this->locked_exchange_rate && $this->currency_id) {
            $baseCurrency = Currency::where('is_base', true)->first();
            
            if ($baseCurrency && $this->currency_id !== $baseCurrency->id) {
                $this->locked_exchange_rate = $this->currency->exchange_rate;
                $this->total_in_base_currency = $this->total_amount * $this->locked_exchange_rate;
                $this->save();
            }
        }
    }

    public function generatePoNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastPo = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastPo ? (int) substr($lastPo->po_number, -4) + 1 : 1;
        
        return sprintf('PO-%s%s-%04d', $year, $month, $sequence);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($purchaseOrder) {
            if (!$purchaseOrder->po_number) {
                $purchaseOrder->po_number = $purchaseOrder->generatePoNumber();
            }
            
            if (!$purchaseOrder->created_by) {
                $purchaseOrder->created_by = auth()->id();
            }
        });
        
        static::updating(function ($purchaseOrder) {
            $purchaseOrder->updated_by = auth()->id();
        });
    }
}
