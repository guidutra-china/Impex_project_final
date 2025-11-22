<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'period_year',
        'period_month',
        'total_orders',
        'on_time_deliveries',
        'late_deliveries',
        'average_delay_days',
        'total_inspections',
        'passed_inspections',
        'failed_inspections',
        'quality_score',
        'total_purchase_value',
        'total_orders_value',
        'average_order_value',
        'response_time_hours',
        'communication_score',
        'overall_score',
        'rating',
        'notes',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Accessors
    public function getOnTimeDeliveryRateAttribute(): float
    {
        if ($this->total_orders == 0) return 0;
        return ($this->on_time_deliveries / $this->total_orders) * 100;
    }

    public function getQualityPassRateAttribute(): float
    {
        if ($this->total_inspections == 0) return 0;
        return ($this->passed_inspections / $this->total_inspections) * 100;
    }

    // Scopes
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)
            ->where('period_month', $month);
    }

    public function scopeExcellent($query)
    {
        return $query->where('rating', 'excellent');
    }

    public function scopePoor($query)
    {
        return $query->whereIn('rating', ['poor', 'unacceptable']);
    }
}