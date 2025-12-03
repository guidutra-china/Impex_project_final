<?php

namespace App\Models;

use App\Models\Scopes\ClientOwnershipScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new ClientOwnershipScope());
    }

    protected static function boot()
    {
        parent::boot();

        // Ensure every client has a valid code
        static::creating(function ($client) {
            if (empty($client->code) || strlen($client->code) < 2) {
                throw new \Exception('Client must have a valid code of at least 2 characters. Got: ' . ($client->code ?? 'null'));
            }
        });
    }
    
    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clientcontacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'customer_id');
    }
}
