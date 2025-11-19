<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'supplier_code',
        'name',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'country',
        'website',
    ];


    /**
     * Get the tags for this supplier (polymorphic relationship)
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Get the categories for this supplier
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_supplier')
            ->withTimestamps();
    }

    /**
     * Get RFQ statuses for this supplier
     */
    public function rfqStatuses(): HasMany
    {
        return $this->hasMany(RFQSupplierStatus::class);
    }

    public function suppliercontacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }
    public function files(): HasMany
    {
        return $this->hasMany(SupplierFile::class)->orderBy('sort_order');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(SupplierFile::class)->where('file_type', 'photo')->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierFile::class)->where('file_type', 'document')->orderBy('date_uploaded', 'desc');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
