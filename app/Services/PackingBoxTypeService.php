<?php

namespace App\Services;

use App\Models\PackingBoxType;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Packing Box Type Service
 * 
 * Provides intelligent suggestions for optimal packing box types
 * based on product dimensions, quantities, and cost-benefit analysis.
 */
class PackingBoxTypeService
{
    /**
     * Suggest optimal box types for a product
     * 
     * @param Product $product
     * @param int $quantity Quantity to pack
     * @return array Suggestions with box types and analysis
     */
    public function suggestBoxTypes(Product $product, int $quantity = 1): array
    {
        $productVolume = ($product->length * $product->width * $product->height) / 1000000; // CBM
        $productWeight = $product->weight ?? 0;
        
        $boxTypes = PackingBoxType::where('is_active', true)
            ->orderBy('internal_volume_cbm', 'asc')
            ->get();
        
        $suggestions = [];
        
        foreach ($boxTypes as $boxType) {
            $analysis = $this->analyzeBoxType($boxType, $product, $quantity);
            
            if ($analysis['can_fit']) {
                $suggestions[] = $analysis;
            }
        }
        
        // Sort by cost-benefit score (best first)
        usort($suggestions, function ($a, $b) {
            return $b['cost_benefit_score'] <=> $a['cost_benefit_score'];
        });
        
        return [
            'suggestions' => $suggestions,
            'product' => [
                'name' => $product->name,
                'dimensions' => "{$product->length}x{$product->width}x{$product->height} mm",
                'volume' => $productVolume,
                'weight' => $productWeight,
            ],
            'quantity' => $quantity,
            'recommended' => $suggestions[0] ?? null,
        ];
    }
    
    /**
     * Analyze a box type for a product
     * 
     * @param PackingBoxType $boxType
     * @param Product $product
     * @param int $quantity
     * @return array Analysis data
     */
    protected function analyzeBoxType(PackingBoxType $boxType, Product $product, int $quantity): array
    {
        // Calculate how many products fit in one box
        $unitsPerBox = $this->calculateUnitsPerBox($boxType, $product);
        
        // Calculate number of boxes needed
        $boxesNeeded = $unitsPerBox > 0 ? ceil($quantity / $unitsPerBox) : 0;
        
        // Calculate space utilization
        $productVolume = ($product->length * $product->width * $product->height) / 1000000;
        $totalProductVolume = $productVolume * $unitsPerBox;
        $spaceUtilization = $boxType->internal_volume_cbm > 0 
            ? ($totalProductVolume / $boxType->internal_volume_cbm) * 100 
            : 0;
        
        // Calculate weight utilization
        $productWeight = $product->weight ?? 0;
        $totalProductWeight = $productWeight * $unitsPerBox;
        $weightUtilization = $boxType->max_load_weight > 0 
            ? ($totalProductWeight / $boxType->max_load_weight) * 100 
            : 0;
        
        // Calculate total cost
        $totalCost = $boxType->unit_cost * $boxesNeeded;
        
        // Calculate cost per unit
        $costPerUnit = $unitsPerBox > 0 ? $boxType->unit_cost / $unitsPerBox : 0;
        
        // Calculate cost-benefit score (0-100)
        $utilizationScore = min(($spaceUtilization + $weightUtilization) / 2, 100);
        $costScore = 100 - min(($costPerUnit / max($costPerUnit, 1)) * 100, 100);
        $costBenefitScore = ($utilizationScore * 0.7) + ($costScore * 0.3);
        
        return [
            'box_type' => $boxType,
            'can_fit' => $unitsPerBox > 0,
            'units_per_box' => $unitsPerBox,
            'boxes_needed' => $boxesNeeded,
            'space_utilization' => round($spaceUtilization, 2),
            'weight_utilization' => round($weightUtilization, 2),
            'total_cost' => round($totalCost, 2),
            'cost_per_unit' => round($costPerUnit, 4),
            'cost_benefit_score' => round($costBenefitScore, 2),
            'efficiency_rating' => $this->getEfficiencyRating($costBenefitScore),
        ];
    }
    
    /**
     * Calculate how many product units fit in a box
     * Simple algorithm based on volume ratio
     * 
     * @param PackingBoxType $boxType
     * @param Product $product
     * @return int Number of units that fit
     */
    protected function calculateUnitsPerBox(PackingBoxType $boxType, Product $product): int
    {
        // Check if product dimensions fit in box
        $productDimensions = [$product->length, $product->width, $product->height];
        $boxDimensions = [
            $boxType->internal_length,
            $boxType->internal_width,
            $boxType->internal_height
        ];
        
        sort($productDimensions);
        sort($boxDimensions);
        
        // Check if product fits at all
        if ($productDimensions[0] > $boxDimensions[0] ||
            $productDimensions[1] > $boxDimensions[1] ||
            $productDimensions[2] > $boxDimensions[2]) {
            return 0;
        }
        
        // Simple volume-based calculation
        $productVolume = ($product->length * $product->width * $product->height) / 1000000;
        $boxVolume = $boxType->internal_volume_cbm;
        
        // Account for packing efficiency (typically 70-85%)
        $packingEfficiency = 0.75;
        $effectiveBoxVolume = $boxVolume * $packingEfficiency;
        
        $volumeBasedUnits = floor($effectiveBoxVolume / $productVolume);
        
        // Check weight constraint
        $productWeight = $product->weight ?? 0;
        $weightBasedUnits = $productWeight > 0 
            ? floor($boxType->max_load_weight / $productWeight) 
            : PHP_INT_MAX;
        
        return min($volumeBasedUnits, $weightBasedUnits);
    }
    
    /**
     * Get efficiency rating based on cost-benefit score
     * 
     * @param float $score
     * @return string Rating (Excellent, Good, Fair, Poor)
     */
    protected function getEfficiencyRating(float $score): string
    {
        if ($score >= 85) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 50) return 'Fair';
        return 'Poor';
    }
    
    /**
     * Perform cost-benefit analysis for multiple box types
     * 
     * @param Collection $boxTypes
     * @param Product $product
     * @param int $quantity
     * @return array Detailed analysis
     */
    public function costBenefitAnalysis(Collection $boxTypes, Product $product, int $quantity): array
    {
        $analyses = [];
        
        foreach ($boxTypes as $boxType) {
            $analysis = $this->analyzeBoxType($boxType, $product, $quantity);
            
            if ($analysis['can_fit']) {
                $analyses[] = $analysis;
            }
        }
        
        // Sort by cost-benefit score
        usort($analyses, function ($a, $b) {
            return $b['cost_benefit_score'] <=> $a['cost_benefit_score'];
        });
        
        // Calculate comparison metrics
        $bestCost = count($analyses) > 0 ? min(array_column($analyses, 'total_cost')) : 0;
        $bestUtilization = count($analyses) > 0 ? max(array_column($analyses, 'space_utilization')) : 0;
        
        foreach ($analyses as &$analysis) {
            $analysis['cost_vs_best'] = $bestCost > 0 
                ? (($analysis['total_cost'] - $bestCost) / $bestCost) * 100 
                : 0;
            $analysis['utilization_vs_best'] = $bestUtilization > 0 
                ? (($analysis['space_utilization'] - $bestUtilization) / $bestUtilization) * 100 
                : 0;
        }
        
        return [
            'analyses' => $analyses,
            'best_cost' => $bestCost,
            'best_utilization' => $bestUtilization,
            'total_options' => count($analyses),
        ];
    }
    
    /**
     * Get recommended box type for a product
     * 
     * @param Product $product
     * @param int $quantity
     * @return PackingBoxType|null
     */
    public function getRecommendedBoxType(Product $product, int $quantity = 1): ?PackingBoxType
    {
        $suggestions = $this->suggestBoxTypes($product, $quantity);
        
        return $suggestions['recommended']['box_type'] ?? null;
    }
}
