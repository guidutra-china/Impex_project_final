# 🚀 GUIA DE IMPLEMENTAÇÃO - PARTE 3

**Continuação do IMPLEMENTATION_GUIDE_PART2.md**

---

## 📦 PURCHASE ORDER & FINANCIAL MODELS

### 19. PurchaseOrder.php

Crie em `app/Models/PurchaseOrder.php`:

```php
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
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->comment('RFQ relacionado');
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
```

### 20. PurchaseOrderItem.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'allocated_quantity',
        'unit_cost',
        'total_cost',
        'selling_price',
        'selling_total',
        'product_name',
        'product_sku',
        'expected_delivery_date',
        'actual_delivery_date',
        'notes',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getAvailableQuantityAttribute(): int
    {
        return $this->received_quantity - $this->allocated_quantity;
    }

    public function getMarginAttribute(): ?float
    {
        if (!$this->selling_price || $this->unit_cost == 0) return null;
        return (($this->selling_price - $this->unit_cost) / $this->unit_cost) * 100;
    }
}
```

### 21. BankAccount.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_name',
        'account_number',
        'account_type',
        'bank_name',
        'bank_branch',
        'swift_code',
        'iban',
        'routing_number',
        'currency_id',
        'current_balance',
        'available_balance',
        'daily_limit',
        'monthly_limit',
        'is_active',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Relationships
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function customerReceipts(): HasMany
    {
        return $this->hasMany(CustomerReceipt::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
```

### 22. PaymentMethod.php

```php
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
```

### 23. SupplierPayment.php

```php
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
```

### 24. SupplierPaymentAllocation.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentAllocation extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'supplier_payment_id',
        'purchase_order_id',
        'allocated_amount',
        'allocation_type',
        'notes',
    ];

    // Relationships
    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
```

### 25. CustomerReceipt.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'client_id',
        'bank_account_id',
        'payment_method_id',
        'currency_id',
        'amount',
        'fee',
        'net_amount',
        'exchange_rate',
        'amount_base_currency',
        'receipt_date',
        'reference_number',
        'transaction_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
        return $this->hasMany(CustomerReceiptAllocation::class);
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
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }
}
```

### 26. CustomerReceiptAllocation.php

```php
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
```

---

## 📋 PRÓXIMO: ENUMS E SERVICES

Continue para **IMPLEMENTATION_GUIDE_PART4.md** para Enums e Services...
