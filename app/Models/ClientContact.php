<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    protected $fillable = [
        'name',
        'client_id',
        'email',
        'phone',
        'wechat',
        'function',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
