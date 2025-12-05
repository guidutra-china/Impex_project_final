<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'sales_order_item_id',
        'sales_invoice_item_id',
        'proforma_invoice_item_id',
        'product_id',
        'quantity_ordered',
        'quantity_to_ship',
        'quantity_shipped',
        'product_name',
        'product_sku',
        'product_description',
        'hs_code',
        'country_of_origin',
        'unit_price',
        'customs_value',
        'unit_weight',
        'total_weight',
        'unit_volume',
        'total_volume',
        'packing_status',
        'quantity_packed',
        'quantity_remaining',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_to_ship' => 'integer',
        'quantity_shipped' => 'integer',
        'quantity_packed' => 'integer',
        'quantity_remaining' => 'integer',
        'unit_price' => 'integer',
        'customs_value' => 'integer',
        'unit_weight' => 'decimal:3',
        'total_weight' => 'decimal:3',
        'unit_volume' => 'decimal:6',
        'total_volume' => 'decimal:6',
    ];

    /**
     * Get unit_price in decimal format for display
     */
    protected function unitPrice(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) round($value * 100),
        );
    }

    /**
     * Get customs_value in decimal format for display
     */
    protected function customsValue(): \Illuminate\Database\Eloquent\Casts\Attribute
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

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'sales_order_item_id');
    }

    /**
     * Relationship to SalesInvoiceItem (legacy/backward compatibility)
     */
    public function salesInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceItem::class);
    }

    /**
     * Relationship to ProformaInvoiceItem (primary)
     */
    public function proformaInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * NEW: Packing box items (which boxes contain this item)
     */
    public function packingBoxItems(): HasMany
    {
        return $this->hasMany(PackingBoxItem::class);
    }

    // Methods

    /**
     * Calculate totals based on quantity and unit values
     */
    public function calculateTotals(): void
    {
        if ($this->unit_weight) {
            $this->total_weight = $this->quantity_to_ship * $this->unit_weight;
        }

        if ($this->unit_volume) {
            $this->total_volume = $this->quantity_to_ship * $this->unit_volume;
        }

        if ($this->unit_price) {
            $this->customs_value = $this->quantity_to_ship * $this->unit_price;
        }

        $this->save();
    }

    /**
     * Update packing status based on packed quantity
     */
    public function updatePackingStatus(): void
    {
        // Calculate total packed from all packing box items
        $this->quantity_packed = $this->packingBoxItems()->sum('quantity');
        $this->quantity_remaining = $this->quantity_to_ship - $this->quantity_packed;

        // Update status
        if ($this->quantity_packed === 0) {
            $this->packing_status = 'unpacked';
        } elseif ($this->quantity_packed < $this->quantity_to_ship) {
            $this->packing_status = 'partially_packed';
        } else {
            $this->packing_status = 'fully_packed';
        }

        $this->save();
    }

    /**
     * Update the related sales invoice item quantities
     */
    public function updateSalesInvoiceItem(): void
    {
        if (!$this->salesInvoiceItem) {
            return;
        }

        $invoiceItem = $this->salesInvoiceItem;
        
        // Add this shipment's quantity to the total shipped
        $invoiceItem->quantity_shipped += $this->quantity_shipped;
        $invoiceItem->quantity_remaining = $invoiceItem->quantity - $invoiceItem->quantity_shipped;
        
        // Update status
        if ($invoiceItem->quantity_shipped === 0) {
            $invoiceItem->shipment_status = 'not_shipped';
        } elseif ($invoiceItem->quantity_shipped < $invoiceItem->quantity) {
            $invoiceItem->shipment_status = 'partially_shipped';
        } else {
            $invoiceItem->shipment_status = 'fully_shipped';
        }
        
        $invoiceItem->save();
    }

    /**
     * Load product data into shipment item
     */
    public function loadProductData(): void
    {
        if (!$this->product) {
            return;
        }

        $product = $this->product;
        
        $this->product_name = $product->name;
        $this->product_sku = $product->sku;
        $this->product_description = $product->description;
        $this->hs_code = $product->hs_code;
        $this->country_of_origin = $product->country_of_origin;
        $this->unit_weight = $product->weight;
        $this->unit_volume = $product->volume;
        
        // Get price from sales invoice item if available
        if ($this->salesInvoiceItem) {
            $this->unit_price = $this->salesInvoiceItem->unit_price;
        }
        
        $this->save();
    }

    /**
     * Update packed quantity based on container items
     */
    public function updatePackedQuantity(): void
    {
        // Sum quantity from all container items for this shipment item
        $totalPacked = \App\Models\ShipmentContainerItem::whereHas('container', function ($query) {
            $query->where('shipment_id', $this->shipment_id);
        })
        ->where('product_id', $this->product_id)
        ->sum('quantity');
        
        $this->quantity_packed = $totalPacked;
        
        // Update packing status
        if ($totalPacked == 0) {
            $this->packing_status = 'unpacked';
        } elseif ($totalPacked < $this->quantity_to_ship) {
            $this->packing_status = 'partially_packed';
        } else {
            $this->packing_status = 'fully_packed';
        }
        
        $this->save();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Load product data if product_id is set
            if ($item->product_id && !$item->product_name) {
                $item->loadProductData();
            }
            
            // Initialize quantity_remaining
            if (!$item->quantity_remaining) {
                $item->quantity_remaining = $item->quantity_to_ship;
            }
        });

        static::saved(function ($item) {
            // Recalculate shipment totals
            $item->shipment->calculateTotals();
        });
    }
}
