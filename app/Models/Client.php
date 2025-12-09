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
     * Uses first 5 letters, if duplicate replaces last char with number
     */
    protected static function generateCode(string $name): string
    {
        // Remove special characters and spaces, keep only letters
        $clean = strtoupper(preg_replace('/[^A-Z]/i', '', $name));
        
        // Get first 5 characters
        $code = substr($clean, 0, 5);
        
        // If not enough characters, pad with X
        $code = str_pad($code, 5, 'X');
        
        // Ensure uniqueness by replacing last character with number
        if (static::where('code', $code)->exists()) {
            $baseCode = substr($code, 0, 4);
            $counter = 1;
            
            do {
                $code = $baseCode . $counter;
                $counter++;
            } while (static::where('code', $code)->exists() && $counter < 10);
            
            // If still not unique after 1-9, throw exception
            if ($counter >= 10 && static::where('code', $code)->exists()) {
                throw new \Exception("Unable to generate unique code for: {$name}");
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
