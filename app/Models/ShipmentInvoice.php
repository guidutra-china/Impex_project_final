<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'sales_invoice_id',
        'proforma_invoice_id',
        'total_items',
        'total_quantity',
        'total_value',
        'status',
        'shipped_at',
        'notes',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'total_quantity' => 'integer',
        'total_value' => 'integer',
    ];

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

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    /**
     * Calculate totals from shipment items
     */
    public function calculateTotals(): void
    {
        $shipmentItems = $this->shipment->items()
            ->whereHas('salesInvoiceItem', function ($query) {
                $query->where('sales_invoice_id',
        'proforma_invoice_id', $this->sales_invoice_id);
            })
            ->get();

        $this->total_items = $shipmentItems->count();
        $this->total_quantity = $shipmentItems->sum('quantity_to_ship');
        $this->total_value = $shipmentItems->sum('customs_value');
        $this->save();
    }
}
