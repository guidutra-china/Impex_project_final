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
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
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
