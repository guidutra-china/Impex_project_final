<?php

namespace App\Models;

use App\Enums\ContactFunctionEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContact extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'supplier_id',
        'email',
        'phone',
        'wechat',
        'function',
    ];

    protected $casts = [
        'function' => ContactFunctionEnum::class,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);

    }
}
