<?php

namespace App\Services;

use App\Models\ContainerType;
use App\Models\PackingBox;
use Illuminate\Support\Collection;

/**
 * Container Loading Service
 * 
 * Provides intelligent algorithms for loading packing boxes into shipping containers
 * with optimization for space utilization and weight distribution.
 */
class ContainerLoadingService
{
    /**
     * Load boxes into containers using Best-Fit algorithm
     * Prioritizes filling containers to maximum capacity before starting a new one
     * 
     * @param Collection $boxes Collection of PackingBox models
     * @param ContainerType|null $preferredType Preferred container type (optional)
     * @return array Array with 'containers' and 'unallocated' boxes
     */
    public function bestFitAlgorithm(Collection $boxes, ?ContainerType $preferredType = null): array
    {
        $availableTypes = $preferredType 
            ? collect([$preferredType]) 
            : ContainerType::where('is_active', true)->orderBy('capacity_cbm', 'asc')->get();
        
        $containers = [];
        $unallocated = [];
        
        // Sort boxes by volume (largest first) for better packing
        $sortedBoxes = $boxes->sortByDesc(function ($box) {
            return $box->length * $box->width * $box->height;
        });
        
        foreach ($sortedBoxes as $box) {
            $boxVolume = ($box->length * $box->width * $box->height) / 1000000; // Convert to CBM
            $boxWeight = $box->gross_weight;
            
            $allocated = false;
            
            // Try to fit in existing containers first
            foreach ($containers as &$container) {
                if ($this->canFitInContainer($box, $container)) {
                    $container['boxes'][] = $box;
                    $container['used_volume'] += $boxVolume;
                    $container['used_weight'] += $boxWeight;
                    $container['box_count']++;
                    $allocated = true;
                    break;
                }
            }
            
            // If not allocated, try to create a new container
            if (!$allocated) {
                $newContainer = $this->findSuitableContainer($box, $availableTypes);
                
                if ($newContainer) {
                    $containers[] = [
                        'type' => $newContainer,
                        'boxes' => [$box],
                        'used_volume' => $boxVolume,
                        'used_weight' => $boxWeight,
                        'box_count' => 1,
                        'utilization' => ($boxVolume / $newContainer->capacity_cbm) * 100,
                    ];
                } else {
                    $unallocated[] = $box;
                }
            }
        }
        
        // Calculate final utilization for each container
        foreach ($containers as &$container) {
            $container['utilization'] = ($container['used_volume'] / $container['type']->capacity_cbm) * 100;
            $container['weight_utilization'] = ($container['used_weight'] / $container['type']->max_gross_weight) * 100;
        }
        
        return [
            'containers' => $containers,
            'unallocated' => $unallocated,
            'total_containers' => count($containers),
            'total_boxes' => $boxes->count(),
            'allocated_boxes' => $boxes->count() - count($unallocated),
            'average_utilization' => count($containers) > 0 
                ? collect($containers)->avg('utilization') 
                : 0,
        ];
    }
    
    /**
     * Load boxes into containers with weight balance optimization
     * Distributes weight evenly across containers to prevent overloading
     * 
     * @param Collection $boxes Collection of PackingBox models
     * @param ContainerType|null $preferredType Preferred container type (optional)
     * @return array Array with 'containers' and 'unallocated' boxes
     */
    public function weightBalancedAlgorithm(Collection $boxes, ?ContainerType $preferredType = null): array
    {
        $availableTypes = $preferredType 
            ? collect([$preferredType]) 
            : ContainerType::where('is_active', true)->orderBy('max_gross_weight', 'desc')->get();
        
        $containers = [];
        $unallocated = [];
        
        // Sort boxes by weight (heaviest first)
        $sortedBoxes = $boxes->sortByDesc('gross_weight');
        
        foreach ($sortedBoxes as $box) {
            $boxVolume = ($box->length * $box->width * $box->height) / 1000000;
            $boxWeight = $box->gross_weight;
            
            $allocated = false;
            
            // Find container with lowest weight utilization that can fit the box
            $bestContainer = null;
            $lowestWeightUtil = 100;
            
            foreach ($containers as $index => &$container) {
                if ($this->canFitInContainer($box, $container)) {
                    $weightUtil = ($container['used_weight'] / $container['type']->max_gross_weight) * 100;
                    
                    if ($weightUtil < $lowestWeightUtil) {
                        $lowestWeightUtil = $weightUtil;
                        $bestContainer = $index;
                    }
                }
            }
            
            if ($bestContainer !== null) {
                $containers[$bestContainer]['boxes'][] = $box;
                $containers[$bestContainer]['used_volume'] += $boxVolume;
                $containers[$bestContainer]['used_weight'] += $boxWeight;
                $containers[$bestContainer]['box_count']++;
                $allocated = true;
            }
            
            // Create new container if needed
            if (!$allocated) {
                $newContainer = $this->findSuitableContainer($box, $availableTypes);
                
                if ($newContainer) {
                    $containers[] = [
                        'type' => $newContainer,
                        'boxes' => [$box],
                        'used_volume' => $boxVolume,
                        'used_weight' => $boxWeight,
                        'box_count' => 1,
                    ];
                } else {
                    $unallocated[] = $box;
                }
            }
        }
        
        // Calculate utilization metrics
        foreach ($containers as &$container) {
            $container['utilization'] = ($container['used_volume'] / $container['type']->capacity_cbm) * 100;
            $container['weight_utilization'] = ($container['used_weight'] / $container['type']->max_gross_weight) * 100;
            $container['balance_score'] = 100 - abs($container['utilization'] - $container['weight_utilization']);
        }
        
        return [
            'containers' => $containers,
            'unallocated' => $unallocated,
            'total_containers' => count($containers),
            'total_boxes' => $boxes->count(),
            'allocated_boxes' => $boxes->count() - count($unallocated),
            'average_balance' => count($containers) > 0 
                ? collect($containers)->avg('balance_score') 
                : 0,
        ];
    }
    
    /**
     * Suggest optimal container types for given boxes
     * 
     * @param Collection $boxes Collection of PackingBox models
     * @return array Suggestions with container types and estimated quantities
     */
    public function suggestContainers(Collection $boxes): array
    {
        $totalVolume = 0;
        $totalWeight = 0;
        
        foreach ($boxes as $box) {
            $totalVolume += ($box->length * $box->width * $box->height) / 1000000;
            $totalWeight += $box->gross_weight;
        }
        
        $containerTypes = ContainerType::where('is_active', true)
            ->orderBy('capacity_cbm', 'asc')
            ->get();
        
        $suggestions = [];
        
        foreach ($containerTypes as $type) {
            $volumeNeeded = ceil($totalVolume / $type->capacity_cbm);
            $weightNeeded = ceil($totalWeight / $type->max_gross_weight);
            $containersNeeded = max($volumeNeeded, $weightNeeded);
            
            $utilization = ($totalVolume / ($type->capacity_cbm * $containersNeeded)) * 100;
            $weightUtil = ($totalWeight / ($type->max_gross_weight * $containersNeeded)) * 100;
            
            $suggestions[] = [
                'container_type' => $type,
                'quantity' => $containersNeeded,
                'volume_utilization' => min($utilization, 100),
                'weight_utilization' => min($weightUtil, 100),
                'efficiency_score' => min(($utilization + $weightUtil) / 2, 100),
                'limiting_factor' => $volumeNeeded > $weightNeeded ? 'volume' : 'weight',
            ];
        }
        
        // Sort by efficiency score (best first)
        usort($suggestions, function ($a, $b) {
            return $b['efficiency_score'] <=> $a['efficiency_score'];
        });
        
        return [
            'suggestions' => $suggestions,
            'total_volume' => $totalVolume,
            'total_weight' => $totalWeight,
            'box_count' => $boxes->count(),
            'recommended' => $suggestions[0] ?? null,
        ];
    }
    
    /**
     * Check if a box can fit in a container
     * 
     * @param PackingBox $box
     * @param array $container Container data array
     * @return bool
     */
    protected function canFitInContainer(PackingBox $box, array $container): bool
    {
        $boxVolume = ($box->length * $box->width * $box->height) / 1000000;
        $boxWeight = $box->gross_weight;
        
        $remainingVolume = $container['type']->capacity_cbm - $container['used_volume'];
        $remainingWeight = $container['type']->max_gross_weight - $container['used_weight'];
        
        return $boxVolume <= $remainingVolume && $boxWeight <= $remainingWeight;
    }
    
    /**
     * Find suitable container type for a box
     * 
     * @param PackingBox $box
     * @param Collection $availableTypes
     * @return ContainerType|null
     */
    protected function findSuitableContainer(PackingBox $box, Collection $availableTypes): ?ContainerType
    {
        $boxVolume = ($box->length * $box->width * $box->height) / 1000000;
        $boxWeight = $box->gross_weight;
        
        foreach ($availableTypes as $type) {
            if ($boxVolume <= $type->capacity_cbm && $boxWeight <= $type->max_gross_weight) {
                return $type;
            }
        }
        
        return null;
    }
}
