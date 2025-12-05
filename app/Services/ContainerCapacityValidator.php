<?php

namespace App\Services;

use App\Models\ShipmentContainer;
use App\Models\PackingBox;
use Illuminate\Support\Collection;

/**
 * Container Capacity Validator
 * 
 * Provides comprehensive validation for container capacity, balance,
 * and optimization suggestions for shipping containers.
 */
class ContainerCapacityValidator
{
    /**
     * Validation severity levels
     */
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    
    /**
     * Validate container capacity
     * 
     * @param ShipmentContainer $container
     * @param Collection $boxes Packing boxes in this container
     * @return array Validation result
     */
    public function validateCapacity(ShipmentContainer $container, Collection $boxes): array
    {
        $containerType = $container->containerType;
        
        if (!$containerType) {
            return [
                'valid' => false,
                'severity' => self::SEVERITY_ERROR,
                'message' => 'Container type not found',
                'issues' => [],
            ];
        }
        
        $totalVolume = 0;
        $totalWeight = 0;
        
        foreach ($boxes as $box) {
            $totalVolume += ($box->length * $box->width * $box->height) / 1000000; // Convert to CBM
            $totalWeight += $box->gross_weight;
        }
        
        $issues = [];
        
        // Volume validation
        $volumeUtilization = ($totalVolume / $containerType->capacity_cbm) * 100;
        
        if ($totalVolume > $containerType->capacity_cbm) {
            $issues[] = [
                'type' => 'volume_exceeded',
                'severity' => self::SEVERITY_ERROR,
                'message' => sprintf(
                    'Volume exceeds container capacity by %.2f CBM (%.1f%%)',
                    $totalVolume - $containerType->capacity_cbm,
                    $volumeUtilization - 100
                ),
                'current' => $totalVolume,
                'limit' => $containerType->capacity_cbm,
                'utilization' => $volumeUtilization,
            ];
        } elseif ($volumeUtilization > 95) {
            $issues[] = [
                'type' => 'volume_near_limit',
                'severity' => self::SEVERITY_WARNING,
                'message' => sprintf('Volume utilization is very high (%.1f%%)', $volumeUtilization),
                'current' => $totalVolume,
                'limit' => $containerType->capacity_cbm,
                'utilization' => $volumeUtilization,
            ];
        } elseif ($volumeUtilization < 60) {
            $issues[] = [
                'type' => 'volume_underutilized',
                'severity' => self::SEVERITY_INFO,
                'message' => sprintf('Volume utilization is low (%.1f%%). Consider using a smaller container.', $volumeUtilization),
                'current' => $totalVolume,
                'limit' => $containerType->capacity_cbm,
                'utilization' => $volumeUtilization,
            ];
        }
        
        // Weight validation
        $weightUtilization = ($totalWeight / $containerType->max_gross_weight) * 100;
        
        if ($totalWeight > $containerType->max_gross_weight) {
            $issues[] = [
                'type' => 'weight_exceeded',
                'severity' => self::SEVERITY_ERROR,
                'message' => sprintf(
                    'Weight exceeds container limit by %.2f kg (%.1f%%)',
                    $totalWeight - $containerType->max_gross_weight,
                    $weightUtilization - 100
                ),
                'current' => $totalWeight,
                'limit' => $containerType->max_gross_weight,
                'utilization' => $weightUtilization,
            ];
        } elseif ($weightUtilization > 95) {
            $issues[] = [
                'type' => 'weight_near_limit',
                'severity' => self::SEVERITY_WARNING,
                'message' => sprintf('Weight utilization is very high (%.1f%%)', $weightUtilization),
                'current' => $totalWeight,
                'limit' => $containerType->max_gross_weight,
                'utilization' => $weightUtilization,
            ];
        }
        
        $valid = !collect($issues)->contains('severity', self::SEVERITY_ERROR);
        $highestSeverity = $this->getHighestSeverity($issues);
        
        return [
            'valid' => $valid,
            'severity' => $highestSeverity,
            'message' => $valid ? 'Container capacity is within limits' : 'Container capacity validation failed',
            'volume' => [
                'current' => round($totalVolume, 4),
                'limit' => $containerType->capacity_cbm,
                'utilization' => round($volumeUtilization, 2),
                'available' => round($containerType->capacity_cbm - $totalVolume, 4),
            ],
            'weight' => [
                'current' => round($totalWeight, 2),
                'limit' => $containerType->max_gross_weight,
                'utilization' => round($weightUtilization, 2),
                'available' => round($containerType->max_gross_weight - $totalWeight, 2),
            ],
            'box_count' => $boxes->count(),
            'issues' => $issues,
        ];
    }
    
    /**
     * Validate container balance (weight distribution)
     * 
     * @param ShipmentContainer $container
     * @param Collection $boxes
     * @return array Balance validation result
     */
    public function validateBalance(ShipmentContainer $container, Collection $boxes): array
    {
        if ($boxes->isEmpty()) {
            return [
                'balanced' => true,
                'severity' => self::SEVERITY_INFO,
                'message' => 'No boxes to validate',
                'balance_score' => 100,
            ];
        }
        
        $totalWeight = $boxes->sum('gross_weight');
        $avgWeight = $totalWeight / $boxes->count();
        
        // Calculate weight distribution
        $weightVariance = 0;
        foreach ($boxes as $box) {
            $weightVariance += pow($box->gross_weight - $avgWeight, 2);
        }
        $weightStdDev = sqrt($weightVariance / $boxes->count());
        
        // Calculate coefficient of variation (CV)
        $cv = $avgWeight > 0 ? ($weightStdDev / $avgWeight) * 100 : 0;
        
        // Balance score (0-100, higher is better)
        $balanceScore = max(0, 100 - $cv);
        
        $issues = [];
        $balanced = true;
        $severity = self::SEVERITY_INFO;
        
        if ($cv > 50) {
            $balanced = false;
            $severity = self::SEVERITY_WARNING;
            $issues[] = [
                'type' => 'high_weight_variation',
                'severity' => self::SEVERITY_WARNING,
                'message' => 'High weight variation detected. Consider redistributing boxes.',
                'coefficient_variation' => round($cv, 2),
            ];
        } elseif ($cv > 30) {
            $severity = self::SEVERITY_INFO;
            $issues[] = [
                'type' => 'moderate_weight_variation',
                'severity' => self::SEVERITY_INFO,
                'message' => 'Moderate weight variation. Balance is acceptable.',
                'coefficient_variation' => round($cv, 2),
            ];
        }
        
        return [
            'balanced' => $balanced,
            'severity' => $severity,
            'message' => $balanced ? 'Container is well balanced' : 'Container balance needs attention',
            'balance_score' => round($balanceScore, 2),
            'statistics' => [
                'total_weight' => round($totalWeight, 2),
                'average_weight' => round($avgWeight, 2),
                'std_deviation' => round($weightStdDev, 2),
                'coefficient_variation' => round($cv, 2),
                'min_weight' => round($boxes->min('gross_weight'), 2),
                'max_weight' => round($boxes->max('gross_weight'), 2),
            ],
            'issues' => $issues,
        ];
    }
    
    /**
     * Provide optimization suggestions for container loading
     * 
     * @param ShipmentContainer $container
     * @param Collection $boxes
     * @return array Optimization suggestions
     */
    public function suggestOptimizations(ShipmentContainer $container, Collection $boxes): array
    {
        $capacityValidation = $this->validateCapacity($container, $boxes);
        $balanceValidation = $this->validateBalance($container, $boxes);
        
        $suggestions = [];
        
        // Volume optimization
        if ($capacityValidation['volume']['utilization'] < 60) {
            $suggestions[] = [
                'type' => 'downsize_container',
                'priority' => 'medium',
                'title' => 'Consider Smaller Container',
                'description' => sprintf(
                    'Current volume utilization is only %.1f%%. A smaller container could reduce costs.',
                    $capacityValidation['volume']['utilization']
                ),
                'potential_savings' => 'Cost reduction',
            ];
        } elseif ($capacityValidation['volume']['utilization'] > 85 && $capacityValidation['volume']['utilization'] < 95) {
            $suggestions[] = [
                'type' => 'optimal_utilization',
                'priority' => 'low',
                'title' => 'Good Volume Utilization',
                'description' => sprintf(
                    'Volume utilization is optimal at %.1f%%.',
                    $capacityValidation['volume']['utilization']
                ),
                'potential_savings' => 'None needed',
            ];
        }
        
        // Weight optimization
        if ($capacityValidation['weight']['utilization'] < 50 && $capacityValidation['volume']['utilization'] < 70) {
            $suggestions[] = [
                'type' => 'add_more_cargo',
                'priority' => 'medium',
                'title' => 'Add More Cargo',
                'description' => sprintf(
                    'Weight utilization is only %.1f%% and volume is %.1f%%. You can add %.2f kg more.',
                    $capacityValidation['weight']['utilization'],
                    $capacityValidation['volume']['utilization'],
                    $capacityValidation['weight']['available']
                ),
                'potential_savings' => 'Better cost per kg',
            ];
        }
        
        // Balance optimization
        if (!$balanceValidation['balanced']) {
            $suggestions[] = [
                'type' => 'rebalance_load',
                'priority' => 'high',
                'title' => 'Rebalance Container Load',
                'description' => 'Weight distribution is uneven. Redistribute boxes for better balance and safety.',
                'potential_savings' => 'Improved safety and handling',
            ];
        }
        
        // Consolidation suggestion
        if ($boxes->count() > 20 && $capacityValidation['volume']['utilization'] < 70) {
            $suggestions[] = [
                'type' => 'consolidate_boxes',
                'priority' => 'low',
                'title' => 'Consider Box Consolidation',
                'description' => sprintf(
                    'You have %d boxes with low utilization. Consider consolidating into fewer, larger boxes.',
                    $boxes->count()
                ),
                'potential_savings' => 'Reduced handling time',
            ];
        }
        
        // Sort by priority
        $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
        usort($suggestions, function ($a, $b) use ($priorityOrder) {
            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });
        
        return [
            'suggestions' => $suggestions,
            'total_suggestions' => count($suggestions),
            'high_priority' => count(array_filter($suggestions, fn($s) => $s['priority'] === 'high')),
            'overall_score' => $this->calculateOverallScore($capacityValidation, $balanceValidation),
        ];
    }
    
    /**
     * Perform comprehensive validation
     * 
     * @param ShipmentContainer $container
     * @param Collection $boxes
     * @return array Complete validation report
     */
    public function comprehensiveValidation(ShipmentContainer $container, Collection $boxes): array
    {
        $capacity = $this->validateCapacity($container, $boxes);
        $balance = $this->validateBalance($container, $boxes);
        $optimizations = $this->suggestOptimizations($container, $boxes);
        
        $allIssues = array_merge(
            $capacity['issues'] ?? [],
            $balance['issues'] ?? []
        );
        
        $overallValid = $capacity['valid'] && $balance['balanced'];
        $highestSeverity = $this->getHighestSeverity($allIssues);
        
        return [
            'valid' => $overallValid,
            'severity' => $highestSeverity,
            'overall_score' => $optimizations['overall_score'],
            'capacity' => $capacity,
            'balance' => $balance,
            'optimizations' => $optimizations,
            'summary' => [
                'total_issues' => count($allIssues),
                'errors' => count(array_filter($allIssues, fn($i) => $i['severity'] === self::SEVERITY_ERROR)),
                'warnings' => count(array_filter($allIssues, fn($i) => $i['severity'] === self::SEVERITY_WARNING)),
                'info' => count(array_filter($allIssues, fn($i) => $i['severity'] === self::SEVERITY_INFO)),
            ],
        ];
    }
    
    /**
     * Get highest severity from issues
     * 
     * @param array $issues
     * @return string Highest severity level
     */
    protected function getHighestSeverity(array $issues): string
    {
        if (empty($issues)) {
            return self::SEVERITY_INFO;
        }
        
        $severities = array_column($issues, 'severity');
        
        if (in_array(self::SEVERITY_ERROR, $severities)) {
            return self::SEVERITY_ERROR;
        }
        
        if (in_array(self::SEVERITY_WARNING, $severities)) {
            return self::SEVERITY_WARNING;
        }
        
        return self::SEVERITY_INFO;
    }
    
    /**
     * Calculate overall score (0-100)
     * 
     * @param array $capacityValidation
     * @param array $balanceValidation
     * @return float Overall score
     */
    protected function calculateOverallScore(array $capacityValidation, array $balanceValidation): float
    {
        // Capacity score (50% weight)
        $volumeUtil = $capacityValidation['volume']['utilization'];
        $weightUtil = $capacityValidation['weight']['utilization'];
        
        // Optimal utilization is 80-90%
        $volumeScore = 100 - abs(85 - $volumeUtil);
        $weightScore = 100 - abs(85 - $weightUtil);
        $capacityScore = ($volumeScore + $weightScore) / 2;
        
        // Balance score (30% weight)
        $balanceScore = $balanceValidation['balance_score'];
        
        // Issue penalty (20% weight)
        $errorCount = count(array_filter($capacityValidation['issues'] ?? [], fn($i) => $i['severity'] === self::SEVERITY_ERROR));
        $warningCount = count(array_filter($capacityValidation['issues'] ?? [], fn($i) => $i['severity'] === self::SEVERITY_WARNING));
        $issuePenalty = ($errorCount * 20) + ($warningCount * 10);
        $issueScore = max(0, 100 - $issuePenalty);
        
        $overallScore = ($capacityScore * 0.5) + ($balanceScore * 0.3) + ($issueScore * 0.2);
        
        return round(max(0, min(100, $overallScore)), 2);
    }
}
