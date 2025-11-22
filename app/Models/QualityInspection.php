<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inspection_number',
        'inspectable_type',
        'inspectable_id',
        'inspection_type',
        'status',
        'result',
        'inspection_date',
        'completed_date',
        'inspector_id',
        'inspector_name',
        'notes',
        'failure_reason',
        'corrective_action',
        'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'completed_date' => 'date',
    ];

    // Relationships
    public function inspectable(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(QualityInspectionItem::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(QualityInspectionCheckpoint::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(QualityCertificate::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeFailed($query)
    {
        return $query->where('result', 'failed');
    }
}