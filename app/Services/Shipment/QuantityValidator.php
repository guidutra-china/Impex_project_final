<?php

namespace App\Services\Shipment;

// use App\Models\SalesInvoiceItem; // DEPRECATED: Refactored to CommercialInvoice
use App\Models\ShipmentItem;
use App\Models\Shipment;

class QuantityValidator
{
    /**
     * Validate if quantity can be shipped from invoice item
     * DEPRECATED: SalesInvoiceItem no longer exists
     */
    /*
    public function validateShipmentQuantity(SalesInvoiceItem $invoiceItem, int $quantityToShip): array
    {
        $errors = [];

        // Check if quantity is positive
        if ($quantityToShip <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }

        // Check if quantity doesn't exceed remaining
        if ($quantityToShip > $invoiceItem->quantity_remaining) {
            $errors[] = sprintf(
                'Cannot ship %d units. Only %d remaining (ordered: %d, already shipped: %d)',
                $quantityToShip,
                $invoiceItem->quantity_remaining,
                $invoiceItem->quantity,
                $invoiceItem->quantity_shipped
            );
        }

        // Check if item can be shipped
        if (!$invoiceItem->canBeShipped()) {
            $errors[] = 'This item is already fully shipped';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'available_quantity' => $invoiceItem->quantity_remaining,
        ];
    }
    */

    /**
     * Validate if quantity can be packed
     */
    public function validatePackingQuantity(ShipmentItem $shipmentItem, int $quantityToPack): array
    {
        $errors = [];

        // Check if quantity is positive
        if ($quantityToPack <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }

        // Calculate what would be packed after this operation
        $currentPacked = $shipmentItem->quantity_packed;
        $totalAfterPacking = $currentPacked + $quantityToPack;

        // Check if doesn't exceed quantity to ship
        if ($totalAfterPacking > $shipmentItem->quantity_to_ship) {
            $remaining = $shipmentItem->quantity_to_ship - $currentPacked;
            $errors[] = sprintf(
                'Cannot pack %d units. Only %d remaining (to ship: %d, already packed: %d)',
                $quantityToPack,
                $remaining,
                $shipmentItem->quantity_to_ship,
                $currentPacked
            );
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'available_quantity' => $shipmentItem->quantity_to_ship - $currentPacked,
        ];
    }

    /**
     * Validate entire shipment before confirmation
     */
    public function validateShipmentForConfirmation(Shipment $shipment): array
    {
        $errors = [];
        $warnings = [];

        // Must have at least one item
        if ($shipment->items()->count() === 0) {
            $errors[] = 'Shipment must have at least one item';
        }

        // Check if all items are from attached invoices
        // DEPRECATED: salesInvoiceItem no longer exists
        /*
        foreach ($shipment->items as $item) {
            if ($item->salesInvoiceItem) {
                $invoiceId = $item->salesInvoiceItem->sales_invoice_id;
                
                if (!$shipment->salesInvoices()->where('sales_invoice_id', $invoiceId)->exists()) {
                    $warnings[] = sprintf(
                        'Item "%s" is from invoice that is not attached to this shipment',
                        $item->product_name
                    );
                }
            }
        }
        */

        // If there are packing boxes, all items must be fully packed
        if ($shipment->packingBoxes()->count() > 0) {
            $unpackedItems = $shipment->items()
                ->where('packing_status', '!=', 'fully_packed')
                ->get();

            if ($unpackedItems->count() > 0) {
                $errors[] = sprintf(
                    '%d item(s) are not fully packed. All items must be packed before confirmation.',
                    $unpackedItems->count()
                );

                foreach ($unpackedItems as $item) {
                    $errors[] = sprintf(
                        '  - %s: %d/%d packed',
                        $item->product_name,
                        $item->quantity_packed,
                        $item->quantity_to_ship
                    );
                }
            }
        }

        // Check if shipment has valid status
        if (!in_array($shipment->status, ['draft', 'preparing', 'ready_to_ship'])) {
            $errors[] = sprintf(
                'Shipment cannot be confirmed in "%s" status',
                $shipment->status
            );
        }

        // Check if already confirmed
        if ($shipment->confirmed_at) {
            $errors[] = 'Shipment is already confirmed';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'can_confirm' => empty($errors),
        ];
    }

    /**
     * Validate packing box before sealing
     */
    public function validateBoxForSealing(\App\Models\PackingBox $box): array
    {
        $errors = [];

        // Must have items
        if ($box->total_quantity === 0) {
            $errors[] = 'Box must have at least one item before sealing';
        }

        // Must be in packing status
        if ($box->packing_status !== 'packing') {
            $errors[] = sprintf(
                'Box must be in "packing" status to be sealed (current: %s)',
                $box->packing_status
            );
        }

        // Check if already sealed
        if ($box->packing_status === 'sealed') {
            $errors[] = 'Box is already sealed';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'can_seal' => empty($errors),
        ];
    }

    /**
     * Get validation summary for shipment
     */
    public function getShipmentValidationSummary(Shipment $shipment): array
    {
        $confirmationValidation = $this->validateShipmentForConfirmation($shipment);
        
        $itemsValidation = [];
        foreach ($shipment->items as $item) {
            $packingValidation = $this->validatePackingQuantity($item, 0);
            
            $itemsValidation[] = [
                'item_id' => $item->id,
                'product_name' => $item->product_name,
                'quantity_to_ship' => $item->quantity_to_ship,
                'quantity_packed' => $item->quantity_packed,
                'packing_status' => $item->packing_status,
                'is_fully_packed' => $item->packing_status === 'fully_packed',
            ];
        }

        $boxesValidation = [];
        foreach ($shipment->packingBoxes as $box) {
            $sealValidation = $this->validateBoxForSealing($box);
            
            $boxesValidation[] = [
                'box_id' => $box->id,
                'box_number' => $box->box_number,
                'total_items' => $box->total_items,
                'total_quantity' => $box->total_quantity,
                'packing_status' => $box->packing_status,
                'can_seal' => $sealValidation['can_seal'],
            ];
        }

        return [
            'shipment_id' => $shipment->id,
            'can_confirm' => $confirmationValidation['can_confirm'],
            'confirmation_errors' => $confirmationValidation['errors'],
            'confirmation_warnings' => $confirmationValidation['warnings'],
            'items' => $itemsValidation,
            'boxes' => $boxesValidation,
            'summary' => [
                'total_items' => count($itemsValidation),
                'fully_packed_items' => collect($itemsValidation)->where('is_fully_packed', true)->count(),
                'total_boxes' => count($boxesValidation),
                'sealed_boxes' => collect($boxesValidation)->where('packing_status', 'sealed')->count(),
            ],
        ];
    }
}
