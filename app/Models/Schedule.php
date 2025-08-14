<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable =
    [
        'user_id',
        'work_date',
        'start_time',
        'end_time',
        'is_day_off'
    ];

    protected $casts = [
        'work_date' => 'date',
        'start_time' => 'datetime', // yoki 'time'
        'end_time' => 'datetime',   // yoki 'time'
        'is_day_off' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(ScheduleBreak::class);
    }
}
