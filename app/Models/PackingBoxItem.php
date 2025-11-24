<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingBoxItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'packing_box_id',
        'shipment_item_id',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationships
    public function packingBox(): BelongsTo
    {
        return $this->belongsTo(PackingBox::class);
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        // After creating/updating/deleting, update the packing box totals
        static::saved(function ($item) {
            $item->packingBox->calculateTotals();
            $item->shipmentItem->updatePackingStatus();
        });

        static::deleted(function ($item) {
            $item->packingBox->calculateTotals();
            $item->shipmentItem->updatePackingStatus();
        });
    }
}
