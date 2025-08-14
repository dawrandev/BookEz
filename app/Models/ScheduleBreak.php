<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleBreak extends Model
{
    protected $fillable =
    [
        'schedule_id',
        'start_time',
        'end_time',
        'reason'
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
