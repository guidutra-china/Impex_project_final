<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentContainer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipment_id',
        'container_number',
        'container_type',
        'max_weight',
        'max_volume',
        'current_weight',
        'current_volume',
        'status',
        'seal_number',
        'sealed_at',
        'sealed_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'max_weight' => 'decimal:2',
        'max_volume' => 'decimal:4',
        'current_weight' => 'decimal:2',
        'current_volume' => 'decimal:4',
        'sealed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentContainerItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class);
    }

    public function sealedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sealed_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Methods
     */
    public function getRemainingWeight(): float
    {
        return (float) ($this->max_weight - $this->current_weight);
    }

    public function getRemainingVolume(): float
    {
        return (float) ($this->max_volume - $this->current_volume);
    }

    public function getWeightUtilization(): float
    {
        return ($this->current_weight / $this->max_weight) * 100;
    }

    public function getVolumeUtilization(): float
    {
        return ($this->current_volume / $this->max_volume) * 100;
    }

    public function canFit(float $weight, float $volume): bool
    {
        return $weight <= $this->getRemainingWeight() && 
               $volume <= $this->getRemainingVolume();
    }

    public function calculateTotals(): void
    {
        $this->current_weight = $this->items()->sum('total_weight');
        $this->current_volume = $this->items()->sum('total_volume');
        $this->save();
    }

    public function getProformaInvoicesInContainer()
    {
        return $this->items()
            ->with('proformaInvoiceItem.proformaInvoice')
            ->get()
            ->groupBy(fn($item) => $item->proformaInvoiceItem->proforma_invoice_id)
            ->map(fn($items) => [
                'proforma_invoice_id' => $items->first()->proformaInvoiceItem->proforma_invoice_id,
                'proforma_invoice_number' => $items->first()->proformaInvoiceItem->proformaInvoice->proforma_number,
                'quantity' => $items->sum('quantity'),
                'items_count' => $items->count(),
                'total_weight' => $items->sum('total_weight'),
                'total_volume' => $items->sum('total_volume'),
            ]);
    }

    public function seal(string $sealNumber, int $userId): void
    {
        if ($this->status === 'sealed') {
            throw new \Exception('Container já está selado');
        }

        if ($this->items()->count() === 0) {
            throw new \Exception('Container não pode estar vazio');
        }

        $this->seal_number = $sealNumber;
        $this->sealed_at = now();
        $this->sealed_by = $userId;
        $this->status = 'sealed';
        $this->save();
    }

    public function unseal(): void
    {
        if ($this->status !== 'sealed') {
            throw new \Exception('Container não está selado');
        }

        $this->seal_number = null;
        $this->sealed_at = null;
        $this->sealed_by = null;
        $this->status = 'packed';
        $this->save();
    }
}
