<?php

namespace App\Services\Shipment;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentService
{
    /**
     * Create a new shipment
     */
    public function createShipment(array $data): Shipment
    {
        return DB::transaction(function () use ($data) {
            $shipment = Shipment::create([
                'shipment_type' => $data['shipment_type'] ?? 'outbound',
                'shipping_method' => $data['shipping_method'] ?? null,
                'carrier' => $data['carrier'] ?? null,
                'shipment_date' => $data['shipment_date'] ?? now(),
                'origin_address' => $data['origin_address'] ?? null,
                'destination_address' => $data['destination_address'] ?? null,
                'currency_id' => $data['currency_id'] ?? 1,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            Log::info('Shipment created', ['shipment_id' => $shipment->id]);

            return $shipment;
        });
    }

    /**
     * Attach sales invoices to shipment
     */
    public function attachInvoices(Shipment $shipment, array $invoiceIds): void
    {
        DB::transaction(function () use ($shipment, $invoiceIds) {
            foreach ($invoiceIds as $invoiceId) {
                if (!$shipment->salesInvoices()->where('sales_invoice_id', $invoiceId)->exists()) {
                    $shipment->salesInvoices()->attach($invoiceId);
                    
                    Log::info('Invoice attached to shipment', [
                        'shipment_id' => $shipment->id,
                        'invoice_id' => $invoiceId,
                    ]);
                }
            }
        });
    }

    /**
     * Detach sales invoice from shipment
     */
    public function detachInvoice(Shipment $shipment, int $invoiceId): void
    {
        DB::transaction(function () use ($shipment, $invoiceId) {
            // Remove any shipment items from this invoice
            $shipment->items()
                ->whereHas('salesInvoiceItem', function ($query) use ($invoiceId) {
                    $query->where('sales_invoice_id', $invoiceId);
                })
                ->delete();

            // Detach invoice
            $shipment->salesInvoices()->detach($invoiceId);

            Log::info('Invoice detached from shipment', [
                'shipment_id' => $shipment->id,
                'invoice_id' => $invoiceId,
            ]);
        });
    }

    /**
     * Add item to shipment with quantity validation
     */
    public function addItem(Shipment $shipment, array $itemData): ShipmentItem
    {
        return DB::transaction(function () use ($shipment, $itemData) {
            // Validate quantity
            $this->validateItemQuantity($itemData);

            // Create shipment item
            $item = $shipment->items()->create([
                'sales_invoice_item_id' => $itemData['sales_invoice_item_id'],
                'product_id' => $itemData['product_id'],
                'quantity_ordered' => $itemData['quantity_ordered'] ?? 0,
                'quantity_to_ship' => $itemData['quantity_to_ship'],
                'quantity_shipped' => 0,
                'product_name' => $itemData['product_name'],
                'product_sku' => $itemData['product_sku'],
                'unit_price' => $itemData['unit_price'] ?? 0,
            ]);

            // Load product data
            $item->loadProductData();
            $item->calculateTotals();

            Log::info('Item added to shipment', [
                'shipment_id' => $shipment->id,
                'item_id' => $item->id,
                'quantity' => $item->quantity_to_ship,
            ]);

            return $item;
        });
    }

    /**
     * Validate item quantity against remaining quantity in invoice
     */
    protected function validateItemQuantity(array $itemData): void
    {
        if (!isset($itemData['sales_invoice_item_id'])) {
            return; // Skip validation if no invoice item
        }

        $invoiceItem = SalesInvoiceItem::find($itemData['sales_invoice_item_id']);
        
        if (!$invoiceItem) {
            throw new \Exception('Sales invoice item not found');
        }

        $quantityToShip = $itemData['quantity_to_ship'];
        $remaining = $invoiceItem->quantity_remaining;

        if ($quantityToShip > $remaining) {
            throw new \Exception(
                "Cannot ship {$quantityToShip} units. Only {$remaining} remaining for {$invoiceItem->product_name}"
            );
        }

        if ($quantityToShip <= 0) {
            throw new \Exception('Quantity to ship must be greater than 0');
        }
    }

    /**
     * Update shipment item quantity
     */
    public function updateItemQuantity(ShipmentItem $item, int $newQuantity): void
    {
        DB::transaction(function () use ($item, $newQuantity) {
            // Validate new quantity
            $this->validateItemQuantity([
                'sales_invoice_item_id' => $item->sales_invoice_item_id,
                'quantity_to_ship' => $newQuantity,
            ]);

            $item->quantity_to_ship = $newQuantity;
            $item->calculateTotals();
            $item->save();

            Log::info('Item quantity updated', [
                'item_id' => $item->id,
                'new_quantity' => $newQuantity,
            ]);
        });
    }

    /**
     * Remove item from shipment
     */
    public function removeItem(ShipmentItem $item): void
    {
        DB::transaction(function () use ($item) {
            $shipmentId = $item->shipment_id;
            
            // Remove from any packing boxes first
            $item->packingBoxItems()->delete();
            
            // Delete item
            $item->delete();

            Log::info('Item removed from shipment', [
                'shipment_id' => $shipmentId,
                'item_id' => $item->id,
            ]);
        });
    }

    /**
     * Confirm shipment (lock it and update invoice quantities)
     */
    public function confirmShipment(Shipment $shipment): void
    {
        if (!$shipment->canBeConfirmed()) {
            throw new \Exception('Shipment cannot be confirmed. Please check all items are packed and ready.');
        }

        DB::transaction(function () use ($shipment) {
            $shipment->confirm();

            Log::info('Shipment confirmed', [
                'shipment_id' => $shipment->id,
                'total_items' => $shipment->total_items,
                'total_quantity' => $shipment->total_quantity,
            ]);

            // TODO: Send notifications
        });
    }

    /**
     * Cancel shipment
     */
    public function cancelShipment(Shipment $shipment, string $reason = null): void
    {
        if ($shipment->status === 'delivered') {
            throw new \Exception('Cannot cancel a delivered shipment');
        }

        DB::transaction(function () use ($shipment, $reason) {
            // If shipment was confirmed, we need to reverse the quantities
            if ($shipment->status === 'confirmed' || $shipment->confirmed_at) {
                $this->reverseInvoiceQuantities($shipment);
            }

            $shipment->status = 'cancelled';
            $shipment->notes = ($shipment->notes ? $shipment->notes . "\n\n" : '') . 
                              "Cancelled: " . ($reason ?? 'No reason provided');
            $shipment->save();

            Log::info('Shipment cancelled', [
                'shipment_id' => $shipment->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Reverse invoice quantities (when cancelling a confirmed shipment)
     */
    protected function reverseInvoiceQuantities(Shipment $shipment): void
    {
        foreach ($shipment->items as $item) {
            if ($item->quantity_shipped > 0 && $item->salesInvoiceItem) {
                $invoiceItem = $item->salesInvoiceItem;
                
                // Subtract the shipped quantity
                $invoiceItem->quantity_shipped -= $item->quantity_shipped;
                $invoiceItem->quantity_remaining = $invoiceItem->quantity - $invoiceItem->quantity_shipped;
                
                // Update status
                if ($invoiceItem->quantity_shipped === 0) {
                    $invoiceItem->shipment_status = 'not_shipped';
                } elseif ($invoiceItem->quantity_shipped < $invoiceItem->quantity) {
                    $invoiceItem->shipment_status = 'partially_shipped';
                }
                
                $invoiceItem->save();
            }
        }
    }

    /**
     * Update shipment status
     */
    public function updateStatus(Shipment $shipment, string $newStatus): void
    {
        $validStatuses = [
            'draft', 'preparing', 'ready_to_ship', 'confirmed', 
            'picked_up', 'in_transit', 'customs_clearance', 
            'out_for_delivery', 'delivered', 'cancelled', 'returned'
        ];

        if (!in_array($newStatus, $validStatuses)) {
            throw new \Exception("Invalid status: {$newStatus}");
        }

        DB::transaction(function () use ($shipment, $newStatus) {
            $oldStatus = $shipment->status;
            $shipment->status = $newStatus;
            
            // Set actual dates based on status
            if ($newStatus === 'picked_up' && !$shipment->actual_departure_date) {
                $shipment->actual_departure_date = now();
            }
            
            if ($newStatus === 'delivered' && !$shipment->actual_delivery_date) {
                $shipment->actual_delivery_date = now();
                $shipment->actual_arrival_date = now();
            }
            
            $shipment->save();

            Log::info('Shipment status updated', [
                'shipment_id' => $shipment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        });
    }

    /**
     * Calculate and update all shipment totals
     */
    public function recalculateTotals(Shipment $shipment): void
    {
        DB::transaction(function () use ($shipment) {
            $shipment->calculateTotals();
            
            // Update shipment-invoice pivot totals
            foreach ($shipment->shipmentInvoices as $pivot) {
                $pivot->calculateTotals();
            }

            Log::info('Shipment totals recalculated', [
                'shipment_id' => $shipment->id,
                'total_quantity' => $shipment->total_quantity,
                'total_weight' => $shipment->total_weight,
            ]);
        });
    }

    /**
     * Get available items from attached invoices
     */
    public function getAvailableItems(Shipment $shipment): array
    {
        $availableItems = [];

        foreach ($shipment->salesInvoices as $invoice) {
            foreach ($invoice->items as $item) {
                if ($item->canBeShipped()) {
                    $availableItems[] = [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku,
                        'quantity_ordered' => $item->quantity,
                        'quantity_shipped' => $item->quantity_shipped,
                        'quantity_remaining' => $item->quantity_remaining,
                        'unit_price' => $item->unit_price,
                    ];
                }
            }
        }

        return $availableItems;
    }

    /**
     * Bulk add items from invoice
     */
    public function addItemsFromInvoice(Shipment $shipment, int $invoiceId, array $itemQuantities): void
    {
        DB::transaction(function () use ($shipment, $invoiceId, $itemQuantities) {
            $invoice = SalesInvoice::findOrFail($invoiceId);

            foreach ($itemQuantities as $itemId => $quantity) {
                if ($quantity <= 0) {
                    continue;
                }

                $invoiceItem = $invoice->items()->findOrFail($itemId);

                $this->addItem($shipment, [
                    'sales_invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'quantity_ordered' => $invoiceItem->quantity,
                    'quantity_to_ship' => $quantity,
                    'product_name' => $invoiceItem->product_name,
                    'product_sku' => $invoiceItem->product_sku,
                    'unit_price' => $invoiceItem->unit_price,
                ]);
            }

            Log::info('Bulk items added from invoice', [
                'shipment_id' => $shipment->id,
                'invoice_id' => $invoiceId,
                'items_count' => count($itemQuantities),
            ]);
        });
    }
}
