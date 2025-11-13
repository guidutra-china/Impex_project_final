<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
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

}
