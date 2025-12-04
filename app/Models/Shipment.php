<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'vessel_name',
        'voyage_number',
        'flight_number',
        'shipping_method',
        'status',
        'origin_address',
        'destination_address',
        'origin_port',
        'destination_port',
        'notify_party_address',
        'shipment_date',
        'estimated_delivery_date',
        'actual_delivery_date',
        'estimated_departure_date',
        'actual_departure_date',
        'estimated_arrival_date',
        'actual_arrival_date',
        'shipping_cost',
        'insurance_cost',
        'other_costs',
        'total_shipping_cost',
        'currency_id',
        'total_weight',
        'total_volume',
        'total_boxes',
        'total_items',
        'total_quantity',
        'incoterm',
        'commercial_invoice_generated',
        'packing_list_generated',
        'bill_of_lading_number',
        'awb_number',
        'notes',
        'special_instructions',
        'customs_notes',
        'notification_sent_at',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'estimated_departure_date' => 'date',
        'actual_departure_date' => 'date',
        'estimated_arrival_date' => 'date',
        'actual_arrival_date' => 'date',
        'notification_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'commercial_invoice_generated' => 'boolean',
        'packing_list_generated' => 'boolean',
        'total_boxes' => 'integer',
        'total_items' => 'integer',
        'total_quantity' => 'integer',
        'shipping_cost' => 'integer',
        'insurance_cost' => 'integer',
        'other_costs' => 'integer',
        'total_shipping_cost' => 'integer',
        'total_weight' => 'decimal:2',
        'total_volume' => 'decimal:4',
    ];

    /**
     * Get shipping_cost in decimal format for display
     */
    protected function shippingCost(): \Illuminate\Database\Eloquent\Casts\Attribute
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
     * Get total_shipping_cost in decimal format for display
     */
    protected function totalShippingCost(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    // Relationships
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sales_order_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * NEW: Many-to-Many relationship with SalesInvoice
     */
    public function salesInvoices(): BelongsToMany
    {
        return $this->belongsToMany(SalesInvoice::class, 'shipment_invoices')
            ->withPivot(['total_items', 'total_quantity', 'total_value', 'notes'])
            ->withTimestamps();
    }

    /**
     * NEW: Pivot records for shipment-invoice relationships
     */
    public function shipmentInvoices(): HasMany
    {
        return $this->hasMany(ShipmentInvoice::class);
    }

    /**
     * Alias for salesInvoices() relationship
     */
    public function invoices(): BelongsToMany
    {
        return $this->salesInvoices();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * NEW: Packing boxes
     */
    public function packingBoxes(): HasMany
    {
        return $this->hasMany(PackingBox::class);
    }

    /**
     * Shipment containers relationship
     */
    public function containers(): HasMany
    {
        return $this->hasMany(ShipmentContainer::class);
    }

    /**
     * NEW: Commercial invoice
     */
    public function commercialInvoice(): HasOne
    {
        return $this->hasOne(CommercialInvoice::class);
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

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
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

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmed_at');
    }

    // Methods

    /**
     * Calculate all totals from items and boxes
     */
    public function calculateTotals(): void
    {
        // Calculate from items
        $this->total_items = $this->items()->count();
        $this->total_quantity = $this->items()->sum('quantity_to_ship');
        $this->total_weight = $this->items()->sum('total_weight');
        $this->total_volume = $this->items()->sum('total_volume');
        
        // Calculate from packing boxes
        $this->total_boxes = $this->packingBoxes()->count();
        
        // Calculate total shipping cost
        $this->total_shipping_cost = ($this->shipping_cost ?? 0) + 
                                     ($this->insurance_cost ?? 0) + 
                                     ($this->other_costs ?? 0);
        
        $this->save();
    }

    /**
     * Check if shipment can be confirmed
     */
    public function canBeConfirmed(): bool
    {
        // Must be in draft or preparing status
        if (!in_array($this->status, ['draft', 'preparing', 'ready_to_ship'])) {
            return false;
        }

        // Must have at least one item
        if ($this->items()->count() === 0) {
            return false;
        }

        // All items must be fully packed (if packing is enabled)
        if ($this->packingBoxes()->count() > 0) {
            $unpackedItems = $this->items()
                ->where('packing_status', '!=', 'fully_packed')
                ->count();
            
            if ($unpackedItems > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Confirm the shipment (lock it and update invoice quantities)
     */
    public function confirm(): void
    {
        if (!$this->canBeConfirmed()) {
            throw new \Exception('Shipment cannot be confirmed');
        }

        \DB::transaction(function () {
            // Update shipment status
            $this->status = 'confirmed';
            $this->confirmed_at = now();
            $this->confirmed_by = auth()->id();
            
            // Set quantity_shipped = quantity_to_ship for all items
            foreach ($this->items as $item) {
                $item->quantity_shipped = $item->quantity_to_ship;
                $item->save();
                
                // Update the sales invoice item
                $item->updateSalesInvoiceItem();
            }
            
            // Recalculate totals
            $this->calculateTotals();
            
            $this->save();
        });
    }

    /**
     * Generate commercial invoice
     */
    public function generateCommercialInvoice(): CommercialInvoice
    {
        // Check if already exists
        if ($this->commercialInvoice) {
            return $this->commercialInvoice;
        }

        // Create new commercial invoice
        $invoice = new CommercialInvoice();
        $invoice->shipment_id = $this->id;
        
        // Copy data from shipment
        $invoice->port_of_loading = $this->origin_port;
        $invoice->port_of_discharge = $this->destination_port;
        $invoice->vessel_flight_number = $this->vessel_name ?? $this->flight_number;
        $invoice->incoterm = $this->incoterm;
        $invoice->currency_id = $this->currency_id;
        
        // TODO: Get exporter/importer info from settings or first invoice
        
        $invoice->save();
        $invoice->calculateTotals();
        
        return $invoice;
    }

    /**
     * Generate packing list PDF (placeholder)
     */
    public function generatePackingList(): string
    {
        // TODO: Implement in Phase 4
        $this->packing_list_generated = true;
        $this->save();
        
        return '';
    }

    /**
     * Get shipping progress as percentage
     */
    public function getShippingProgress(): array
    {
        $statuses = [
            'draft' => 0,
            'preparing' => 10,
            'ready_to_ship' => 20,
            'confirmed' => 30,
            'picked_up' => 40,
            'in_transit' => 60,
            'customs_clearance' => 75,
            'out_for_delivery' => 85,
            'delivered' => 100,
            'cancelled' => 0,
            'returned' => 0,
        ];

        return [
            'status' => $this->status,
            'progress' => $statuses[$this->status] ?? 0,
            'is_complete' => $this->status === 'delivered',
            'is_cancelled' => in_array($this->status, ['cancelled', 'returned']),
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (!$shipment->shipment_number) {
                $shipment->shipment_number = static::generateShipmentNumber();
            }
        });
    }

    /**
     * Generate unique shipment number
     */
    public static function generateShipmentNumber(): string
    {
        $year = now()->year;
        
        try {
            // Use dedicated sequence table with pessimistic locking
            return \DB::transaction(function () use ($year) {
            $sequence = ShipmentSequence::forYear($year);
            
            // Lock the sequence row to prevent concurrent increments
            $sequence = ShipmentSequence::where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
            
            $nextNumber = $sequence->next_number;
            
            // Increment for next call
            $sequence->increment('next_number');
            
            // Validate we don't exceed 5 digits
            if ($nextNumber > 99999) {
                throw new \Exception('Shipment number exceeds maximum for year ' . $year);
            }

            return sprintf('SHP-%d-%05d', $year, $nextNumber);
            });
        } catch (\Exception $e) {
            // Fallback if shipment_sequences table doesn't exist yet
            if (strpos($e->getMessage(), 'shipment_sequences') !== false || 
                strpos($e->getMessage(), 'Base table or view not found') !== false) {
                
                // Use shipment records directly with lock
                return \DB::transaction(function () use ($year) {
                    $lastShipment = static::whereYear('created_at', $year)
                        ->orderBy('id', 'desc')
                        ->lockForUpdate()
                        ->first();

                    $nextNumber = $lastShipment ? (int) substr($lastShipment->shipment_number, -5) + 1 : 1;

                    if ($nextNumber > 99999) {
                        throw new \Exception('Shipment number exceeds maximum for year ' . $year);
                    }

                    return sprintf('SHP-%d-%05d', $year, $nextNumber);
                });
            }
            
            throw $e;
        }
    }

    // Métodos adicionais para containers
    public function getProformaInvoicesInShipment()
    {
        return $this->shipmentInvoices()
            ->with('proformaInvoice')
            ->get()
            ->map(fn($si) => [
                'proforma_invoice_id' => $si->proforma_invoice_id,
                'proforma_number' => $si->proformaInvoice->proforma_number,
                'total_quantity' => $si->total_quantity,
                'is_fully_shipped' => $si->isFullyShipped(),
                'sequence' => $si->getShipmentSequence(),
            ]);
    }

    public function getContainersByProformaInvoice($proformaInvoiceId)
    {
        return $this->containers()
            ->whereHas('items', fn($q) => 
                $q->whereHas('proformaInvoiceItem', fn($q2) => 
                    $q2->where('proforma_invoice_id', $proformaInvoiceId)
                )
            )
            ->get();
    }
}
