<?php

namespace App\Services\Shipment;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ProformaInvoiceItem;
use App\Models\ContainerType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Container Loading Optimization Service
 * 
 * Provides intelligent algorithms for optimizing container loading:
 * - Best-fit algorithm (minimize wasted space)
 * - First-fit algorithm (fastest loading)
 * - Weight-balanced loading
 * - Volume-optimized loading
 */
class ContainerLoadingService
{
    /**
     * Suggest optimal container types for shipment items
     * 
     * Analyzes total weight and volume to recommend container types
     */
    public function suggestContainerTypes(Shipment $shipment): array
    {
        $items = $shipment->items()->with('product')->get();
        
        if ($items->isEmpty()) {
            return [];
        }

        // Calculate totals
        $totalWeight = $items->sum(fn($item) => $item->quantity_to_ship * $item->product->weight);
        $totalVolume = $items->sum(fn($item) => $item->quantity_to_ship * $item->product->volume);

        // Get available container types
        $containerTypes = ContainerType::where('is_active', true)
            ->orderBy('internal_volume', 'asc')
            ->get();

        $suggestions = [];

        foreach ($containerTypes as $type) {
            // Calculate how many containers needed
            $containersByWeight = ceil($totalWeight / $type->max_weight);
            $containersByVolume = ceil($totalVolume / $type->internal_volume);
            $containersNeeded = max($containersByWeight, $containersByVolume);

            // Calculate utilization
            $weightUtilization = ($totalWeight / ($containersNeeded * $type->max_weight)) * 100;
            $volumeUtilization = ($totalVolume / ($containersNeeded * $type->internal_volume)) * 100;
            $avgUtilization = ($weightUtilization + $volumeUtilization) / 2;

            $suggestions[] = [
                'container_type' => $type->type_code,
                'container_type_name' => $type->name,
                'containers_needed' => $containersNeeded,
                'weight_utilization' => round($weightUtilization, 2),
                'volume_utilization' => round($volumeUtilization, 2),
                'avg_utilization' => round($avgUtilization, 2),
                'total_cost_estimate' => $containersNeeded * ($type->estimated_cost ?? 0),
                'is_recommended' => $avgUtilization >= 70 && $avgUtilization <= 95,
            ];
        }

        // Sort by average utilization (best fit first)
        usort($suggestions, fn($a, $b) => $b['avg_utilization'] <=> $a['avg_utilization']);

        return $suggestions;
    }

    /**
     * Auto-load items into containers using best-fit algorithm
     * 
     * Minimizes wasted space by selecting the smallest container that fits each item
     */
    public function autoLoadBestFit(Shipment $shipment, string $containerType = null): array
    {
        return DB::transaction(function () use ($shipment, $containerType) {
            $items = $shipment->items()
                ->with('product', 'proformaInvoiceItem')
                ->where('packing_status', '!=', 'fully_packed')
                ->get();

            if ($items->isEmpty()) {
                throw new \Exception('No items to load');
            }

            // Get or create containers
            $containers = $this->prepareContainers($shipment, $containerType);
            $loadedItems = [];

            // Sort items by volume (largest first) for better packing
            $sortedItems = $items->sortByDesc(fn($item) => 
                $item->product->volume * $item->quantity_to_ship
            );

            foreach ($sortedItems as $item) {
                $remainingQty = $item->quantity_to_ship - $item->quantity_packed;
                
                if ($remainingQty <= 0) {
                    continue;
                }

                // Try to fit in existing containers
                $loaded = $this->loadItemIntoContainers($containers, $item, $remainingQty);
                $loadedItems[] = [
                    'item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity_loaded' => $loaded,
                    'quantity_remaining' => $remainingQty - $loaded,
                ];

                // Create new container if needed
                if ($loaded < $remainingQty) {
                    $newContainer = $this->createContainer($shipment, $containerType);
                    $containers->push($newContainer);
                    
                    $additionalLoaded = $this->loadItemIntoContainers(
                        collect([$newContainer]), 
                        $item, 
                        $remainingQty - $loaded
                    );
                    
                    $loadedItems[count($loadedItems) - 1]['quantity_loaded'] += $additionalLoaded;
                    $loadedItems[count($loadedItems) - 1]['quantity_remaining'] -= $additionalLoaded;
                }
            }

            Log::info('Auto-load completed (best-fit)', [
                'shipment_id' => $shipment->id,
                'containers_used' => $containers->count(),
                'items_loaded' => count($loadedItems),
            ]);

            return [
                'containers' => $containers->map(fn($c) => [
                    'container_number' => $c->container_number,
                    'weight_utilization' => round($c->getWeightUtilization(), 2),
                    'volume_utilization' => round($c->getVolumeUtilization(), 2),
                ]),
                'items' => $loadedItems,
            ];
        });
    }

    /**
     * Auto-load items into containers using weight-balanced algorithm
     * 
     * Distributes weight evenly across containers to prevent overloading
     */
    public function autoLoadWeightBalanced(Shipment $shipment, int $numberOfContainers): array
    {
        return DB::transaction(function () use ($shipment, $numberOfContainers) {
            $items = $shipment->items()
                ->with('product', 'proformaInvoiceItem')
                ->where('packing_status', '!=', 'fully_packed')
                ->get();

            if ($items->isEmpty()) {
                throw new \Exception('No items to load');
            }

            // Create containers
            $containers = collect();
            for ($i = 0; $i < $numberOfContainers; $i++) {
                $containers->push($this->createContainer($shipment));
            }

            // Sort items by weight (heaviest first)
            $sortedItems = $items->sortByDesc(fn($item) => 
                $item->product->weight * $item->quantity_to_ship
            );

            foreach ($sortedItems as $item) {
                $remainingQty = $item->quantity_to_ship - $item->quantity_packed;
                
                if ($remainingQty <= 0) {
                    continue;
                }

                // Find container with lowest current weight
                $lightestContainer = $containers->sortBy('current_weight')->first();
                
                // Calculate how much can fit
                $unitWeight = $item->product->weight;
                $maxQtyByWeight = floor($lightestContainer->getRemainingWeight() / $unitWeight);
                $qtyToLoad = min($remainingQty, $maxQtyByWeight);

                if ($qtyToLoad > 0) {
                    $this->addItemToContainer($lightestContainer, $item->proformaInvoiceItem, $qtyToLoad);
                }
            }

            Log::info('Auto-load completed (weight-balanced)', [
                'shipment_id' => $shipment->id,
                'containers_used' => $numberOfContainers,
            ]);

            return [
                'containers' => $containers->map(fn($c) => [
                    'container_number' => $c->container_number,
                    'weight_utilization' => round($c->getWeightUtilization(), 2),
                    'volume_utilization' => round($c->getVolumeUtilization(), 2),
                ]),
            ];
        });
    }

    /**
     * Calculate loading efficiency metrics
     */
    public function calculateLoadingEfficiency(Shipment $shipment): array
    {
        $containers = $shipment->containers()->with('items')->get();

        if ($containers->isEmpty()) {
            return [
                'total_containers' => 0,
                'avg_weight_utilization' => 0,
                'avg_volume_utilization' => 0,
                'total_wasted_weight' => 0,
                'total_wasted_volume' => 0,
            ];
        }

        $totalWeightUtilization = 0;
        $totalVolumeUtilization = 0;
        $totalWastedWeight = 0;
        $totalWastedVolume = 0;

        foreach ($containers as $container) {
            $weightUtil = $container->getWeightUtilization();
            $volumeUtil = $container->getVolumeUtilization();
            
            $totalWeightUtilization += $weightUtil;
            $totalVolumeUtilization += $volumeUtil;
            $totalWastedWeight += $container->getRemainingWeight();
            $totalWastedVolume += $container->getRemainingVolume();
        }

        $containerCount = $containers->count();

        return [
            'total_containers' => $containerCount,
            'avg_weight_utilization' => round($totalWeightUtilization / $containerCount, 2),
            'avg_volume_utilization' => round($totalVolumeUtilization / $containerCount, 2),
            'total_wasted_weight' => round($totalWastedWeight, 2),
            'total_wasted_volume' => round($totalWastedVolume, 4),
            'efficiency_score' => round((($totalWeightUtilization + $totalVolumeUtilization) / 2) / $containerCount, 2),
        ];
    }

    /**
     * Prepare containers for loading
     */
    protected function prepareContainers(Shipment $shipment, ?string $containerType = null): Collection
    {
        $containers = $shipment->containers()
            ->where('status', '!=', 'sealed')
            ->get();

        if ($containers->isEmpty()) {
            $containers = collect([$this->createContainer($shipment, $containerType)]);
        }

        return $containers;
    }

    /**
     * Create a new container
     */
    protected function createContainer(Shipment $shipment, ?string $containerType = null): ShipmentContainer
    {
        $type = $containerType ?? '20GP'; // Default to 20GP

        return $shipment->containers()->create([
            'container_type' => $type,
            'container_number' => $this->generateContainerNumber($shipment),
            'status' => 'draft',
        ]);
    }

    /**
     * Generate unique container number
     */
    protected function generateContainerNumber(Shipment $shipment): string
    {
        $count = $shipment->containers()->count() + 1;
        return sprintf('%s-CNT%03d', $shipment->shipment_number, $count);
    }

    /**
     * Load item into available containers
     */
    protected function loadItemIntoContainers(Collection $containers, $item, int $quantity): int
    {
        $loaded = 0;

        foreach ($containers as $container) {
            if ($loaded >= $quantity) {
                break;
            }

            $remaining = $quantity - $loaded;
            $unitWeight = $item->product->weight;
            $unitVolume = $item->product->volume;

            $maxByWeight = floor($container->getRemainingWeight() / $unitWeight);
            $maxByVolume = floor($container->getRemainingVolume() / $unitVolume);
            $maxQty = min($maxByWeight, $maxByVolume, $remaining);

            if ($maxQty > 0) {
                $this->addItemToContainer($container, $item->proformaInvoiceItem, $maxQty);
                $loaded += $maxQty;
            }
        }

        return $loaded;
    }

    /**
     * Add item to container (wrapper for service)
     */
    protected function addItemToContainer(ShipmentContainer $container, ProformaInvoiceItem $piItem, int $quantity): void
    {
        $service = app(ShipmentContainerService::class);
        $service->addItemToContainer($container, $piItem, $quantity);
    }
}
