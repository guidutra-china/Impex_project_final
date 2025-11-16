<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductFile extends Model
{
    protected $fillable = [
        'product_id',
        'file_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'description',
        'date_uploaded',
        'sort_order',
    ];

    protected $casts = [
        'date_uploaded' => 'date',
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the product that owns the file
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get formatted file size
     */
    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get full URL to the file
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}