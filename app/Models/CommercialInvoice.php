<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'invoice_number',
        'invoice_date',
        // Exporter
        'exporter_name',
        'exporter_address',
        'exporter_tax_id',
        'exporter_country',
        'exporter_phone',
        'exporter_email',
        // Importer
        'importer_name',
        'importer_address',
        'importer_tax_id',
        'importer_country',
        'importer_phone',
        'importer_email',
        // Notify Party
        'notify_party_name',
        'notify_party_address',
        'notify_party_phone',
        // Shipping
        'port_of_loading',
        'port_of_discharge',
        'country_of_origin',
        'country_of_destination',
        'vessel_flight_number',
        // Terms
        'incoterm',
        'payment_terms',
        'terms_of_sale',
        // Totals
        'currency_id',
        'subtotal',
        'freight_cost',
        'insurance_cost',
        'other_costs',
        'total_value',
        // Additional
        'reason_for_export',
        'declaration',
        'additional_notes',
        // Status
        'status',
        'issued_at',
        'issued_by',
        // Customs & Display
        'customs_discount_percentage',
        'display_options',
        // Bank
        'bank_name',
        'bank_account_number',
        'bank_swift_code',
        'bank_address',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'integer',
        'freight_cost' => 'integer',
        'insurance_cost' => 'integer',
        'other_costs' => 'integer',
        'total_value' => 'integer',
        'issued_at' => 'datetime',
        'customs_discount_percentage' => 'decimal:2',
        'display_options' => 'array',
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
     * Get freight_cost in decimal format for display
     */
    protected function freightCost(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get insurance_cost in decimal format for display
     */
    protected function insuranceCost(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get other_costs in decimal format for display
     */
    protected function otherCosts(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get total_value in decimal format for display
     */
    protected function totalValue(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // Methods

    /**
     * Calculate totals from shipment
     */
    public function calculateTotals(): void
    {
        $shipment = $this->shipment;
        
        // Sum customs values from all shipment items
        $this->subtotal = $shipment->items()->sum('customs_value');
        
        // Get costs from shipment
        $this->freight_cost = $shipment->shipping_cost ?? 0;
        $this->insurance_cost = $shipment->insurance_cost ?? 0;
        $this->other_costs = $shipment->other_costs ?? 0;
        
        // Calculate total
        $this->total_value = $this->subtotal + $this->freight_cost + $this->insurance_cost + $this->other_costs;
        
        $this->save();
    }

    /**
     * Issue the commercial invoice (lock it)
     */
    public function issue(): void
    {
        if ($this->canBeIssued()) {
            $this->status = 'issued';
            $this->issued_at = now();
            $this->issued_by = auth()->id();
            $this->save();
            
            // Update shipment
            $this->shipment->commercial_invoice_generated = true;
            $this->shipment->save();
        }
    }

    /**
     * Check if can be issued
     */
    public function canBeIssued(): bool
    {
        return $this->status === 'draft' && $this->total_value > 0;
    }

    /**
     * Get subtotal with customs discount applied
     */
    public function getCustomsSubtotal(): float
    {
        $discount = $this->customs_discount_percentage / 100;
        return $this->subtotal * (1 - $discount);
    }

    /**
     * Get total value with customs discount applied
     */
    public function getCustomsTotalValue(): float
    {
        $customsSubtotal = $this->getCustomsSubtotal();
        return $customsSubtotal + $this->freight_cost + $this->insurance_cost + $this->other_costs;
    }

    /**
     * Get items with customs discount applied to unit prices
     */
    public function getItemsWithCustomsDiscount(): array
    {
        $items = $this->shipment->items;
        $discount = $this->customs_discount_percentage / 100;
        
        return $items->map(function ($item) use ($discount) {
            $customsUnitPrice = $item->unit_price * (1 - $discount);
            $customsValue = $customsUnitPrice * $item->quantity_to_ship;
            
            return [
                'product_sku' => $item->product_sku,
                'product_name' => $item->product_name,
                'product_description' => $item->product_description,
                'hs_code' => $item->hs_code,
                'country_of_origin' => $item->country_of_origin,
                'quantity' => $item->quantity_to_ship,
                'unit_price' => $item->unit_price,
                'customs_unit_price' => $customsUnitPrice,
                'total_value' => $item->unit_price * $item->quantity_to_ship,
                'customs_total_value' => $customsValue,
                'unit_weight' => $item->unit_weight,
                'total_weight' => $item->unit_weight * $item->quantity_to_ship,
                'unit_volume' => $item->unit_volume,
                'total_volume' => $item->unit_volume * $item->quantity_to_ship,
            ];
        })->toArray();
    }

    /**
     * Get default display options
     */
    public function getDefaultDisplayOptions(): array
    {
        return [
            'show_exporter_tax_id' => true,
            'show_exporter_phone' => true,
            'show_exporter_email' => true,
            'show_importer_tax_id' => true,
            'show_importer_phone' => true,
            'show_importer_email' => true,
            'show_notify_party' => false,
            'show_bank_info' => false,
            'show_payment_terms' => true,
            'show_terms_of_sale' => false,
            'show_declaration' => true,
            'show_additional_notes' => false,
            'show_unit_weight' => true,
            'show_unit_volume' => true,
            'show_hs_code' => true,
            'show_country_of_origin' => true,
        ];
    }

    /**
     * Generate PDF (placeholder - will be implemented in Phase 4)
     */
    public function generatePDF(string $version = 'original'): string
    {
        // TODO: Implement in Phase 4
        // version: 'original' or 'customs'
        return '';
    }

    /**
     * Generate Excel (placeholder - will be implemented in Phase 5)
     */
    public function generateExcel(string $version = 'original'): string
    {
        // TODO: Implement in Phase 5
        // version: 'original' or 'customs'
        return '';
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
            
            if (!$invoice->invoice_date) {
                $invoice->invoice_date = now();
            }
        });
    }

    /**
     * Generate unique invoice number
     */
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
