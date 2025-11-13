<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'country',
        'website',
    ];


    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
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

}
