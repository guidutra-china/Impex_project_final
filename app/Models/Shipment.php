<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipment_number',
        'sales_order_id',
        'purchase_order_id',
        'shipment_type',
        'carrier',
        'tracking_number',
        'container_number',
        'shipping_method',
        'status',
        'origin_address',
        'destination_address',
        'shipment_date',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipping_cost',
        'currency_id',
        'total_weight',
        'total_volume',
        'notes',
        'special_instructions',
        'notification_sent_at',
        'created_by',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'notification_sent_at' => 'datetime',
    ];

    // Relationships
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sales_order_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('event_date', 'desc');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'related');
    }

    // Scopes
    public function scopeInTransit($query)
    {
        return $query->whereIn('status', ['picked_up', 'in_transit', 'customs_clearance', 'out_for_delivery']);
    }

    public function scopeDelayed($query)
    {
        return $query->where('estimated_delivery_date', '<', now())
            ->whereNull('actual_delivery_date')
            ->whereNotIn('status', ['delivered', 'cancelled']);
    }
}