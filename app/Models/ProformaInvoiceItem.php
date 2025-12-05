<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaInvoiceItem extends Model
{
    protected $fillable = [
        'proforma_invoice_id',
        'supplier_quote_id',
        'quote_item_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'quantity_shipped',
        'quantity_remaining',
        'shipment_count',
        'unit_price',
        'commission_amount',
        'commission_percent',
        'commission_type',
        'total',
        'notes',
        'delivery_days',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_shipped' => 'integer',
        'quantity_remaining' => 'integer',
        'shipment_count' => 'integer',
        'delivery_days' => 'integer',
        'commission_percent' => 'decimal:2',
    ];

    /**
     * Attribute accessors for amounts (convert cents to dollars)
     */
    protected function unitPrice(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    protected function commissionAmount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    protected function total(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total before saving
        static::saving(function ($item) {
            // Calculate total from quantity and unit_price
            if ($item->quantity && $item->unit_price) {
                $item->total = $item->quantity * $item->unit_price;
            }
            
            // Auto-fill product name and SKU
            if ($item->product_id && !$item->product_name) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $item->product_name = $product->name;
                    $item->product_sku = $product->sku;
                }
            }
        });

        // Recalculate proforma totals after save/delete
        static::saved(function ($item) {
            $item->proformaInvoice->recalculateTotals();
        });

        static::deleted(function ($item) {
            $item->proformaInvoice->recalculateTotals();
        });
    }

    /**
     * Relationships
     */
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(SupplierQuote::class);
    }

    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(QuoteItem::class);
    }

    public function shipmentContainerItems()
    {
        return $this->hasMany(ShipmentContainerItem::class);
    }

    /**
     * Methods for shipment tracking
     */
    public function getQuantityRemaining(): int
    {
        return $this->quantity - ($this->quantity_shipped ?? 0);
    }

    public function canShip(int $quantity): bool
    {
        return $quantity <= $this->getQuantityRemaining();
    }

    public function getShipments()
    {
        return $this->shipmentContainerItems()
            ->with('container.shipment')
            ->get()
            ->groupBy(fn($item) => $item->container->shipment_id)
            ->map(fn($items) => [
                'shipment_id' => $items->first()->container->shipment_id,
                'shipment_number' => $items->first()->container->shipment->shipment_number,
                'quantity' => $items->sum('quantity'),
                'containers' => $items->map(fn($i) => $i->container->container_number)->unique()->values(),
                'sequence' => $items->first()->shipment_sequence,
            ]);
    }

    public function addShipped(int $quantity): void
    {
        if (!$this->canShip($quantity)) {
            throw new \Exception(
                "Quantidade insuficiente. Restante: {$this->getQuantityRemaining()}, Solicitado: {$quantity}"
            );
        }

        $this->quantity_shipped = ($this->quantity_shipped ?? 0) + $quantity;
        $this->quantity_remaining = $this->quantity - $this->quantity_shipped;
        $this->shipment_count = ($this->shipment_count ?? 0) + 1;
        $this->save();
    }

    public function removeShipped(int $quantity): void
    {
        $this->quantity_shipped = max(0, ($this->quantity_shipped ?? 0) - $quantity);
        $this->quantity_remaining = $this->quantity - $this->quantity_shipped;
        $this->save();
    }

    public function isFullyShipped(): bool
    {
        return $this->quantity_shipped >= $this->quantity;
    }

    public function isPartiallyShipped(): bool
    {
        return $this->quantity_shipped > 0 && $this->quantity_shipped < $this->quantity;
    }
}
