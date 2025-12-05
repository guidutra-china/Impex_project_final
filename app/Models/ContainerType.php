<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContainerType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'category',
        'unit_system',
        'description',
        'length',
        'width',
        'height',
        'max_weight',
        'max_volume',
        'tare_weight',
        'base_cost',
        'currency_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'category' => 'string',
        'unit_system' => 'string',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'max_volume' => 'decimal:4',
        'tare_weight' => 'decimal:2',
    ];

    /**
     * Get the currency associated with the container type.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user who created this container type.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the shipment containers of this type.
     */
    public function shipmentContainers(): HasMany
    {
        return $this->hasMany(ShipmentContainer::class);
    }

    /**
     * Calculate volume from dimensions (L x W x H in meters = m³)
     */
    public function calculateVolume(): float
    {
        return ($this->length * $this->width * $this->height);
    }
}
