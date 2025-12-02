<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class GeneratedDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'document_type',
        'document_number',
        'format',
        'filename',
        'file_path',
        'file_size',
        'version',
        'revision_number',
        'generated_by',
        'generated_at',
        'generated_from_ip',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'metadata' => 'array',
        'file_size' => 'integer',
        'version' => 'integer',
        'revision_number' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (!$document->generated_at) {
                $document->generated_at = now();
            }
            if (!$document->generated_by) {
                $document->generated_by = auth()->id();
            }
            if (!$document->generated_from_ip) {
                $document->generated_from_ip = request()->ip();
            }
        });

        // Delete file when document is force deleted
        static::forceDeleted(function ($document) {
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
        });
    }

    /**
     * Relationships
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Helper methods
     */
    public function getFullPath(): string
    {
        return Storage::path($this->file_path);
    }

    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function download(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::download($this->file_path, $this->filename);
    }

    public function exists(): bool
    {
        return Storage::exists($this->file_path);
    }

    public function getFormattedSize(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Scopes
     */
    public function scopeForDocument($query, $documentableType, $documentableId)
    {
        return $query->where('documentable_type', $documentableType)
                     ->where('documentable_id', $documentableId);
    }

    public function scopeOfType($query, string $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    public function scopeOfFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc')->orderBy('generated_at', 'desc');
    }

    /**
     * Static helper to create new document record
     */
    public static function createFromFile(
        Model $documentable,
        string $documentType,
        string $format,
        string $filePath,
        array $options = []
    ): self {
        $filename = $options['filename'] ?? basename($filePath);
        $fileSize = Storage::exists($filePath) ? Storage::size($filePath) : null;

        // Get next version number
        $version = static::forDocument(get_class($documentable), $documentable->id)
            ->ofType($documentType)
            ->ofFormat($format)
            ->max('version') ?? 0;
        $version++;

        return static::create([
            'documentable_type' => get_class($documentable),
            'documentable_id' => $documentable->id,
            'document_type' => $documentType,
            'document_number' => $options['document_number'] ?? null,
            'format' => $format,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'version' => $version,
            'revision_number' => $options['revision_number'] ?? null,
            'metadata' => $options['metadata'] ?? null,
            'notes' => $options['notes'] ?? null,
        ]);
    }
}
