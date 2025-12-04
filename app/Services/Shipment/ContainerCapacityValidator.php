<?php

namespace App\Services\Shipment;

use App\Models\ShipmentContainer;
use App\Models\ContainerType;
use Illuminate\Support\Collection;

class ContainerCapacityValidator
{
    /**
     * Validate if items can fit in container
     * 
     * @param ShipmentContainer $container
     * @param Collection $items Collection of items to add
     * @return array ['can_fit' => bool, 'issues' => array, 'warnings' => array]
     */
    public function validateItemsFit(ShipmentContainer $container, Collection $items): array
    {
        $result = [
            'can_fit' => true,
            'issues' => [],
            'warnings' => [],
            'metrics' => [],
        ];

        // Calculate total weight and volume of new items
        $additionalWeight = $items->sum(fn($item) => $item->total_weight);
        $additionalVolume = $items->sum(fn($item) => $item->total_volume);

        // Calculate new totals
        $newWeight = $container->current_weight + $additionalWeight;
        $newVolume = $container->current_volume + $additionalVolume;

        // Get container type limits
        $containerType = $container->containerType;
        $maxWeight = $containerType->max_weight;
        $maxVolume = $this->calculateContainerVolume($containerType);

        // Check weight capacity
        if ($newWeight > $maxWeight) {
            $result['can_fit'] = false;
            $result['issues'][] = [
                'type' => 'weight_exceeded',
                'message' => "Weight limit exceeded: {$newWeight}kg > {$maxWeight}kg",
                'current' => $container->current_weight,
                'additional' => $additionalWeight,
                'new_total' => $newWeight,
                'limit' => $maxWeight,
                'excess' => $newWeight - $maxWeight,
            ];
        } elseif ($newWeight >= $maxWeight * 0.95) {
            $result['warnings'][] = [
                'type' => 'weight_critical',
                'message' => "Weight capacity critical: {$newWeight}kg (95%+ of limit)",
                'utilization' => round(($newWeight / $maxWeight) * 100, 2),
            ];
        } elseif ($newWeight >= $maxWeight * 0.90) {
            $result['warnings'][] = [
                'type' => 'weight_high',
                'message' => "Weight capacity high: {$newWeight}kg (90%+ of limit)",
                'utilization' => round(($newWeight / $maxWeight) * 100, 2),
            ];
        }

        // Check volume capacity
        if ($newVolume > $maxVolume) {
            $result['can_fit'] = false;
            $result['issues'][] = [
                'type' => 'volume_exceeded',
                'message' => "Volume limit exceeded: {$newVolume}m³ > {$maxVolume}m³",
                'current' => $container->current_volume,
                'additional' => $additionalVolume,
                'new_total' => $newVolume,
                'limit' => $maxVolume,
                'excess' => $newVolume - $maxVolume,
            ];
        } elseif ($newVolume >= $maxVolume * 0.95) {
            $result['warnings'][] = [
                'type' => 'volume_critical',
                'message' => "Volume capacity critical: {$newVolume}m³ (95%+ of limit)",
                'utilization' => round(($newVolume / $maxVolume) * 100, 2),
            ];
        } elseif ($newVolume >= $maxVolume * 0.90) {
            $result['warnings'][] = [
                'type' => 'volume_high',
                'message' => "Volume capacity high: {$newVolume}m³ (90%+ of limit)",
                'utilization' => round(($newVolume / $maxVolume) * 100, 2),
            ];
        }

        // Calculate utilization metrics
        $result['metrics'] = [
            'weight' => [
                'current' => $container->current_weight,
                'additional' => $additionalWeight,
                'new_total' => $newWeight,
                'limit' => $maxWeight,
                'utilization' => round(($newWeight / $maxWeight) * 100, 2),
                'remaining' => $maxWeight - $newWeight,
            ],
            'volume' => [
                'current' => $container->current_volume,
                'additional' => $additionalVolume,
                'new_total' => $newVolume,
                'limit' => $maxVolume,
                'utilization' => round(($newVolume / $maxVolume) * 100, 2),
                'remaining' => $maxVolume - $newVolume,
            ],
        ];

        return $result;
    }

    /**
     * Validate container balance (weight distribution)
     * 
     * @param ShipmentContainer $container
     * @return array ['is_balanced' => bool, 'warnings' => array]
     */
    public function validateBalance(ShipmentContainer $container): array
    {
        $result = [
            'is_balanced' => true,
            'warnings' => [],
        ];

        // Get all items in container
        $items = $container->items;

        if ($items->isEmpty()) {
            return $result;
        }

        // Calculate weight distribution
        $totalWeight = $items->sum('total_weight');
        $itemCount = $items->count();
        $averageWeight = $totalWeight / $itemCount;

        // Check for significantly heavy items
        $heavyItems = $items->filter(fn($item) => $item->total_weight > $averageWeight * 2);

        if ($heavyItems->isNotEmpty()) {
            $result['warnings'][] = [
                'type' => 'unbalanced_weight',
                'message' => "Container has {$heavyItems->count()} items significantly heavier than average",
                'heavy_items' => $heavyItems->pluck('id')->toArray(),
                'recommendation' => 'Consider distributing heavy items across multiple containers',
            ];
        }

        // Check for concentration of weight
        $topHeaviest = $items->sortByDesc('total_weight')->take(3);
        $topWeight = $topHeaviest->sum('total_weight');
        $weightConcentration = ($topWeight / $totalWeight) * 100;

        if ($weightConcentration > 70) {
            $result['is_balanced'] = false;
            $result['warnings'][] = [
                'type' => 'weight_concentration',
                'message' => "Top 3 items account for {$weightConcentration}% of total weight",
                'concentration' => round($weightConcentration, 2),
                'recommendation' => 'Redistribute items for better balance',
            ];
        }

        return $result;
    }

    /**
     * Validate container safety limits
     * 
     * @param ShipmentContainer $container
     * @return array ['is_safe' => bool, 'issues' => array]
     */
    public function validateSafetyLimits(ShipmentContainer $container): array
    {
        $result = [
            'is_safe' => true,
            'issues' => [],
        ];

        $containerType = $container->containerType;

        // Check if container is overloaded (safety margin)
        $safeWeightLimit = $containerType->max_weight * 0.95; // 95% safety margin
        if ($container->current_weight > $safeWeightLimit) {
            $result['is_safe'] = false;
            $result['issues'][] = [
                'type' => 'safety_weight_exceeded',
                'message' => "Container weight exceeds safe limit (95% of maximum)",
                'current' => $container->current_weight,
                'safe_limit' => $safeWeightLimit,
                'max_limit' => $containerType->max_weight,
            ];
        }

        // Check if container is underutilized (waste)
        $weightUtilization = ($container->current_weight / $containerType->max_weight) * 100;
        if ($weightUtilization < 30 && $container->items()->count() > 0) {
            $result['issues'][] = [
                'type' => 'underutilized',
                'message' => "Container is significantly underutilized ({$weightUtilization}%)",
                'utilization' => round($weightUtilization, 2),
                'recommendation' => 'Consider consolidating with other containers',
            ];
        }

        return $result;
    }

    /**
     * Get optimization suggestions for container
     * 
     * @param ShipmentContainer $container
     * @return array Suggestions for optimization
     */
    public function getOptimizationSuggestions(ShipmentContainer $container): array
    {
        $suggestions = [];

        $containerType = $container->containerType;
        $maxWeight = $containerType->max_weight;
        $maxVolume = $this->calculateContainerVolume($containerType);

        $weightUtilization = ($container->current_weight / $maxWeight) * 100;
        $volumeUtilization = ($container->current_volume / $maxVolume) * 100;

        // Suggest adding more items if underutilized
        if ($weightUtilization < 70 && $volumeUtilization < 70) {
            $remainingWeight = $maxWeight - $container->current_weight;
            $remainingVolume = $maxVolume - $container->current_volume;

            $suggestions[] = [
                'type' => 'add_more_items',
                'priority' => 'high',
                'message' => "Container can accommodate more items",
                'remaining_capacity' => [
                    'weight' => round($remainingWeight, 2),
                    'volume' => round($remainingVolume, 4),
                ],
            ];
        }

        // Suggest different container type if significantly underutilized
        if ($weightUtilization < 50 && $volumeUtilization < 50) {
            $suggestions[] = [
                'type' => 'downsize_container',
                'priority' => 'medium',
                'message' => "Consider using a smaller container type",
                'current_utilization' => [
                    'weight' => round($weightUtilization, 2),
                    'volume' => round($volumeUtilization, 2),
                ],
            ];
        }

        // Suggest splitting if significantly overutilized
        if ($weightUtilization > 95 || $volumeUtilization > 95) {
            $suggestions[] = [
                'type' => 'split_container',
                'priority' => 'high',
                'message' => "Container is at capacity - consider splitting into multiple containers",
                'current_utilization' => [
                    'weight' => round($weightUtilization, 2),
                    'volume' => round($volumeUtilization, 2),
                ],
            ];
        }

        // Check balance
        $balanceCheck = $this->validateBalance($container);
        if (!$balanceCheck['is_balanced']) {
            $suggestions[] = [
                'type' => 'rebalance',
                'priority' => 'medium',
                'message' => "Container weight distribution should be rebalanced",
                'details' => $balanceCheck['warnings'],
            ];
        }

        return $suggestions;
    }

    /**
     * Calculate container volume in m³
     */
    private function calculateContainerVolume(ContainerType $containerType): float
    {
        return ($containerType->length / 100) * 
               ($containerType->width / 100) * 
               ($containerType->height / 100);
    }
}
