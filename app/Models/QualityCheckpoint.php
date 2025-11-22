<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'checkpoint_type',
        'criterion',
        'applies_to',
        'product_category_id',
        'product_id',
        'is_active',
        'is_mandatory',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    // Relationships
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inspectionCheckpoints(): HasMany
    {
        return $this->hasMany(QualityInspectionCheckpoint::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }
}