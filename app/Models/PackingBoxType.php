<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackingBoxType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'length',
        'width',
        'height',
        'max_weight',
        'max_volume',
        'tare_weight',
        'unit_cost',
        'currency_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'max_volume' => 'decimal:4',
        'tare_weight' => 'decimal:2',
    ];

    /**
     * Get the currency associated with the packing box type.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user who created this packing box type.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the packing boxes of this type.
     */
    public function packingBoxes(): HasMany
    {
        return $this->hasMany(PackingBox::class);
    }

    /**
     * Calculate volume from dimensions (L x W x H in cm = cm³, convert to m³)
     */
    public function calculateVolume(): float
    {
        // Convert cm³ to m³ (divide by 1,000,000)
        return ($this->length * $this->width * $this->height) / 1000000;
    }
}
