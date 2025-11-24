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
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'integer',
        'freight_cost' => 'integer',
        'insurance_cost' => 'integer',
        'other_costs' => 'integer',
        'total_value' => 'integer',
        'issued_at' => 'datetime',
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
     * Generate PDF (placeholder - will be implemented in Phase 4)
     */
    public function generatePDF(): string
    {
        // TODO: Implement in Phase 4
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
