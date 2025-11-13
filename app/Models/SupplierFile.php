<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SupplierFile extends Model
{
    protected $fillable = [
        'supplier_id',
        'file_type',
        'file_path',
        'original_filename',
        'description',
        'date_uploaded',
        'file_size',
        'mime_type',
        'sort_order',
    ];

    protected $casts = [
        'date_uploaded' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the full URL to the file
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
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
     * Check if file is a photo
     */
    public function isPhoto(): bool
    {
        return $this->file_type === 'photo';
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        return $this->file_type === 'document';
    }

    /**
     * Scope to get only photos
     */
    public function scopePhotos($query)
    {
        return $query->where('file_type', 'photo');
    }

    /**
     * Scope to get only documents
     */
    public function scopeDocuments($query)
    {
        return $query->where('file_type', 'document');
    }
}
