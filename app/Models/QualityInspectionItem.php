<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_inspection_id',
        'product_id',
        'quantity_inspected',
        'quantity_passed',
        'quantity_failed',
        'result',
        'defects_found',
        'notes',
    ];

    // Relationships
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}