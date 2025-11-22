<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['last_movement_date', 'updated_at'];

    protected $fillable = [
        'warehouse_id',
        'warehouse_location_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_value',
        'last_movement_date',
    ];

    // Relationships
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}