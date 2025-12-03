<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavedFilter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'resource_type',
        'name',
        'description',
        'filters',
        'is_public',
        'is_default',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_public' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the saved filter
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get filters for a specific resource
     */
    public function scopeForResource($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope to get public filters
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get user's private filters
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get accessible filters (public or owned by user)
     */
    public function scopeAccessibleBy($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_public', true)
              ->orWhere('user_id', $userId);
        });
    }

    /**
     * Get the default filter for a resource and user
     */
    public static function getDefault(string $resourceType, int $userId): ?self
    {
        return static::forResource($resourceType)
            ->forUser($userId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Set this filter as default (and unset others)
     */
    public function setAsDefault(): void
    {
        // Unset other defaults for this user and resource
        static::forResource($this->resource_type)
            ->forUser($this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }
}
