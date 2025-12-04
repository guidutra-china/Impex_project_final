<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentSequence extends Model
{
    protected $table = 'shipment_sequences';
    
    protected $fillable = [
        'year',
        'next_number',
    ];
    
    protected $casts = [
        'year' => 'integer',
        'next_number' => 'integer',
    ];
    
    /**
     * Get or create sequence for current year
     */
    public static function forYear(int $year): self
    {
        return static::firstOrCreate(
            ['year' => $year],
            ['next_number' => 1]
        );
    }
    
    /**
     * Get next number and increment
     */
    public function getNextNumber(): int
    {
        $number = $this->next_number;
        $this->increment('next_number');
        return $number;
    }
}
