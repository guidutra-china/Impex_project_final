<?php

namespace App\Services\Shipment;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\PackingBox;
use App\Models\PackingBoxItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackingService
{
    /**
     * Create a new packing box
     */
    public function createBox(Shipment $shipment, array $data): PackingBox
    {
        return DB::transaction(function () use ($shipment, $data) {
            $box = $shipment->packingBoxes()->create([
                'box_type' => $data['box_type'] ?? 'carton',
                'box_label' => $data['box_label'] ?? null,
                'length' => $data['length'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'gross_weight' => $data['gross_weight'] ?? null,
                'notes' => $data['notes'] ?? null,
                'packing_status' => 'empty',
            ]);

            // Calculate volume if dimensions provided
            if ($box->length && $box->width && $box->height) {
                $box->volume = $box->calculateVolume();
                $box->save();
            }

            Log::info('Packing box created', [
                'shipment_id' => $shipment->id,
                'box_id' => $box->id,
                'box_number' => $box->box_number,
            ]);

            return $box;
        });
    }

    /**
     * Update packing box details
     */
    public function updateBox(PackingBox $box, array $data): void
    {
        if ($box->packing_status === 'sealed') {
            throw new \Exception('Cannot update a sealed box');
        }

        DB::transaction(function () use ($box, $data) {
            $box->update([
                'box_type' => $data['box_type'] ?? $box->box_type,
                'box_label' => $data['box_label'] ?? $box->box_label,
                'length' => $data['length'] ?? $box->length,
                'width' => $data['width'] ?? $box->width,
                'height' => $data['height'] ?? $box->height,
                'gross_weight' => $data['gross_weight'] ?? $box->gross_weight,
                'notes' => $data['notes'] ?? $box->notes,
            ]);

            // Recalculate volume
            if ($box->length && $box->width && $box->height) {
                $box->volume = $box->calculateVolume();
                $box->save();
            }

            Log::info('Packing box updated', ['box_id' => $box->id]);
        });
    }

    /**
     * Delete packing box (only if empty or not sealed)
     */
    public function deleteBox(PackingBox $box): void
    {
        if ($box->packing_status === 'sealed') {
            throw new \Exception('Cannot delete a sealed box');
        }

        if ($box->total_quantity > 0) {
            throw new \Exception('Cannot delete a box with items. Remove items first.');
        }

        DB::transaction(function () use ($box) {
            $shipmentId = $box->shipment_id;
            $boxNumber = $box->box_number;
            
            $box->delete();

            Log::info('Packing box deleted', [
                'shipment_id' => $shipmentId,
                'box_number' => $boxNumber,
            ]);
        });
    }

    /**
     * Add item to packing box
     */
    public function addItemToBox(PackingBox $box, ShipmentItem $item, int $quantity): PackingBoxItem
    {
        if ($box->packing_status === 'sealed') {
            throw new \Exception('Cannot add items to a sealed box');
        }

        // Validate quantity
        $this->validatePackingQuantity($item, $quantity);

        return DB::transaction(function () use ($box, $item, $quantity) {
            // Check if item already in box
            $existingItem = $box->packingBoxItems()
                ->where('shipment_item_id', $item->id)
                ->first();

            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem->quantity + $quantity;
                $this->validatePackingQuantity($item, $newQuantity - $existingItem->quantity);
                
                $existingItem->quantity = $newQuantity;
                $existingItem->save();
                
                $packingBoxItem = $existingItem;
            } else {
                // Create new
                $packingBoxItem = $box->packingBoxItems()->create([
                    'shipment_item_id' => $item->id,
                    'quantity' => $quantity,
                ]);
            }

            // Update box status
            if ($box->packing_status === 'empty') {
                $box->packing_status = 'packing';
                $box->save();
            }

            Log::info('Item added to packing box', [
                'box_id' => $box->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
            ]);

            return $packingBoxItem;
        });
    }

    /**
     * Validate packing quantity
     */
    protected function validatePackingQuantity(ShipmentItem $item, int $additionalQuantity): void
    {
        $currentPacked = $item->quantity_packed;
        $totalAfterPacking = $currentPacked + $additionalQuantity;

        if ($additionalQuantity <= 0) {
            throw new \Exception('Quantity must be greater than 0');
        }

        if ($totalAfterPacking > $item->quantity_to_ship) {
            $remaining = $item->quantity_to_ship - $currentPacked;
            throw new \Exception(
                "Cannot pack {$additionalQuantity} units. Only {$remaining} remaining for {$item->product_name}"
            );
        }
    }

    /**
     * Update item quantity in box
     */
    public function updateItemQuantity(PackingBoxItem $packingBoxItem, int $newQuantity): void
    {
        $box = $packingBoxItem->packingBox;
        
        if ($box->packing_status === 'sealed') {
            throw new \Exception('Cannot update items in a sealed box');
        }

        DB::transaction(function () use ($packingBoxItem, $newQuantity) {
            $item = $packingBoxItem->shipmentItem;
            $oldQuantity = $packingBoxItem->quantity;
            $difference = $newQuantity - $oldQuantity;

            // Validate new quantity
            if ($difference > 0) {
                $this->validatePackingQuantity($item, $difference);
            }

            $packingBoxItem->quantity = $newQuantity;
            $packingBoxItem->save();

            Log::info('Packing box item quantity updated', [
                'packing_box_item_id' => $packingBoxItem->id,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
            ]);
        });
    }

    /**
     * Remove item from box
     */
    public function removeItemFromBox(PackingBoxItem $packingBoxItem): void
    {
        $box = $packingBoxItem->packingBox;
        
        if ($box->packing_status === 'sealed') {
            throw new \Exception('Cannot remove items from a sealed box');
        }

        DB::transaction(function () use ($packingBoxItem, $box) {
            $packingBoxItem->delete();

            // Update box status if empty
            if ($box->packingBoxItems()->count() === 0) {
                $box->packing_status = 'empty';
                $box->save();
            }

            Log::info('Item removed from packing box', [
                'box_id' => $box->id,
                'packing_box_item_id' => $packingBoxItem->id,
            ]);
        });
    }

    /**
     * Seal a packing box (lock it)
     */
    public function sealBox(PackingBox $box): void
    {
        if (!$box->canBeSealed()) {
            throw new \Exception('Box cannot be sealed. It must be in packing status and have items.');
        }

        DB::transaction(function () use ($box) {
            $box->seal();

            Log::info('Packing box sealed', [
                'box_id' => $box->id,
                'box_number' => $box->box_number,
                'total_items' => $box->total_items,
                'total_quantity' => $box->total_quantity,
            ]);
        });
    }

    /**
     * Unseal a packing box (unlock it)
     */
    public function unsealBox(PackingBox $box): void
    {
        if ($box->packing_status !== 'sealed') {
            throw new \Exception('Box is not sealed');
        }

        DB::transaction(function () use ($box) {
            $box->packing_status = 'packing';
            $box->sealed_at = null;
            $box->sealed_by = null;
            $box->save();

            Log::info('Packing box unsealed', ['box_id' => $box->id]);
        });
    }

    /**
     * Auto-pack items (distribute items across boxes evenly)
     */
    public function autoPackItems(Shipment $shipment, int $numberOfBoxes): array
    {
        if ($numberOfBoxes <= 0) {
            throw new \Exception('Number of boxes must be greater than 0');
        }

        return DB::transaction(function () use ($shipment, $numberOfBoxes) {
            $items = $shipment->items()->where('packing_status', '!=', 'fully_packed')->get();
            
            if ($items->isEmpty()) {
                throw new \Exception('No items to pack');
            }

            // Create boxes if needed
            $existingBoxes = $shipment->packingBoxes()->where('packing_status', '!=', 'sealed')->count();
            $boxesToCreate = max(0, $numberOfBoxes - $existingBoxes);
            
            for ($i = 0; $i < $boxesToCreate; $i++) {
                $this->createBox($shipment, ['box_type' => 'carton']);
            }

            // Get all available boxes
            $boxes = $shipment->packingBoxes()
                ->where('packing_status', '!=', 'sealed')
                ->orderBy('box_number')
                ->get();

            // Distribute items
            $boxIndex = 0;
            foreach ($items as $item) {
                $remainingQuantity = $item->quantity_to_ship - $item->quantity_packed;
                
                if ($remainingQuantity <= 0) {
                    continue;
                }

                // Distribute across boxes
                $quantityPerBox = ceil($remainingQuantity / $numberOfBoxes);
                
                while ($remainingQuantity > 0) {
                    $box = $boxes[$boxIndex % $numberOfBoxes];
                    $quantityForThisBox = min($quantityPerBox, $remainingQuantity);
                    
                    $this->addItemToBox($box, $item, $quantityForThisBox);
                    
                    $remainingQuantity -= $quantityForThisBox;
                    $boxIndex++;
                }
            }

            Log::info('Auto-pack completed', [
                'shipment_id' => $shipment->id,
                'boxes_used' => $numberOfBoxes,
                'items_packed' => $items->count(),
            ]);

            return $boxes->toArray();
        });
    }

    /**
     * Validate all items are fully packed
     */
    public function validateAllItemsPacked(Shipment $shipment): array
    {
        $unpackedItems = [];

        foreach ($shipment->items as $item) {
            if ($item->packing_status !== 'fully_packed') {
                $unpackedItems[] = [
                    'item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity_to_ship' => $item->quantity_to_ship,
                    'quantity_packed' => $item->quantity_packed,
                    'quantity_remaining' => $item->quantity_remaining,
                ];
            }
        }

        return $unpackedItems;
    }

    /**
     * Get packing summary for shipment
     */
    public function getPackingSummary(Shipment $shipment): array
    {
        $boxes = $shipment->packingBoxes;
        $items = $shipment->items;

        return [
            'total_boxes' => $boxes->count(),
            'sealed_boxes' => $boxes->where('packing_status', 'sealed')->count(),
            'unsealed_boxes' => $boxes->where('packing_status', '!=', 'sealed')->count(),
            'total_items' => $items->count(),
            'fully_packed_items' => $items->where('packing_status', 'fully_packed')->count(),
            'partially_packed_items' => $items->where('packing_status', 'partially_packed')->count(),
            'unpacked_items' => $items->where('packing_status', 'unpacked')->count(),
            'total_quantity' => $items->sum('quantity_to_ship'),
            'packed_quantity' => $items->sum('quantity_packed'),
            'remaining_quantity' => $items->sum('quantity_remaining'),
            'packing_complete' => $items->where('packing_status', '!=', 'fully_packed')->count() === 0,
        ];
    }

    /**
     * Clear all packing (remove all items from boxes)
     */
    public function clearAllPacking(Shipment $shipment): void
    {
        DB::transaction(function () use ($shipment) {
            // Remove all packing box items
            foreach ($shipment->packingBoxes as $box) {
                if ($box->packing_status === 'sealed') {
                    $this->unsealBox($box);
                }
                
                $box->packingBoxItems()->delete();
                $box->packing_status = 'empty';
                $box->save();
            }

            Log::info('All packing cleared', ['shipment_id' => $shipment->id]);
        });
    }
}
