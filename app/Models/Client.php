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

        // Auto-generate client code if not provided
        static::creating(function ($client) {
            if (empty($client->code)) {
                $client->code = static::generateCode($client->name);
            }
            
            // Validate code length
            if (strlen($client->code) < 2) {
                throw new \Exception('Client must have a valid code of at least 2 characters. Got: ' . ($client->code ?? 'null'));
            }
        });
    }
    
    /**
     * Generate a 5-letter code from company name
     */
    protected static function generateCode(string $name): string
    {
        // Remove special characters and get first 5 consonants
        $clean = strtoupper(preg_replace('/[^A-Z]/i', '', $name));
        
        // Try to get 5 characters, prioritizing consonants
        $consonants = preg_replace('/[AEIOU]/', '', $clean);
        $code = substr($consonants, 0, 5);
        
        // If not enough consonants, use all characters
        if (strlen($code) < 5) {
            $code = substr($clean, 0, 5);
        }
        
        // If still not enough, pad with X
        $code = str_pad($code, 5, 'X');
        
        // Ensure uniqueness
        $originalCode = $code;
        $counter = 1;
        while (static::where('code', $code)->exists()) {
            $code = substr($originalCode, 0, 4) . $counter;
            $counter++;
            if ($counter > 9) {
                $code = substr($originalCode, 0, 3) . sprintf('%02d', $counter);
            }
        }
        
        return $code;
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
