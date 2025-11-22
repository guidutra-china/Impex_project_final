<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspectionCheckpoint extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['checked_at', 'created_at'];

    protected $fillable = [
        'quality_inspection_id',
        'quality_checkpoint_id',
        'result',
        'measured_value',
        'expected_value',
        'notes',
        'checked_by',
        'checked_at',
    ];

    // Relationships
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(QualityCheckpoint::class, 'quality_checkpoint_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}