<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'country',
        'website',
        'tax_number',
    ];

    public function clientcontacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'customer_id');
    }


}
