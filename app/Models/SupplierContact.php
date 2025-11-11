<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContact extends Model
{
    protected $fillable = [
        'name',
        'supplier_id',
        'email',
        'phone',
        'wechat',
        'function',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);

    }
}
