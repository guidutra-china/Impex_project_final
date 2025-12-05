<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentContainerItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_container_id',
        'proforma_invoice_item_id',
        'product_id',
        'quantity',
        'unit_weight',
        'total_weight',
        'unit_volume',
        'total_volume',
        'hs_code',
        'country_of_origin',
        'unit_price',
        'customs_value',
        'packing_box_id',
        'status',
        'shipment_sequence',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_weight' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'unit_volume' => 'decimal:4',
        'total_volume' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'customs_value' => 'decimal:2',
        'shipment_sequence' => 'integer',
    ];

    /**
     * Relationships
     */
    public function container(): BelongsTo
    {
        return $this->belongsTo(ShipmentContainer::class, 'shipment_container_id');
    }

    public function proformaInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packingBox(): BelongsTo
    {
        return $this->belongsTo(PackingBox::class);
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        // Ao criar: recalcular totals do container
        static::created(function ($item) {
            // Don't call addShipped here - that's for actual shipping, not packing
            // ShipmentItem already handles quantity tracking
            if ($item->container) {
                $item->container->calculateTotals();
            }
        });

        // Ao deletar: recalcular totals do container
        static::deleted(function ($item) {
            // Don't call removeShipped here - that's for actual shipping, not packing
            // ShipmentItem already handles quantity tracking
            if ($item->container) {
                $item->container->calculateTotals();
            }
        });
    }
}
