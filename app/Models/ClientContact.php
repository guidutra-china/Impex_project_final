<?php

namespace App\Models;

use App\Enums\ContactFunctionEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'client_id',
        'email',
        'phone',
        'wechat',
        'function',
    ];

    protected $casts = [
        'function' => ContactFunctionEnum::class,
    ];


    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
