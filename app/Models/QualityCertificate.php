<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_inspection_id',
        'certificate_number',
        'certificate_type',
        'issue_date',
        'expiry_date',
        'file_path',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('status', 'valid')
            ->where(function($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }
}
