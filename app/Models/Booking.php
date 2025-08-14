<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable =
    [
        'user_id',
        'service_id',
        'client_id',
        'booking_date',
        'start_time',
        'end_time'
    ];
}
