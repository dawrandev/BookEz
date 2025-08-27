<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialNetworks extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'url',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
