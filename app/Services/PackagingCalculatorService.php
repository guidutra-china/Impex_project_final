<?php

namespace App\Services;

use App\Models\PackingBox;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Packaging Calculator Service
 * 
 * Provides comprehensive calculations for packaging operations including
 * CBM calculations, optimal packing strategies, and freight class determination.
 */
class PackagingCalculatorService
{
    /**
     * Calculate CBM (Cubic Meter) for a packing box
     * 
     * @param float $length Length in mm
     * @param float $width Width in mm
     * @param float $height Height in mm
     * @return float CBM value
     */
    public function calculateCBM(float $length, float $width, float $height): float
    {
        // Convert mm to meters and calculate volume
        return ($length * $width * $height) / 1000000000;
    }
    
    /**
     * Calculate total CBM for multiple boxes
     * 
     * @param Collection $boxes Collection of PackingBox models
     * @return array Detailed CBM calculation
     */
    public function calculateTotalCBM(Collection $boxes): array
    {
        $totalCBM = 0;
        $totalWeight = 0;
        $boxDetails = [];
        
        foreach ($boxes as $box) {
            $cbm = $this->calculateCBM($box->length, $box->width, $box->height);
            $totalCBM += $cbm;
            $totalWeight += $box->gross_weight;
            
            $boxDetails[] = [
                'box_id' => $box->id,
                'box_number' => $box->box_number,
                'dimensions' => "{$box->length}x{$box->width}x{$box->height} mm",
                'cbm' => round($cbm, 4),
                'weight' => $box->gross_weight,
                'volumetric_weight' => $this->calculateVolumetricWeight($cbm),
            ];
        }
        
        return [
            'total_cbm' => round($totalCBM, 4),
            'total_weight' => round($totalWeight, 2),
            'total_boxes' => $boxes->count(),
            'average_cbm_per_box' => $boxes->count() > 0 ? round($totalCBM / $boxes->count(), 4) : 0,
            'volumetric_weight' => $this->calculateVolumetricWeight($totalCBM),
            'chargeable_weight' => $this->calculateChargeableWeight($totalWeight, $totalCBM),
            'boxes' => $boxDetails,
        ];
    }
    
    /**
     * Calculate volumetric weight (for air freight)
     * Standard: 1 CBM = 167 kg (6000 cubic cm per kg)
     * 
     * @param float $cbm Cubic meters
     * @return float Volumetric weight in kg
     */
    public function calculateVolumetricWeight(float $cbm): float
    {
        return round($cbm * 167, 2);
    }
    
    /**
     * Calculate chargeable weight (higher of actual or volumetric)
     * 
     * @param float $actualWeight Actual weight in kg
     * @param float $cbm Cubic meters
     * @return float Chargeable weight in kg
     */
    public function calculateChargeableWeight(float $actualWeight, float $cbm): float
    {
        $volumetricWeight = $this->calculateVolumetricWeight($cbm);
        return max($actualWeight, $volumetricWeight);
    }
    
    /**
     * Suggest optimal packing strategy for products
     * 
     * @param Collection $products Collection of products with quantities
     * @param array $availableBoxSizes Available box dimensions
     * @return array Packing strategy recommendations
     */
    public function suggestOptimalPacking(Collection $products, array $availableBoxSizes = []): array
    {
        if (empty($availableBoxSizes)) {
            $availableBoxSizes = $this->getStandardBoxSizes();
        }
        
        $strategies = [];
        
        // Strategy 1: Group by size
        $strategies['group_by_size'] = $this->packBySize($products, $availableBoxSizes);
        
        // Strategy 2: Mixed packing (maximize space)
        $strategies['mixed_packing'] = $this->packMixed($products, $availableBoxSizes);
        
        // Strategy 3: Weight-balanced
        $strategies['weight_balanced'] = $this->packByWeight($products, $availableBoxSizes);
        
        // Evaluate strategies
        $bestStrategy = $this->evaluateStrategies($strategies);
        
        return [
            'strategies' => $strategies,
            'recommended' => $bestStrategy,
            'total_products' => $products->sum('quantity'),
            'total_weight' => $products->sum(function ($p) {
                return ($p->weight ?? 0) * $p->quantity;
            }),
        ];
    }
    
    /**
     * Pack products by size (similar sizes together)
     * 
     * @param Collection $products
     * @param array $boxSizes
     * @return array Packing plan
     */
    protected function packBySize(Collection $products, array $boxSizes): array
    {
        $boxes = [];
        $totalCost = 0;
        
        foreach ($products as $product) {
            $bestBox = $this->findBestFitBox($product, $boxSizes);
            
            if ($bestBox) {
                $unitsPerBox = $this->calculateUnitsPerBox($product, $bestBox);
                $boxesNeeded = ceil($product->quantity / $unitsPerBox);
                
                $boxes[] = [
                    'product' => $product->name,
                    'box_size' => $bestBox['name'],
                    'units_per_box' => $unitsPerBox,
                    'boxes_needed' => $boxesNeeded,
                    'utilization' => $this->calculateBoxUtilization($product, $bestBox, $unitsPerBox),
                ];
                
                $totalCost += $boxesNeeded * ($bestBox['cost'] ?? 0);
            }
        }
        
        return [
            'name' => 'Group by Size',
            'description' => 'Pack similar-sized products together',
            'boxes' => $boxes,
            'total_boxes' => array_sum(array_column($boxes, 'boxes_needed')),
            'estimated_cost' => $totalCost,
            'pros' => ['Easy to organize', 'Consistent packing', 'Good for inventory'],
            'cons' => ['May not maximize space', 'More boxes needed'],
        ];
    }
    
    /**
     * Pack products mixed (maximize space utilization)
     * 
     * @param Collection $products
     * @param array $boxSizes
     * @return array Packing plan
     */
    protected function packMixed(Collection $products, array $boxSizes): array
    {
        // Simplified mixed packing algorithm
        $boxes = [];
        $totalCost = 0;
        
        // Use largest box size for mixed packing
        $largestBox = collect($boxSizes)->sortByDesc('volume')->first();
        
        $totalVolume = $products->sum(function ($p) {
            return (($p->length * $p->width * $p->height) / 1000000000) * $p->quantity;
        });
        
        $boxVolume = $largestBox['volume'] ?? 1;
        $boxesNeeded = ceil($totalVolume / ($boxVolume * 0.8)); // 80% packing efficiency
        
        return [
            'name' => 'Mixed Packing',
            'description' => 'Mix different products to maximize space',
            'boxes' => [[
                'box_size' => $largestBox['name'] ?? 'Large',
                'boxes_needed' => $boxesNeeded,
                'mixed_products' => $products->count(),
                'utilization' => 80,
            ]],
            'total_boxes' => $boxesNeeded,
            'estimated_cost' => $boxesNeeded * ($largestBox['cost'] ?? 0),
            'pros' => ['Fewer boxes', 'Lower cost', 'Maximum space utilization'],
            'cons' => ['Complex packing', 'Harder to organize', 'Unpacking complexity'],
        ];
    }
    
    /**
     * Pack products by weight (balanced distribution)
     * 
     * @param Collection $products
     * @param array $boxSizes
     * @return array Packing plan
     */
    protected function packByWeight(Collection $products, array $boxSizes): array
    {
        $boxes = [];
        $totalWeight = $products->sum(function ($p) {
            return ($p->weight ?? 0) * $p->quantity;
        });
        
        $mediumBox = collect($boxSizes)->sortBy('volume')->skip(1)->first() ?? $boxSizes[0];
        $maxWeightPerBox = $mediumBox['max_weight'] ?? 20;
        
        $boxesNeeded = ceil($totalWeight / $maxWeightPerBox);
        
        return [
            'name' => 'Weight Balanced',
            'description' => 'Distribute weight evenly across boxes',
            'boxes' => [[
                'box_size' => $mediumBox['name'] ?? 'Medium',
                'boxes_needed' => $boxesNeeded,
                'avg_weight_per_box' => round($totalWeight / $boxesNeeded, 2),
                'utilization' => round(($totalWeight / ($boxesNeeded * $maxWeightPerBox)) * 100, 2),
            ]],
            'total_boxes' => $boxesNeeded,
            'estimated_cost' => $boxesNeeded * ($mediumBox['cost'] ?? 0),
            'pros' => ['Balanced weight', 'Easy handling', 'Safe transport'],
            'cons' => ['May not maximize space', 'More boxes than mixed'],
        ];
    }
    
    /**
     * Determine freight class based on density
     * (US NMFC freight classification)
     * 
     * @param float $weight Weight in lbs
     * @param float $cbm Volume in cubic meters
     * @return array Freight class information
     */
    public function determineFreightClass(float $weight, float $cbm): array
    {
        // Convert CBM to cubic feet
        $cubicFeet = $cbm * 35.3147;
        
        // Calculate density (lbs per cubic foot)
        $density = $cubicFeet > 0 ? $weight / $cubicFeet : 0;
        
        // Determine freight class based on density
        $freightClass = $this->getFreightClassByDensity($density);
        
        return [
            'density' => round($density, 2),
            'density_unit' => 'lbs/cu ft',
            'freight_class' => $freightClass['class'],
            'class_name' => $freightClass['name'],
            'description' => $freightClass['description'],
        ];
    }
    
    /**
     * Get freight class by density
     * 
     * @param float $density Density in lbs/cu ft
     * @return array Class information
     */
    protected function getFreightClassByDensity(float $density): array
    {
        if ($density < 1) return ['class' => 500, 'name' => 'Low Density', 'description' => 'Very light items'];
        if ($density < 2) return ['class' => 400, 'name' => 'Low Density', 'description' => 'Light items'];
        if ($density < 4) return ['class' => 300, 'name' => 'Low Density', 'description' => 'Light items'];
        if ($density < 6) return ['class' => 250, 'name' => 'Low-Medium Density', 'description' => 'Light to medium items'];
        if ($density < 8) return ['class' => 175, 'name' => 'Medium Density', 'description' => 'Medium weight items'];
        if ($density < 10) return ['class' => 125, 'name' => 'Medium Density', 'description' => 'Medium weight items'];
        if ($density < 12) return ['class' => 100, 'name' => 'Medium-High Density', 'description' => 'Medium to heavy items'];
        if ($density < 15) return ['class' => 85, 'name' => 'High Density', 'description' => 'Heavy items'];
        if ($density < 22.5) return ['class' => 70, 'name' => 'High Density', 'description' => 'Heavy items'];
        if ($density < 30) return ['class' => 65, 'name' => 'Very High Density', 'description' => 'Very heavy items'];
        return ['class' => 60, 'name' => 'Very High Density', 'description' => 'Extremely heavy items'];
    }
    
    /**
     * Get standard box sizes
     * 
     * @return array Standard box dimensions and properties
     */
    protected function getStandardBoxSizes(): array
    {
        return [
            ['name' => 'Small', 'length' => 300, 'width' => 200, 'height' => 200, 'volume' => 0.012, 'max_weight' => 10, 'cost' => 2],
            ['name' => 'Medium', 'length' => 400, 'width' => 300, 'height' => 300, 'volume' => 0.036, 'max_weight' => 20, 'cost' => 3],
            ['name' => 'Large', 'length' => 600, 'width' => 400, 'height' => 400, 'volume' => 0.096, 'max_weight' => 30, 'cost' => 5],
        ];
    }
    
    /**
     * Find best fit box for a product
     * 
     * @param mixed $product
     * @param array $boxSizes
     * @return array|null Best fit box
     */
    protected function findBestFitBox($product, array $boxSizes): ?array
    {
        foreach ($boxSizes as $box) {
            if ($product->length <= $box['length'] &&
                $product->width <= $box['width'] &&
                $product->height <= $box['height']) {
                return $box;
            }
        }
        return null;
    }
    
    /**
     * Calculate units per box
     * 
     * @param mixed $product
     * @param array $box
     * @return int Units that fit
     */
    protected function calculateUnitsPerBox($product, array $box): int
    {
        $productVolume = ($product->length * $product->width * $product->height) / 1000000000;
        $boxVolume = $box['volume'];
        
        return max(1, floor(($boxVolume * 0.75) / $productVolume));
    }
    
    /**
     * Calculate box utilization
     * 
     * @param mixed $product
     * @param array $box
     * @param int $units
     * @return float Utilization percentage
     */
    protected function calculateBoxUtilization($product, array $box, int $units): float
    {
        $productVolume = ($product->length * $product->width * $product->height) / 1000000000;
        $usedVolume = $productVolume * $units;
        
        return round(($usedVolume / $box['volume']) * 100, 2);
    }
    
    /**
     * Evaluate packing strategies
     * 
     * @param array $strategies
     * @return string Best strategy name
     */
    protected function evaluateStrategies(array $strategies): string
    {
        $scores = [];
        
        foreach ($strategies as $name => $strategy) {
            // Score based on: fewer boxes (40%), lower cost (40%), higher utilization (20%)
            $boxScore = 100 - min(($strategy['total_boxes'] / 10) * 100, 100);
            $costScore = 100 - min(($strategy['estimated_cost'] / 100) * 100, 100);
            $utilScore = 70; // Default utilization score
            
            $scores[$name] = ($boxScore * 0.4) + ($costScore * 0.4) + ($utilScore * 0.2);
        }
        
        arsort($scores);
        return array_key_first($scores);
    }
}
