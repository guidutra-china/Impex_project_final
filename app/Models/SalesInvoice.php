<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'revision_number',
        'client_id',
        'quote_id',
        'payment_term_id',
        'currency_id',
        'base_currency_id',
        'original_invoice_id',
        'superseded_by_invoice_id',
        'superseded_by_id',
        'supersedes_id',
        'revision_reason',
        'invoice_date',
        'shipment_date',
        'due_date',
        'payment_date',
        'exchange_rate',
        'subtotal',
        'commission',
        'tax',
        'total',
        'total_base_currency',
        'status',
        'payment_method',
        'payment_reference',
        'notes',
        'terms_and_conditions',
        'sent_at',
        'paid_at',
        'cancelled_at',
        'cancellation_reason',
        // Approval fields
        'approval_status',
        'approval_deadline',
        'approved_at',
        'approved_by',
        'rejection_reason',
        // Deposit fields
        'deposit_required',
        'deposit_amount',
        'deposit_received',
        'deposit_received_at',
        'deposit_payment_method',
        'deposit_payment_reference',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'shipment_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'approval_deadline' => 'datetime',
        'approved_at' => 'datetime',
        'deposit_received' => 'boolean',
        'deposit_required' => 'boolean',
        'deposit_received_at' => 'datetime',
    ];

    /**
     * Get subtotal in decimal format for display
     */
    protected function subtotal(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get commission in decimal format for display
     */
    protected function commission(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get tax in decimal format for display
     */
    protected function tax(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get total in decimal format for display
     */
    protected function total(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get total_base_currency in decimal format for display
     */
    protected function totalBaseCurrency(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get deposit_amount in decimal format for display
     */
    protected function depositAmount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
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
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function purchaseOrders(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseOrder::class, 'sales_invoice_purchase_orders')
            ->withTimestamps();
    }

    // Revision relationships
    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'original_invoice_id');
    }

    public function supersededByInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'superseded_by_invoice_id');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'superseded_by_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'supersedes_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'original_invoice_id');
    }

    /**
     * NEW: Shipments that include items from this invoice
     */
    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'shipment_invoices')
            ->withPivot([
                'total_items',
                'total_quantity',
                'total_weight',
                'total_volume',
                'total_customs_value',
            ])
            ->withTimestamps();
    }

    /**
     * Check if this invoice has been superseded
     */
    public function isSuperseded(): bool
    {
        return $this->superseded_by_id !== null;
    }

    /**
     * Get the latest version of this invoice
     */
    public function getLatestVersion(): SalesInvoice
    {
        $current = $this;
        while ($current->supersededBy) {
            $current = $current->supersededBy;
        }
        return $current;
    }

    // Helper methods
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        $commission = $this->items()->sum('commission');
        $total = $subtotal + $this->tax;
        $totalBaseCurrency = $total * $this->exchange_rate;

        $this->update([
            'subtotal' => $subtotal,
            'commission' => $commission,
            'total' => $total,
            'total_base_currency' => $totalBaseCurrency,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' 
            && $this->status !== 'cancelled' 
            && $this->status !== 'superseded'
            && $this->due_date < now();
    }

    public function markAsOverdueIfNeeded(): void
    {
        if ($this->isOverdue() && $this->status !== 'overdue') {
            $this->update(['status' => 'overdue']);
        }
    }

    /**
     * Check if invoice is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'accepted';
    }

    /**
     * Check if approval is pending
     */
    public function isApprovalPending(): bool
    {
        return $this->approval_status === 'pending_approval';
    }

    /**
     * Check if approval is overdue
     */
    public function isApprovalOverdue(): bool
    {
        return $this->isApprovalPending() 
            && $this->approval_deadline 
            && $this->approval_deadline < now();
    }

    /**
     * Check if deposit is required
     */
    public function requiresDeposit(): bool
    {
        return $this->deposit_required;
    }

    /**
     * Check if deposit has been received
     */
    public function hasDepositReceived(): bool
    {
        return $this->deposit_received;
    }

    /**
     * Check if can proceed to create PO
     * (approved + deposit received if required)
     */
    public function canProceedToPO(): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        if ($this->requiresDeposit() && !$this->hasDepositReceived()) {
            return false;
        }

        return true;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = static::whereYear('created_at', $year)
            ->where('revision_number', 1)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return sprintf('SI-%d-%04d', $year, $nextNumber);
    }
}
