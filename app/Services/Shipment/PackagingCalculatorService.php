<?php

namespace App\Services\Shipment;

use App\Models\Product;
use App\Models\PackingBoxType;
use App\Models\ContainerType;

class PackagingCalculatorService
{
    /**
     * Calculate CBM (Cubic Meter) from dimensions
     * 
     * @param float $length Length in cm
     * @param float $width Width in cm
     * @param float $height Height in cm
     * @return float CBM in m³
     */
    public function calculateCBM(float $length, float $width, float $height): float
    {
        // Convert cm to m and calculate volume
        return ($length / 100) * ($width / 100) * ($height / 100);
    }

    /**
     * Calculate total weight including packaging
     * 
     * @param float $productWeight Product weight in kg
     * @param int $quantity Quantity of products
     * @param float $packagingWeight Weight of packaging per unit in kg
     * @return float Total weight in kg
     */
    public function calculateTotalWeight(
        float $productWeight,
        int $quantity,
        float $packagingWeight = 0
    ): float {
        return ($productWeight * $quantity) + ($packagingWeight * $quantity);
    }

    /**
     * Calculate total volume including packaging
     * 
     * @param float $productVolume Product volume in m³
     * @param int $quantity Quantity of products
     * @param float $packagingVolume Volume of packaging per unit in m³
     * @return float Total volume in m³
     */
    public function calculateTotalVolume(
        float $productVolume,
        int $quantity,
        float $packagingVolume = 0
    ): float {
        return ($productVolume * $quantity) + ($packagingVolume * $quantity);
    }

    /**
     * Calculate how many items fit in a box
     * 
     * @param Product $product
     * @param PackingBoxType $boxType
     * @return array ['max_quantity' => int, 'utilization' => float]
     */
    public function calculateBoxCapacity(Product $product, PackingBoxType $boxType): array
    {
        // Calculate how many items fit by dimensions
        $lengthFit = floor($boxType->length / $product->length);
        $widthFit = floor($boxType->width / $product->width);
        $heightFit = floor($boxType->height / $product->height);

        $maxByDimensions = $lengthFit * $widthFit * $heightFit;

        // Calculate how many items fit by weight
        $maxByWeight = floor($boxType->max_weight / $product->weight);

        // The limiting factor is the minimum
        $maxQuantity = min($maxByDimensions, $maxByWeight);

        // Calculate utilization
        $actualVolume = $product->volume * $maxQuantity;
        $boxVolume = $this->calculateCBM($boxType->length, $boxType->width, $boxType->height);
        $volumeUtilization = ($actualVolume / $boxVolume) * 100;

        $actualWeight = $product->weight * $maxQuantity;
        $weightUtilization = ($actualWeight / $boxType->max_weight) * 100;

        return [
            'max_quantity' => $maxQuantity,
            'volume_utilization' => round($volumeUtilization, 2),
            'weight_utilization' => round($weightUtilization, 2),
            'overall_utilization' => round(($volumeUtilization + $weightUtilization) / 2, 2),
        ];
    }

    /**
     * Calculate how many boxes needed for a quantity
     * 
     * @param Product $product
     * @param PackingBoxType $boxType
     * @param int $quantity
     * @return array ['boxes_needed' => int, 'items_per_box' => int, 'last_box_quantity' => int]
     */
    public function calculateBoxesNeeded(
        Product $product,
        PackingBoxType $boxType,
        int $quantity
    ): array {
        $capacity = $this->calculateBoxCapacity($product, $boxType);
        $itemsPerBox = $capacity['max_quantity'];

        if ($itemsPerBox === 0) {
            return [
                'boxes_needed' => 0,
                'items_per_box' => 0,
                'last_box_quantity' => 0,
                'error' => 'Product does not fit in this box type',
            ];
        }

        $boxesNeeded = ceil($quantity / $itemsPerBox);
        $lastBoxQuantity = $quantity % $itemsPerBox;
        
        if ($lastBoxQuantity === 0) {
            $lastBoxQuantity = $itemsPerBox;
        }

        return [
            'boxes_needed' => $boxesNeeded,
            'items_per_box' => $itemsPerBox,
            'last_box_quantity' => $lastBoxQuantity,
            'total_weight' => $boxesNeeded * $boxType->max_weight,
            'total_volume' => $boxesNeeded * $this->calculateCBM(
                $boxType->length,
                $boxType->width,
                $boxType->height
            ),
        ];
    }

    /**
     * Calculate container capacity for boxes
     * 
     * @param PackingBoxType $boxType
     * @param ContainerType $containerType
     * @return array ['max_boxes' => int, 'utilization' => float]
     */
    public function calculateContainerCapacity(
        PackingBoxType $boxType,
        ContainerType $containerType
    ): array {
        // Calculate how many boxes fit by dimensions
        $lengthFit = floor($containerType->length / $boxType->length);
        $widthFit = floor($containerType->width / $boxType->width);
        $heightFit = floor($containerType->height / $boxType->height);

        $maxByDimensions = $lengthFit * $widthFit * $heightFit;

        // Calculate how many boxes fit by weight
        $boxWeight = $boxType->max_weight;
        $maxByWeight = floor($containerType->max_weight / $boxWeight);

        // Calculate how many boxes fit by volume
        $boxVolume = $this->calculateCBM($boxType->length, $boxType->width, $boxType->height);
        $containerVolume = $this->calculateCBM(
            $containerType->length,
            $containerType->width,
            $containerType->height
        );
        $maxByVolume = floor($containerVolume / $boxVolume);

        // The limiting factor is the minimum
        $maxBoxes = min($maxByDimensions, $maxByWeight, $maxByVolume);

        // Calculate utilization
        $actualVolume = $boxVolume * $maxBoxes;
        $volumeUtilization = ($actualVolume / $containerVolume) * 100;

        $actualWeight = $boxWeight * $maxBoxes;
        $weightUtilization = ($actualWeight / $containerType->max_weight) * 100;

        return [
            'max_boxes' => $maxBoxes,
            'volume_utilization' => round($volumeUtilization, 2),
            'weight_utilization' => round($weightUtilization, 2),
            'overall_utilization' => round(($volumeUtilization + $weightUtilization) / 2, 2),
            'limiting_factor' => $this->getLimitingFactor($maxByDimensions, $maxByWeight, $maxByVolume),
        ];
    }

    /**
     * Calculate optimal packing strategy
     * 
     * @param Product $product
     * @param int $quantity
     * @param array $availableBoxTypes Array of PackingBoxType
     * @return array Best packing strategy
     */
    public function calculateOptimalPacking(
        Product $product,
        int $quantity,
        array $availableBoxTypes
    ): array {
        $strategies = [];

        foreach ($availableBoxTypes as $boxType) {
            $boxesNeeded = $this->calculateBoxesNeeded($product, $boxType, $quantity);
            
            if (isset($boxesNeeded['error'])) {
                continue;
            }

            $capacity = $this->calculateBoxCapacity($product, $boxType);

            $strategies[] = [
                'box_type' => $boxType,
                'boxes_needed' => $boxesNeeded['boxes_needed'],
                'items_per_box' => $boxesNeeded['items_per_box'],
                'total_weight' => $boxesNeeded['total_weight'],
                'total_volume' => $boxesNeeded['total_volume'],
                'utilization' => $capacity['overall_utilization'],
                'cost_estimate' => $boxesNeeded['boxes_needed'] * ($boxType->cost ?? 0),
            ];
        }

        // Sort by utilization (best first)
        usort($strategies, fn($a, $b) => $b['utilization'] <=> $a['utilization']);

        return [
            'recommended' => $strategies[0] ?? null,
            'alternatives' => array_slice($strategies, 1, 3),
            'all_options' => $strategies,
        ];
    }

    /**
     * Get the limiting factor for capacity calculation
     */
    private function getLimitingFactor(int $byDimensions, int $byWeight, int $byVolume): string
    {
        $min = min($byDimensions, $byWeight, $byVolume);

        if ($min === $byDimensions) {
            return 'dimensions';
        } elseif ($min === $byWeight) {
            return 'weight';
        } else {
            return 'volume';
        }
    }

    /**
     * Calculate freight class based on density
     * 
     * @param float $weight Weight in kg
     * @param float $volume Volume in m³
     * @return array ['density' => float, 'class' => string]
     */
    public function calculateFreightClass(float $weight, float $volume): array
    {
        if ($volume == 0) {
            return [
                'density' => 0,
                'class' => 'unknown',
                'description' => 'Invalid volume',
            ];
        }

        // Calculate density (kg/m³)
        $density = $weight / $volume;

        // Freight class based on density
        // These are approximate ranges
        if ($density < 50) {
            $class = 'Class 500';
            $description = 'Very low density';
        } elseif ($density < 100) {
            $class = 'Class 400';
            $description = 'Low density';
        } elseif ($density < 200) {
            $class = 'Class 300';
            $description = 'Medium-low density';
        } elseif ($density < 300) {
            $class = 'Class 200';
            $description = 'Medium density';
        } elseif ($density < 400) {
            $class = 'Class 150';
            $description = 'Medium-high density';
        } elseif ($density < 600) {
            $class = 'Class 100';
            $description = 'High density';
        } else {
            $class = 'Class 50';
            $description = 'Very high density';
        }

        return [
            'density' => round($density, 2),
            'class' => $class,
            'description' => $description,
        ];
    }
}
