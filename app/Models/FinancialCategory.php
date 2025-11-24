<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'parent_id',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot method to auto-generate code if not provided
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->code)) {
                // Generate code from name
                $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($category->name)), 0, 10));
                
                // Ensure uniqueness
                $originalCode = $code;
                $counter = 1;
                while (static::where('code', $code)->exists()) {
                    $code = $originalCode . $counter;
                    $counter++;
                }
                
                $category->code = $code;
            }
        });
    }

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(FinancialCategory::class, 'parent_id')
            ->orderBy('sort_order');
    }

    /**
     * Get all transactions in this category
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * Get all recurring transactions in this category
     */
    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only expense categories
     */
    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope to get only revenue categories
     */
    public function scopeRevenues($query)
    {
        return $query->where('type', 'revenue');
    }

    /**
     * Scope to get only root categories (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the full hierarchical name
     */
    public function getFullNameAttribute(): string
    {
        $names = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($names, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $names);
    }

    /**
     * Check if this category can be deleted
     */
    public function canBeDeleted(): bool
    {
        // System categories cannot be deleted
        if ($this->is_system) {
            return false;
        }

        // Categories with transactions cannot be deleted
        if ($this->transactions()->exists()) {
            return false;
        }

        // Categories with children cannot be deleted
        if ($this->children()->exists()) {
            return false;
        }

        return true;
    }
}
