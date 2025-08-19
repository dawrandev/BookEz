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
        'schedule_id',
        'booking_date',
        'start_time',
        'end_time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
