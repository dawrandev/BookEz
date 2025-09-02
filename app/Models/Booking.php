<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'client_id',
        'schedule_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'completed_at',
        'rating',
        'feedback'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'rating' => 'integer'
    ];

    // Relationships
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => Carbon::now()
        ]);
    }

    public function addRating(int $rating, ?string $feedback = null): void
    {
        $this->update([
            'rating' => $rating,
            'feedback' => $feedback
        ]);
    }

    public function getBookingDateTimeAttribute(): Carbon
    {
        return Carbon::parse($this->schedule->work_date . ' ' . $this->start_time);
    }

    public function isPast(): bool
    {
        return $this->getBookingDateTimeAttribute()->isPast();
    }

    public function isToday(): bool
    {
        return $this->getBookingDateTimeAttribute()->isToday();
    }

    public function canBeCanceled(): bool
    {
        if (!in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        $bookingDateTime = $this->getBookingDateTimeAttribute();
        return $bookingDateTime->isFuture() && $bookingDateTime->diffInHours(Carbon::now()) >= 1;
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Kútilmekte',
            'confirmed' => 'Tastıyıqlandı',
            'canceled' => 'Bıykarlandı',
            'completed' => 'Juwmaqlandı',
            default => $this->status
        };
    }
}
