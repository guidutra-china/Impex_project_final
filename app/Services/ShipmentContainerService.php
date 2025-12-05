<?php

namespace App\Services;

use App\Models\ShipmentContainer;
use App\Models\ShipmentContainerItem;
use App\Models\ProformaInvoiceItem;
use Exception;

class ShipmentContainerService
{
    /**
     * Add item to container with validations
     */
    public function addItemToContainer(
        ShipmentContainer $container,
        ProformaInvoiceItem $piItem,
        int $quantity
    ): ShipmentContainerItem {
        // V1: Validate available quantity
        if (!$piItem->canShip($quantity)) {
            throw new Exception(
                "Insufficient quantity for {$piItem->product_name}. " .
                "Remaining: {$piItem->getQuantityRemaining()}, " .
                "Requested: {$quantity}"
            );
        }

        // V2: Validate container capacity
        $totalWeight = $quantity * $piItem->product->weight;
        $totalVolume = $quantity * $piItem->product->volume;

        if (!$container->canFit($totalWeight, $totalVolume)) {
            throw new Exception(
                "Container {$container->container_number} has insufficient capacity. " .
                "Weight: {$totalWeight}kg / {$container->getRemainingWeight()}kg, " .
                "Volume: {$totalVolume}m³ / {$container->getRemainingVolume()}m³"
            );
        }

        // V3: Validate that PI is in this shipment
        if (!$container->shipment->shipmentInvoices()
            ->where('proforma_invoice_id', $piItem->proforma_invoice_id)
            ->exists()) {
            throw new Exception(
                "ProformaInvoice {$piItem->proforma_invoice_id} is not in this shipment"
            );
        }

        // V4: Validate shipment status
        if (!in_array($container->shipment->status, ['draft', 'preparing'])) {
            throw new Exception(
                "Shipment {$container->shipment->shipment_number} does not allow additions (status: {$container->shipment->status})"
            );
        }

        // V5: Validate container status
        if (!in_array($container->status, ['draft', 'packed'])) {
            throw new Exception(
                "Container {$container->container_number} does not allow additions (status: {$container->status})"
            );
        }

        // Create item
        return ShipmentContainerItem::create([
            'shipment_container_id' => $container->id,
            'proforma_invoice_item_id' => $piItem->id,
            'product_id' => $piItem->product_id,
            'quantity' => $quantity,
            'unit_weight' => $piItem->product->weight,
            'total_weight' => $totalWeight,
            'unit_volume' => $piItem->product->volume,
            'total_volume' => $totalVolume,
            'unit_price' => $piItem->unit_price,
            'customs_value' => $quantity * $piItem->unit_price,
            'shipment_sequence' => $piItem->shipment_count + 1,
            'status' => 'draft',
        ]);
    }

    /**
     * Remove item from container with validations
     */
    public function removeItemFromContainer(ShipmentContainerItem $item): void
    {
        // V1: Validate shipment status
        if (!in_array($item->container->shipment->status, ['draft', 'preparing'])) {
            throw new Exception(
                "Cannot remove items from shipment with status {$item->container->shipment->status}"
            );
        }

        // V2: Validate container status
        if (!in_array($item->container->status, ['draft', 'packed'])) {
            throw new Exception(
                "Cannot remove items from container with status {$item->container->status}"
            );
        }

        // Remove (hooks will decrement quantity_shipped)
        $item->delete();
    }

    /**
     * Seal container
     */
    public function sealContainer(ShipmentContainer $container, string $sealNumber, int $userId): void
    {
        // V1: Validate that container has items
        if ($container->items()->count() === 0) {
            throw new Exception("Container cannot be empty");
        }

        // V2: Validate that all items are packed
        if ($container->items()->where('status', '!=', 'packed')->exists()) {
            throw new Exception("Not all items are packed");
        }

        // V3: Validate that seal_number is unique
        if (ShipmentContainer::where('seal_number', $sealNumber)
            ->where('id', '!=', $container->id)
            ->exists()) {
            throw new Exception("Seal number {$sealNumber} is already in use");
        }

        $container->seal($sealNumber, $userId);
    }

    /**
     * Unseal container
     */
    public function unsealContainer(ShipmentContainer $container): void
    {
        // V1: Validate that shipment was not confirmed
        if ($container->shipment->status !== 'preparing') {
            throw new Exception(
                "Cannot unseal container from shipment with status {$container->shipment->status}"
            );
        }

        $container->unseal();
    }

    /**
     * Calculate container totals
     */
    public function calculateContainerTotals(ShipmentContainer $container): void
    {
        $container->calculateTotals();
    }

    /**
     * Get container summary
     */
    public function getContainerSummary(ShipmentContainer $container): array
    {
        $items = $container->items()->with('product', 'proformaInvoiceItem')->get();

        return [
            'container_number' => $container->container_number,
            'container_type' => $container->container_type,
            'status' => $container->status,
            'seal_number' => $container->seal_number,
            'items_count' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'weight' => [
                'current' => $container->current_weight,
                'max' => $container->max_weight,
                'remaining' => $container->getRemainingWeight(),
                'utilization' => round($container->getWeightUtilization(), 2) . '%',
            ],
            'volume' => [
                'current' => $container->current_volume,
                'max' => $container->max_volume,
                'remaining' => $container->getRemainingVolume(),
                'utilization' => round($container->getVolumeUtilization(), 2) . '%',
            ],
            'proforma_invoices' => $container->getProformaInvoicesInContainer(),
        ];
    }
}
