<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'telegram_id',
        'telegram_chat_id',
        'username',
        'photo',
        'name',
        'login',
        'phone',
        'password',
        'category_id',
        'description',
        'status',
        'location'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $casts = [
        'location' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->assignRole('specialist');
        });

        static::creating(function ($user) {
            if (!$user->subscription_plan_id) {
                $defaultPlan = SubscriptionPlan::getDefault();
                $user->subscription_plan_id = $defaultPlan?->id;
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function socials()
    {
        return $this->hasMany(SocialNetworks::class);
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return Storage::url($this->photo);
        }
        return null;
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->photo) {
            return Storage::url($this->photo);
        }
        return asset('images/default-avatar.png');
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->latest();
    }

    // Helper methods
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function getMonthlyPrice(): int
    {
        // Agar user uchun maxsus plan belgilangan bo'lsa
        if ($this->subscriptionPlan) {
            return $this->subscriptionPlan->price;
        }

        // Aks holda default plan narxini qaytarish
        $defaultPlan = SubscriptionPlan::getDefault();
        return $defaultPlan ? $defaultPlan->price : 200000;
    }

    public function getSubscriptionPlanName(): string
    {
        return $this->subscriptionPlan?->name ?? 'Стандарт';
    }

    public function canCalculateMonths(int $amount): int
    {
        $monthlyPrice = $this->getMonthlyPrice();
        return floor($amount / $monthlyPrice);
    }

    public function createSubscription(int $amount, ?string $notes = null): Subscription
    {
        $monthlyPrice = $this->getMonthlyPrice();
        $monthsCount = floor($amount / $monthlyPrice);

        if ($monthsCount < 1) {
            throw new \InvalidArgumentException(
                "Summa kamida {$monthlyPrice} UZS (1 oylik to'lov) bo'lishi kerak"
            );
        }

        $startDate = now()->toDateString();
        $endDate = now()->addMonths($monthsCount)->toDateString();

        return $this->subscriptions()->create([
            'subscription_plan_id' => $this->subscription_plan_id,
            'amount' => $amount,
            'months_count' => $monthsCount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
            'notes' => $notes,
        ]);
    }
}
