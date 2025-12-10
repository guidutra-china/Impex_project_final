<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_type',
        'file_path',
        'file_size',
        'import_type',
        'document_type',
        'ai_analysis',
        'column_mapping',
        'supplier_name',
        'supplier_email',
        'status',
        'total_rows',
        'success_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'warning_count',
        'errors',
        'warnings',
        'result_message',
        'analyzed_at',
        'imported_at',
    ];

    protected $casts = [
        'ai_analysis' => 'array',
        'column_mapping' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
        'analyzed_at' => 'datetime',
        'imported_at' => 'datetime',
        'file_size' => 'integer',
        'total_rows' => 'integer',
        'success_count' => 'integer',
        'updated_count' => 'integer',
        'skipped_count' => 'integer',
        'error_count' => 'integer',
        'warning_count' => 'integer',
    ];

    /**
     * Get the user that owns the import
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'analyzing' => 'Analyzing...',
            'ready' => 'Ready to Import',
            'importing' => 'Importing...',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }

    /**
     * Get status color for badges
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'analyzing' => 'info',
            'ready' => 'warning',
            'importing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Check if import is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'analyzing', 'importing']);
    }

    /**
     * Check if import is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if import is ready to execute
     */
    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
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
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->success_count + $this->updated_count) / $this->total_rows * 100, 2);
    }
}
