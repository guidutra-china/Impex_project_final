<?php

namespace App\Models;

use App\Models\Scopes\ClientOwnershipScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialInvoice extends Model
{
    use HasFactory;
    // SoftDeletes removed - not needed for CommercialInvoice

    protected $table = 'commercial_invoices';

    protected static function booted(): void
    {
        static::addGlobalScope(new ClientOwnershipScope());
    }

    protected $fillable = [
        'invoice_number',
        'revision_number',
        'client_id',
        'shipment_id',
        'proforma_invoice_id',
        'payment_term_id',
        'currency_id',
        'base_currency_id',
        
        // Dates
        'invoice_date',
        'shipment_date',
        'due_date',
        'payment_date',
        
        // Financial (stored in cents)
        'exchange_rate',
        'customs_discount_percentage',
        'subtotal',
        'commission',
        'tax',
        'total',
        'total_base_currency',
        
        // Incoterms
        'incoterm',
        'incoterm_location',
        
        // Exporter details
        'exporter_name',
        'exporter_address',
        'exporter_tax_id',
        'exporter_country',
        
        // Importer details
        'importer_name',
        'importer_address',
        'importer_tax_id',
        'importer_country',
        
        // Shipping details
        'port_of_loading',
        'port_of_discharge',
        'final_destination',
        'bl_number',
        'container_numbers',
        
        // Payment details
        'payment_method',
        'payment_reference',
        'bank_name',
        'bank_account',
        'bank_swift',
        'bank_address',
        
        // Status and additional info
        'status',
        'notes',
        'terms_and_conditions',
        'display_options',
        
        // Timestamps
        'sent_at',
        'paid_at',
        'cancelled_at',
        'cancellation_reason',
        
        // Deposit fields (kept for compatibility, can be removed later)
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
        'customs_discount_percentage' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'deposit_received' => 'boolean',
        'deposit_required' => 'boolean',
        'deposit_received_at' => 'datetime',
        'display_options' => 'array',
    ];

    /**
     * Get subtotal in decimal format for display
     */
    protected function subtotal(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    /**
     * Get commission in decimal format for display
     */
    protected function commission(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    /**
     * Get tax in decimal format for display
     */
    protected function tax(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
        );
    }

    /**
     * Get total in decimal format for display
     */
    protected function total(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) round($value * 100) : 0,
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

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
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
        return $this->hasMany(CommercialInvoiceItem::class);
    }

    public function purchaseOrders(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseOrder::class, 'commercial_invoice_purchase_orders')
            ->withTimestamps();
    }

    // Customs calculation methods
    
    /**
     * Get subtotal with customs discount applied (in cents)
     */
    public function getCustomsSubtotalCents(): int
    {
        $originalCents = $this->getRawOriginal('subtotal') ?? 0;
        $discountMultiplier = 1 - ($this->customs_discount_percentage / 100);
        return (int) round($originalCents * $discountMultiplier);
    }

    /**
     * Get subtotal with customs discount applied (in decimal)
     */
    public function getCustomsSubtotal(): float
    {
        return $this->getCustomsSubtotalCents() / 100;
    }

    /**
     * Get total with customs discount applied (in cents)
     */
    public function getCustomsTotalCents(): int
    {
        $customsSubtotalCents = $this->getCustomsSubtotalCents();
        $taxCents = $this->getRawOriginal('tax') ?? 0;
        return $customsSubtotalCents + $taxCents;
    }

    /**
     * Get total with customs discount applied (in decimal)
     */
    public function getCustomsTotal(): float
    {
        return $this->getCustomsTotalCents() / 100;
    }

    /**
     * Check if customs discount is applied
     */
    public function hasCustomsDiscount(): bool
    {
        return $this->customs_discount_percentage > 0;
    }

    // Helper methods
    public function recalculateTotals(): void
    {
        // Commercial Invoice doesn't store financial totals
        // Totals are calculated from items on-the-fly or from Proforma Invoice
        // This method is kept for compatibility but does nothing
    }
    
    /**
     * Get subtotal from items (in cents)
     */
    public function getSubtotalCents(): int
    {
        return $this->items()->sum('total');
    }
    
    /**
     * Get subtotal from items (in decimal)
     */
    public function getSubtotal(): float
    {
        return $this->getSubtotalCents() / 100;
    }
    
    /**
     * Get total from items (in cents)
     */
    public function getTotalCents(): int
    {
        return $this->getSubtotalCents();
    }
    
    /**
     * Get total from items (in decimal)
     */
    public function getTotal(): float
    {
        return $this->getTotalCents() / 100;
    }
    
    /**
     * Get currency from Proforma Invoice
     */
    public function getCurrency()
    {
        return $this->proformaInvoice?->currency ?? $this->currency;
    }
    
    /**
     * Get payment terms from Proforma Invoice
     */
    public function getPaymentTerms()
    {
        return $this->proformaInvoice?->paymentTerm ?? $this->paymentTerm;
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' 
            && $this->status !== 'cancelled' 
            && $this->due_date 
            && $this->due_date < now();
    }

    public function markAsOverdueIfNeeded(): void
    {
        if ($this->isOverdue() && $this->status !== 'overdue') {
            $this->update(['status' => 'overdue']);
        }
    }

    /**
     * Generate Commercial Invoice from Shipment
     * Auto-fills all data including Exporter, Importer, Items, Shipping Details
     */
    public static function generateFromShipment(Shipment $shipment, array $additionalData = []): self
    {
        $invoice = new self();
        
        // Basic info
        $invoice->shipment_id = $shipment->id;
        $invoice->client_id = $shipment->customer_id;
        $invoice->invoice_date = now();
        $invoice->shipment_date = $shipment->actual_departure_date ?? $shipment->estimated_departure_date;
        $invoice->status = 'draft';
        
        // Get proforma invoice from shipment if exists
        $proformaInvoice = $shipment->proformaInvoices()->first();
        if ($proformaInvoice) {
            $invoice->proforma_invoice_id = $proformaInvoice->id;
            $invoice->currency_id = $proformaInvoice->currency_id;
            $invoice->payment_term_id = $proformaInvoice->payment_term_id;
            $invoice->incoterm = $proformaInvoice->incoterm;
            $invoice->incoterm_location = $proformaInvoice->incoterm_location;
        }
        
        // Shipping details from shipment
        $invoice->port_of_loading = $shipment->port_of_loading;
        $invoice->port_of_discharge = $shipment->port_of_discharge;
        $invoice->final_destination = $shipment->final_destination;
        $invoice->bl_number = $shipment->bl_number ?? '';
        
        // Container numbers from shipment
        $containerNumbers = $shipment->containers()->pluck('container_number')->join(', ');
        $invoice->container_numbers = $containerNumbers;
        
        // Exporter details from Company Settings
        $companySettings = CompanySetting::current();
        if ($companySettings) {
            $invoice->exporter_name = $companySettings->company_name;
            $invoice->exporter_address = $companySettings->full_address;
            $invoice->exporter_tax_id = $companySettings->tax_id;
            $invoice->exporter_country = $companySettings->country;
            
            // Bank details
            $invoice->bank_name = $companySettings->bank_name;
            $invoice->bank_account = $companySettings->bank_account_number;
            $invoice->bank_swift = $companySettings->bank_swift_code;
        }
        
        // Importer details from Customer
        $customer = $shipment->customer;
        if ($customer) {
            $invoice->importer_name = $customer->name;
            // Build full address from customer fields
            $addressParts = array_filter([
                $customer->address,
                $customer->city,
                $customer->state . ' ' . $customer->zip,
                $customer->country,
            ]);
            $invoice->importer_address = implode(', ', $addressParts);
            $invoice->importer_tax_id = $customer->tax_number ?? '';
            $invoice->importer_country = $customer->country ?? '';
        }
        
        // Merge additional data
        $invoice->fill($additionalData);
        
        $invoice->save();
        
        // Copy items from shipment
        foreach ($shipment->items as $shipmentItem) {
            $invoiceItem = new CommercialInvoiceItem();
            $invoiceItem->commercial_invoice_id = $invoice->id;
            $invoiceItem->product_id = $shipmentItem->product_id;
            $invoiceItem->product_name = $shipmentItem->product->name ?? 'Unknown Product';
            $invoiceItem->description = $shipmentItem->product->description ?? $shipmentItem->product->name ?? '';
            $invoiceItem->quantity = $shipmentItem->quantity_to_ship;
            $invoiceItem->unit_price = $shipmentItem->unit_price ?? 0;
            $invoiceItem->total = ($shipmentItem->unit_price ?? 0) * $shipmentItem->quantity_to_ship;
            $invoiceItem->hs_code = $shipmentItem->product->hs_code ?? '';
            $invoiceItem->country_of_origin = $shipmentItem->product->country_of_origin ?? '';
            $invoiceItem->save();
        }
        
        // Recalculate totals
        $invoice->recalculateTotals();
        
        return $invoice;
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
        
        // Validate client_id matches shipment->customer_id
        static::saving(function ($invoice) {
            if ($invoice->shipment_id && $invoice->client_id) {
                $shipment = Shipment::find($invoice->shipment_id);
                if ($shipment && $invoice->client_id !== $shipment->customer_id) {
                    throw new \Exception(
                        "Client ID ({$invoice->client_id}) must match Shipment's customer ID ({$shipment->customer_id})"
                    );
                }
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return sprintf('CI-%d-%04d', $year, $nextNumber);
    }
}
