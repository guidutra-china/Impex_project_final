<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['event_date', 'created_at'];

    protected $fillable = [
        'shipment_id',
        'event_type',
        'event_description',
        'notes',
        'location',
        'city',
        'country',
        'event_date',
        'source',
    ];

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}