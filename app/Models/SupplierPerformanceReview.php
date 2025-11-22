<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'review_date',
        'review_period_start',
        'review_period_end',
        'delivery_score',
        'quality_score',
        'pricing_score',
        'communication_score',
        'overall_score',
        'rating',
        'strengths',
        'weaknesses',
        'recommendations',
        'decision',
        'reviewed_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'review_period_start' => 'date',
        'review_period_end' => 'date',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeRecent($query, int $months = 6)
    {
        return $query->where('review_date', '>=', now()->subMonths($months));
    }

    public function scopeByRating($query, string $rating)
    {
        return $query->where('rating', $rating);
    }
}