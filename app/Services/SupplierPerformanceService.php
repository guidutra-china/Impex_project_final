<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierPerformanceMetric;
use App\Models\SupplierPerformanceReview;
use App\Models\SupplierIssue;
use App\Models\PurchaseOrder;
use App\Models\QualityInspection;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceService
{
    /**
     * Calculate supplier performance metrics for a period
     *
     * @param int $supplierId
     * @param int $year
     * @param int $month
     * @return SupplierPerformanceMetric
     */
    public function calculateMetrics(int $supplierId, int $year, int $month): SupplierPerformanceMetric
    {
        $supplier = Supplier::findOrFail($supplierId);

        // Get POs for the period
        $pos = PurchaseOrder::where('supplier_id', $supplierId)
            ->whereYear('po_date', $year)
            ->whereMonth('po_date', $month)
            ->get();

        // Calculate delivery metrics
        $totalOrders = $pos->count();
        $onTimeDeliveries = $pos->filter(function($po) {
            return $po->actual_delivery_date && 
                   $po->actual_delivery_date <= $po->expected_delivery_date;
        })->count();
        $lateDeliveries = $totalOrders - $onTimeDeliveries;

        $averageDelayDays = $pos->filter(function($po) {
            return $po->actual_delivery_date && 
                   $po->actual_delivery_date > $po->expected_delivery_date;
        })->avg(function($po) {
            return $po->actual_delivery_date->diffInDays($po->expected_delivery_date);
        }) ?? 0;

        // Calculate quality metrics
        $inspections = QualityInspection::where('inspectable_type', PurchaseOrder::class)
            ->whereIn('inspectable_id', $pos->pluck('id'))
            ->get();

        $totalInspections = $inspections->count();
        $passedInspections = $inspections->where('result', 'passed')->count();
        $failedInspections = $inspections->where('result', 'failed')->count();
        $qualityScore = $totalInspections > 0 ? ($passedInspections / $totalInspections) * 100 : 0;

        // Calculate financial metrics
        $totalPurchaseValue = $pos->sum('total');
        $averageOrderValue = $totalOrders > 0 ? $totalPurchaseValue / $totalOrders : 0;

        // Calculate overall score
        $deliveryScore = $totalOrders > 0 ? ($onTimeDeliveries / $totalOrders) * 100 : 0;
        $overallScore = ($deliveryScore * 0.4) + ($qualityScore * 0.4) + (80 * 0.2); // 80 is default communication score

        // Determine rating
        $rating = $this->determineRating($overallScore);

        // Create or update metric
        return SupplierPerformanceMetric::updateOrCreate(
            [
                'supplier_id' => $supplierId,
                'period_year' => $year,
                'period_month' => $month,
            ],
            [
                'total_orders' => $totalOrders,
                'on_time_deliveries' => $onTimeDeliveries,
                'late_deliveries' => $lateDeliveries,
                'average_delay_days' => $averageDelayDays,
                'total_inspections' => $totalInspections,
                'passed_inspections' => $passedInspections,
                'failed_inspections' => $failedInspections,
                'quality_score' => $qualityScore,
                'total_purchase_value' => $totalPurchaseValue,
                'total_orders_value' => $totalPurchaseValue,
                'average_order_value' => $averageOrderValue,
                'communication_score' => 80, // Default, can be updated manually
                'overall_score' => $overallScore,
                'rating' => $rating,
            ]
        );
    }

    /**
     * Create supplier performance review
     *
     * @param int $supplierId
     * @param array $data
     * @return SupplierPerformanceReview
     */
    public function createReview(int $supplierId, array $data): SupplierPerformanceReview
    {
        $overallScore = (
            $data['delivery_score'] * 0.25 +
            $data['quality_score'] * 0.25 +
            $data['pricing_score'] * 0.25 +
            $data['communication_score'] * 0.25
        );

        $rating = $this->determineRating($overallScore);

        return SupplierPerformanceReview::create([
            'supplier_id' => $supplierId,
            'review_date' => now(),
            'review_period_start' => $data['review_period_start'],
            'review_period_end' => $data['review_period_end'],
            'delivery_score' => $data['delivery_score'],
            'quality_score' => $data['quality_score'],
            'pricing_score' => $data['pricing_score'],
            'communication_score' => $data['communication_score'],
            'overall_score' => $overallScore,
            'rating' => $rating,
            'strengths' => $data['strengths'] ?? null,
            'weaknesses' => $data['weaknesses'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'decision' => $data['decision'] ?? null,
            'reviewed_by' => auth()->id(),
        ]);
    }

    /**
     * Create supplier issue
     *
     * @param int $supplierId
     * @param array $data
     * @return SupplierIssue
     */
    public function createIssue(int $supplierId, array $data): SupplierIssue
    {
        return SupplierIssue::create([
            'supplier_id' => $supplierId,
            'purchase_order_id' => $data['purchase_order_id'] ?? null,
            'issue_type' => $data['issue_type'],
            'severity' => $data['severity'],
            'status' => 'open',
            'description' => $data['description'],
            'financial_impact' => $data['financial_impact'] ?? 0,
            'reported_date' => now(),
            'reported_by' => auth()->id(),
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);
    }

    /**
     * Resolve supplier issue
     *
     * @param SupplierIssue $issue
     * @param string $resolution
     * @return SupplierIssue
     */
    public function resolveIssue(SupplierIssue $issue, string $resolution): SupplierIssue
    {
        $issue->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolution_date' => now(),
        ]);

        return $issue;
    }

    /**
     * Get supplier performance summary
     *
     * @param int $supplierId
     * @param int $months
     * @return array
     */
    public function getPerformanceSummary(int $supplierId, int $months = 6): array
    {
        $metrics = SupplierPerformanceMetric::where('supplier_id', $supplierId)
            ->where('period_year', '>=', now()->subMonths($months)->year)
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        $openIssues = SupplierIssue::where('supplier_id', $supplierId)
            ->open()
            ->count();

        $criticalIssues = SupplierIssue::where('supplier_id', $supplierId)
            ->critical()
            ->unresolved()
            ->count();

        return [
            'average_overall_score' => $metrics->avg('overall_score'),
            'average_delivery_rate' => $metrics->avg('on_time_delivery_rate'),
            'average_quality_rate' => $metrics->avg('quality_pass_rate'),
            'current_rating' => $metrics->first()?->rating ?? 'N/A',
            'total_orders' => $metrics->sum('total_orders'),
            'total_purchase_value' => $metrics->sum('total_purchase_value'),
            'open_issues' => $openIssues,
            'critical_issues' => $criticalIssues,
            'metrics_by_period' => $metrics->toArray(),
        ];
    }

    /**
     * Determine rating based on score
     *
     * @param float $score
     * @return string
     */
    private function determineRating(float $score): string
    {
        return match(true) {
            $score >= 90 => 'excellent',
            $score >= 75 => 'good',
            $score >= 60 => 'average',
            $score >= 40 => 'poor',
            default => 'unacceptable',
        };
    }

    /**
     * Calculate metrics for all suppliers for a period
     *
     * @param int $year
     * @param int $month
     * @return int Number of suppliers processed
     */
    public function calculateAllMetrics(int $year, int $month): int
    {
        $suppliers = Supplier::active()->get();
        $count = 0;

        foreach ($suppliers as $supplier) {
            $this->calculateMetrics($supplier->id, $year, $month);
            $count++;
        }

        return $count;
    }
}
