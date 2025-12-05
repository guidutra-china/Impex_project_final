<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PackingBox extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'shipment_container_id',
        'box_number',
        'box_label',
        'box_type',
        'length',
        'width',
        'height',
        'gross_weight',
        'net_weight',
        'volume',
        'total_items',
        'total_quantity',
        'packing_status',
        'sealed_at',
        'sealed_by',
        'notes',
        'contents_description',
    ];

    protected $casts = [
        'box_number' => 'integer',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'gross_weight' => 'decimal:2',
        'net_weight' => 'decimal:2',
        'volume' => 'decimal:4',
        'total_items' => 'integer',
        'total_quantity' => 'integer',
        'sealed_at' => 'datetime',
    ];

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(ShipmentContainer::class, 'shipment_container_id');
    }

    public function packingBoxItems(): HasMany
    {
        return $this->hasMany(PackingBoxItem::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackingBoxItem::class);
    }

    public function sealedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sealed_by');
    }

    // Methods

    /**
     * Calculate volume from dimensions (L x W x H in cm, convert to m³)
     */
    public function calculateVolume(): float
    {
        if (!$this->length || !$this->width || !$this->height) {
            return 0;
        }

        // Convert cm³ to m³
        return ($this->length * $this->width * $this->height) / 1000000;
    }

    /**
     * Calculate totals from packing box items
     */
    public function calculateTotals(): void
    {
        $this->total_items = $this->packingBoxItems()->count();
        $this->total_quantity = $this->packingBoxItems()->sum('quantity');
        
        // Calculate net weight from items
        $netWeight = 0;
        foreach ($this->packingBoxItems as $item) {
            $netWeight += ($item->unit_weight ?? 0) * $item->quantity;
        }
        $this->net_weight = $netWeight;
        
        // Calculate volume if dimensions are set
        if ($this->length && $this->width && $this->height) {
            $this->volume = $this->calculateVolume();
        }
        
        $this->save();
    }

    /**
     * Alias for calculateTotals()
     */
    public function recalculateTotals(): void
    {
        $this->calculateTotals();
    }

    /**
     * Seal the box (lock it)
     */
    public function seal(): void
    {
        if ($this->canBeSealed()) {
            $this->packing_status = 'sealed';
            $this->sealed_at = now();
            $this->sealed_by = auth()->id();
            $this->save();
        }
    }

    /**
     * Check if box can be sealed
     */
    public function canBeSealed(): bool
    {
        return $this->packing_status === 'packing' && $this->total_quantity > 0;
    }

    /**
     * Get items summary for display
     */
    public function getItemsSummary(): array
    {
        $summary = [];
        
        foreach ($this->packingBoxItems as $item) {
            $shipmentItem = $item->shipmentItem;
            $summary[] = [
                'product_name' => $shipmentItem->product_name,
                'product_sku' => $shipmentItem->product_sku,
                'quantity' => $item->quantity,
            ];
        }
        
        return $summary;
    }

    /**
     * Get box label (auto-generate if not set)
     */
    public function getBoxLabelAttribute($value): string
    {
        return $value ?? sprintf('BOX-%03d', $this->box_number);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($box) {
            // Auto-set box number if not provided
            if (!$box->box_number) {
                $lastBox = static::where('shipment_id', $box->shipment_id)
                    ->orderBy('box_number', 'desc')
                    ->first();
                
                $box->box_number = $lastBox ? $lastBox->box_number + 1 : 1;
            }
        });
    }
}
