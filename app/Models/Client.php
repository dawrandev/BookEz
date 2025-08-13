<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable =
    [
        'telegram_chat_id',
        'telegram_id',
        'username',
        'full_name',
        'phone',
    ];
}
