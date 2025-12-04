<?php

namespace App\Services\Shipment;

use App\Models\PackingBoxType;
use App\Models\PackingBox;
use Illuminate\Support\Collection;

/**
 * Packing Box Type Service
 * 
 * Manages packing box types and provides intelligent box selection algorithms
 */
class PackingBoxTypeService
{
    /**
     * Suggest optimal box type for given dimensions and weight
     * 
     * Finds the smallest box that can fit the required dimensions and weight
     */
    public function suggestBoxType(float $length, float $width, float $height, float $weight): ?PackingBoxType
    {
        $requiredVolume = ($length * $width * $height) / 1000000; // Convert cm³ to m³

        return PackingBoxType::where('is_active', true)
            ->where('max_weight', '>=', $weight)
            ->where('max_volume', '>=', $requiredVolume)
            ->orderBy('max_volume', 'asc') // Smallest first
            ->first();
    }

    /**
     * Suggest box type for a collection of items
     * 
     * Analyzes total dimensions and weight to recommend appropriate box
     */
    public function suggestBoxTypeForItems(Collection $items): ?PackingBoxType
    {
        $totalWeight = $items->sum(fn($item) => $item->product->weight * $item->quantity);
        $totalVolume = $items->sum(fn($item) => $item->product->volume * $item->quantity);

        return PackingBoxType::where('is_active', true)
            ->where('max_weight', '>=', $totalWeight)
            ->where('max_volume', '>=', $totalVolume)
            ->orderBy('max_volume', 'asc')
            ->first();
    }

    /**
     * Get all active box types sorted by volume
     */
    public function getActiveBoxTypes(): Collection
    {
        return PackingBoxType::where('is_active', true)
            ->orderBy('max_volume', 'asc')
            ->get();
    }

    /**
     * Calculate packing efficiency for a box
     * 
     * Returns percentage of space utilization
     */
    public function calculatePackingEfficiency(PackingBox $box): array
    {
        $boxType = $box->boxType;
        
        if (!$boxType) {
            return [
                'weight_utilization' => 0,
                'volume_utilization' => 0,
                'efficiency_score' => 0,
            ];
        }

        $weightUtilization = ($box->gross_weight / $boxType->max_weight) * 100;
        $volumeUtilization = ($box->volume / $boxType->max_volume) * 100;
        $efficiencyScore = ($weightUtilization + $volumeUtilization) / 2;

        return [
            'weight_utilization' => round($weightUtilization, 2),
            'volume_utilization' => round($volumeUtilization, 2),
            'efficiency_score' => round($efficiencyScore, 2),
            'is_optimal' => $efficiencyScore >= 70 && $efficiencyScore <= 95,
            'is_overloaded' => $weightUtilization > 100 || $volumeUtilization > 100,
        ];
    }

    /**
     * Get box type statistics
     * 
     * Returns usage statistics for a specific box type
     */
    public function getBoxTypeStatistics(PackingBoxType $boxType): array
    {
        $boxes = $boxType->packingBoxes()
            ->with('packingBoxItems')
            ->get();

        if ($boxes->isEmpty()) {
            return [
                'total_boxes' => 0,
                'avg_weight_utilization' => 0,
                'avg_volume_utilization' => 0,
                'total_cost' => 0,
            ];
        }

        $totalWeightUtilization = 0;
        $totalVolumeUtilization = 0;

        foreach ($boxes as $box) {
            $efficiency = $this->calculatePackingEfficiency($box);
            $totalWeightUtilization += $efficiency['weight_utilization'];
            $totalVolumeUtilization += $efficiency['volume_utilization'];
        }

        $boxCount = $boxes->count();

        return [
            'total_boxes' => $boxCount,
            'avg_weight_utilization' => round($totalWeightUtilization / $boxCount, 2),
            'avg_volume_utilization' => round($totalVolumeUtilization / $boxCount, 2),
            'total_cost' => $boxCount * ($boxType->unit_cost ?? 0),
        ];
    }

    /**
     * Compare box types for cost-effectiveness
     * 
     * Analyzes which box type is most cost-effective for given requirements
     */
    public function compareBoxTypes(float $totalWeight, float $totalVolume): array
    {
        $boxTypes = $this->getActiveBoxTypes();
        $comparisons = [];

        foreach ($boxTypes as $boxType) {
            // Calculate how many boxes needed
            $boxesByWeight = ceil($totalWeight / $boxType->max_weight);
            $boxesByVolume = ceil($totalVolume / $boxType->max_volume);
            $boxesNeeded = max($boxesByWeight, $boxesByVolume);

            // Calculate utilization
            $weightUtilization = ($totalWeight / ($boxesNeeded * $boxType->max_weight)) * 100;
            $volumeUtilization = ($totalVolume / ($boxesNeeded * $boxType->max_volume)) * 100;
            $avgUtilization = ($weightUtilization + $volumeUtilization) / 2;

            // Calculate cost
            $totalCost = $boxesNeeded * ($boxType->unit_cost ?? 0);
            $costPerCubicMeter = $totalCost > 0 ? $totalCost / $totalVolume : 0;

            $comparisons[] = [
                'box_type' => $boxType->code,
                'box_type_name' => $boxType->name,
                'boxes_needed' => $boxesNeeded,
                'weight_utilization' => round($weightUtilization, 2),
                'volume_utilization' => round($volumeUtilization, 2),
                'avg_utilization' => round($avgUtilization, 2),
                'total_cost' => round($totalCost, 2),
                'cost_per_m3' => round($costPerCubicMeter, 2),
                'is_recommended' => $avgUtilization >= 70 && $avgUtilization <= 95,
            ];
        }

        // Sort by cost-effectiveness (highest utilization, lowest cost)
        usort($comparisons, function($a, $b) {
            if ($a['avg_utilization'] == $b['avg_utilization']) {
                return $a['total_cost'] <=> $b['total_cost'];
            }
            return $b['avg_utilization'] <=> $a['avg_utilization'];
        });

        return $comparisons;
    }

    /**
     * Validate box type selection
     * 
     * Checks if selected box type is appropriate for the contents
     */
    public function validateBoxTypeSelection(PackingBoxType $boxType, float $contentWeight, float $contentVolume): array
    {
        $errors = [];
        $warnings = [];

        // Check weight capacity
        if ($contentWeight > $boxType->max_weight) {
            $errors[] = "Content weight ({$contentWeight}kg) exceeds box capacity ({$boxType->max_weight}kg)";
        } elseif ($contentWeight > $boxType->max_weight * 0.95) {
            $warnings[] = "Content weight is very close to maximum capacity (>95%)";
        }

        // Check volume capacity
        if ($contentVolume > $boxType->max_volume) {
            $errors[] = "Content volume ({$contentVolume}m³) exceeds box capacity ({$boxType->max_volume}m³)";
        } elseif ($contentVolume > $boxType->max_volume * 0.95) {
            $warnings[] = "Content volume is very close to maximum capacity (>95%)";
        }

        // Check utilization
        $weightUtilization = ($contentWeight / $boxType->max_weight) * 100;
        $volumeUtilization = ($contentVolume / $boxType->max_volume) * 100;

        if ($weightUtilization < 50 || $volumeUtilization < 50) {
            $warnings[] = "Box utilization is low (<50%). Consider using a smaller box type.";
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'weight_utilization' => round($weightUtilization, 2),
            'volume_utilization' => round($volumeUtilization, 2),
        ];
    }
}
