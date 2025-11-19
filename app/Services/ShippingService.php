<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class ShippingService
{
    /**
     * Create shipment from Purchase Order
     *
     * @param PurchaseOrder $purchaseOrder
     * @param array $data
     * @return Shipment
     */
    public function createFromPurchaseOrder(PurchaseOrder $purchaseOrder, array $data): Shipment
    {
        return DB::transaction(function () use ($purchaseOrder, $data) {
            // Create shipment
            $shipment = Shipment::create([
                'shipment_number' => $this->generateShipmentNumber(),
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'origin_address' => $data['origin_address'] ?? null,
                'destination_address' => $purchaseOrder->delivery_address,
                'status' => 'pending',
                'created_by' => auth()->id(),
                ...$data,
            ]);

            // Create shipment items from PO items
            foreach ($purchaseOrder->items as $poItem) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'quantity' => $poItem->quantity,
                    'product_name' => $poItem->product_name,
                    'product_sku' => $poItem->product_sku,
                ]);
            }

            // Create initial tracking event
            $this->addTrackingEvent($shipment, 'pending', 'Shipment created');

            return $shipment->load('items');
        });
    }

    /**
     * Update shipment status
     *
     * @param Shipment $shipment
     * @param string $status
     * @param string|null $notes
     * @return Shipment
     */
    public function updateStatus(Shipment $shipment, string $status, ?string $notes = null): Shipment
    {
        DB::transaction(function () use ($shipment, $status, $notes) {
            $shipment->update(['status' => $status]);

            // Update specific date fields
            match($status) {
                'picked_up' => $shipment->update(['actual_pickup_date' => now()]),
                'delivered' => $shipment->update(['actual_delivery_date' => now()]),
                default => null,
            };

            // Add tracking event
            $this->addTrackingEvent($shipment, $status, $notes);
        });

        return $shipment->fresh();
    }

    /**
     * Add tracking event
     *
     * @param Shipment $shipment
     * @param string $status
     * @param string|null $description
     * @param string|null $location
     * @return TrackingEvent
     */
    public function addTrackingEvent(
        Shipment $shipment, 
        string $status, 
        ?string $description = null,
        ?string $location = null
    ): TrackingEvent {
        return TrackingEvent::create([
            'shipment_id' => $shipment->id,
            'status' => $status,
            'description' => $description ?? $this->getDefaultDescription($status),
            'location' => $location,
            'event_date' => now(),
        ]);
    }

    /**
     * Mark shipment as delivered
     *
     * @param Shipment $shipment
     * @param array $receivedQuantities [item_id => quantity]
     * @return Shipment
     */
    public function markAsDelivered(Shipment $shipment, array $receivedQuantities = []): Shipment
    {
        return DB::transaction(function () use ($shipment, $receivedQuantities) {
            // Update shipment status
            $shipment->update([
                'status' => 'delivered',
                'actual_delivery_date' => now(),
            ]);

            // Update received quantities if provided
            foreach ($receivedQuantities as $itemId => $quantity) {
                $item = $shipment->items()->find($itemId);
                if ($item) {
                    $item->update(['received_quantity' => $quantity]);
                    
                    // Update PO item received quantity
                    if ($item->purchaseOrderItem) {
                        $item->purchaseOrderItem->increment('received_quantity', $quantity);
                    }
                }
            }

            // Add tracking event
            $this->addTrackingEvent($shipment, 'delivered', 'Shipment delivered successfully');

            // Update PO status if all items received
            if ($shipment->purchaseOrder) {
                $this->updatePurchaseOrderStatus($shipment->purchaseOrder);
            }

            return $shipment->fresh('items');
        });
    }

    /**
     * Update Purchase Order status based on received quantities
     *
     * @param PurchaseOrder $po
     * @return void
     */
    private function updatePurchaseOrderStatus(PurchaseOrder $po): void
    {
        $totalOrdered = $po->items()->sum('quantity');
        $totalReceived = $po->items()->sum('received_quantity');

        if ($totalReceived >= $totalOrdered) {
            $po->update(['status' => 'received']);
        } elseif ($totalReceived > 0) {
            $po->update(['status' => 'partially_received']);
        }
    }

    /**
     * Get default description for status
     *
     * @param string $status
     * @return string
     */
    private function getDefaultDescription(string $status): string
    {
        return match($status) {
            'pending' => 'Shipment is pending',
            'preparing' => 'Shipment is being prepared',
            'ready_to_ship' => 'Shipment is ready to ship',
            'picked_up' => 'Shipment has been picked up',
            'in_transit' => 'Shipment is in transit',
            'customs_clearance' => 'Shipment is in customs clearance',
            'out_for_delivery' => 'Shipment is out for delivery',
            'delivered' => 'Shipment has been delivered',
            'cancelled' => 'Shipment has been cancelled',
            'returned' => 'Shipment has been returned',
            default => 'Status updated',
        };
    }

    /**
     * Generate unique shipment number
     *
     * @return string
     */
    private function generateShipmentNumber(): string
    {
        $year = date('Y');
        $lastShipment = Shipment::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastShipment ? (int) substr($lastShipment->shipment_number, -4) + 1 : 1;

        return sprintf('SHP-%s-%04d', $year, $nextNumber);
    }
}
