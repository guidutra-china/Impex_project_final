<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    /**
     * Add stock to warehouse
     *
     * @param int $warehouseId
     * @param int $productId
     * @param int $quantity
     * @param int|null $locationId
     * @param string|null $batchNumber
     * @return WarehouseStock
     */
    public function addStock(
        int $warehouseId,
        int $productId,
        int $quantity,
        ?int $locationId = null,
        ?string $batchNumber = null
    ): WarehouseStock {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $locationId, $batchNumber) {
            // Find or create stock record
            $stock = WarehouseStock::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'location_id' => $locationId,
                    'batch_number' => $batchNumber,
                ],
                [
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                ]
            );

            // Update quantities
            $stock->increment('quantity_on_hand', $quantity);
            $stock->increment('quantity_available', $quantity);

            return $stock->fresh();
        });
    }

    /**
     * Remove stock from warehouse
     *
     * @param int $warehouseId
     * @param int $productId
     * @param int $quantity
     * @param int|null $locationId
     * @return WarehouseStock
     * @throws \Exception
     */
    public function removeStock(
        int $warehouseId,
        int $productId,
        int $quantity,
        ?int $locationId = null
    ): WarehouseStock {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $locationId) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->firstOrFail();

            // Check if enough stock available
            if ($stock->quantity_available < $quantity) {
                throw new \Exception('Insufficient stock available');
            }

            // Update quantities
            $stock->decrement('quantity_on_hand', $quantity);
            $stock->decrement('quantity_available', $quantity);

            return $stock->fresh();
        });
    }

    /**
     * Reserve stock for order
     *
     * @param int $warehouseId
     * @param int $productId
     * @param int $quantity
     * @return WarehouseStock
     * @throws \Exception
     */
    public function reserveStock(int $warehouseId, int $productId, int $quantity): WarehouseStock
    {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->firstOrFail();

            // Check if enough stock available
            if ($stock->quantity_available < $quantity) {
                throw new \Exception('Insufficient stock available for reservation');
            }

            // Update quantities
            $stock->increment('quantity_reserved', $quantity);
            $stock->decrement('quantity_available', $quantity);

            return $stock->fresh();
        });
    }

    /**
     * Release reserved stock
     *
     * @param int $warehouseId
     * @param int $productId
     * @param int $quantity
     * @return WarehouseStock
     */
    public function releaseReservedStock(int $warehouseId, int $productId, int $quantity): WarehouseStock
    {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->firstOrFail();

            // Update quantities
            $stock->decrement('quantity_reserved', $quantity);
            $stock->increment('quantity_available', $quantity);

            return $stock->fresh();
        });
    }

    /**
     * Create warehouse transfer
     *
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @param array $items [['product_id' => 1, 'quantity' => 10], ...]
     * @param array $data
     * @return WarehouseTransfer
     */
    public function createTransfer(
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items,
        array $data = []
    ): WarehouseTransfer {
        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $items, $data) {
            // Create transfer
            $transfer = WarehouseTransfer::create([
                'transfer_number' => $this->generateTransferNumber(),
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'status' => 'pending',
                'transfer_date' => $data['transfer_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create transfer items and reserve stock
            foreach ($items as $item) {
                WarehouseTransferItem::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);

                // Reserve stock in source warehouse
                $this->reserveStock($fromWarehouseId, $item['product_id'], $item['quantity']);
            }

            return $transfer->load('items');
        });
    }

    /**
     * Complete warehouse transfer
     *
     * @param WarehouseTransfer $transfer
     * @return WarehouseTransfer
     */
    public function completeTransfer(WarehouseTransfer $transfer): WarehouseTransfer
    {
        return DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                // Remove from source warehouse (including reserved)
                $sourceStock = WarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->firstOrFail();

                $sourceStock->decrement('quantity_on_hand', $item->quantity);
                $sourceStock->decrement('quantity_reserved', $item->quantity);

                // Add to destination warehouse
                $this->addStock(
                    $transfer->to_warehouse_id,
                    $item->product_id,
                    $item->quantity
                );
            }

            // Update transfer status
            $transfer->update([
                'status' => 'completed',
                'completed_date' => now(),
            ]);

            return $transfer->fresh();
        });
    }

    /**
     * Cancel warehouse transfer
     *
     * @param WarehouseTransfer $transfer
     * @return WarehouseTransfer
     */
    public function cancelTransfer(WarehouseTransfer $transfer): WarehouseTransfer
    {
        return DB::transaction(function () use ($transfer) {
            // Release reserved stock
            foreach ($transfer->items as $item) {
                $this->releaseReservedStock(
                    $transfer->from_warehouse_id,
                    $item->product_id,
                    $item->quantity
                );
            }

            // Update transfer status
            $transfer->update(['status' => 'cancelled']);

            return $transfer->fresh();
        });
    }

    /**
     * Get stock level for product across all warehouses
     *
     * @param int $productId
     * @return array
     */
    public function getStockLevels(int $productId): array
    {
        $stocks = WarehouseStock::where('product_id', $productId)
            ->with('warehouse')
            ->get();

        return [
            'total_on_hand' => $stocks->sum('quantity_on_hand'),
            'total_reserved' => $stocks->sum('quantity_reserved'),
            'total_available' => $stocks->sum('quantity_available'),
            'by_warehouse' => $stocks->map(fn($stock) => [
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,
                'on_hand' => $stock->quantity_on_hand,
                'reserved' => $stock->quantity_reserved,
                'available' => $stock->quantity_available,
            ])->toArray(),
        ];
    }

    /**
     * Generate unique transfer number
     *
     * @return string
     */
    private function generateTransferNumber(): string
    {
        $year = date('Y');
        $lastTransfer = WarehouseTransfer::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastTransfer ? (int) substr($lastTransfer->transfer_number, -4) + 1 : 1;

        return sprintf('TRF-%s-%04d', $year, $nextNumber);
    }
}
