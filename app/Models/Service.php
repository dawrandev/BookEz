<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'price',
        'duration_minutes',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
