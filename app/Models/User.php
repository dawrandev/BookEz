<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

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
        'location',
        'subscription_plan_id', // qo'shildi
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
            'location' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->assignRole('specialist');
        });

        static::creating(function ($user) {
            // Default status qo'yish
            if (!$user->status) {
                $user->status = 'inactive';
            }

            // Default subscription plan qo'yish
            if (!$user->subscription_plan_id) {
                $defaultPlan = SubscriptionPlan::getDefault();
                $user->subscription_plan_id = $defaultPlan?->id;
            }
        });
    }

    /**
     * Existing Relationships
     */
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

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->latest();
    }

    /**
     * YANGI: Barcha faol subscriptionlar uchun relationship
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('end_date', '>=', now()->toDateString());
    }

    /**
     * Photo/Avatar Methods (existing)
     */
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

    /**
     * Subscription Helper Methods (existing + yangi)
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscriptions()->exists();
    }

    public function getMonthlyPrice(): int
    {
        // Agar faol subscription bor bo'lsa, uning planidan narxni olish
        $activeSubscription = $this->activeSubscriptions()->first();
        if ($activeSubscription && $activeSubscription->subscriptionPlan) {
            return $activeSubscription->subscriptionPlan->price;
        }

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

    /**
     * YANGI: User statusini boshqarish metodlari
     */
    public function updateStatusBasedOnSubscriptions(): void
    {
        $newStatus = $this->hasActiveSubscription() ? 'active' : 'inactive';

        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }

    public function getTotalSubscriptionAmount(): int
    {
        return $this->subscriptions()->sum('amount');
    }

    public function getTotalActiveSubscriptionAmount(): int
    {
        return $this->activeSubscriptions()->sum('amount');
    }

    public function getSubscriptionHistory()
    {
        return $this->subscriptions()
            ->with('subscriptionPlan')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getNextExpirationDate(): ?Carbon
    {
        $subscription = $this->activeSubscriptions()
            ->orderBy('end_date', 'asc')
            ->first();

        return $subscription?->end_date;
    }

    public function getDaysUntilExpiration(): int
    {
        $nextExpiration = $this->getNextExpirationDate();

        if (!$nextExpiration) {
            return 0;
        }

        return max(0, now()->diffInDays($nextExpiration, false));
    }

    public function isSubscriptionExpiringSoon(int $days = 7): bool
    {
        $daysUntilExpiration = $this->getDaysUntilExpiration();
        return $daysUntilExpiration > 0 && $daysUntilExpiration <= $days;
    }

    /**
     * YANGI: Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeWithActiveSubscriptions($query)
    {
        return $query->whereHas('activeSubscriptions');
    }

    public function scopeWithoutActiveSubscriptions($query)
    {
        return $query->whereDoesntHave('activeSubscriptions');
    }

    public function scopeSubscriptionExpiringSoon($query, int $days = 7)
    {
        return $query->whereHas('activeSubscriptions', function ($q) use ($days) {
            $q->where('end_date', '>=', now())
                ->where('end_date', '<=', now()->addDays($days));
        });
    }

    /**
     * YANGI: Attributes
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Активный',
            'inactive' => 'Неактивный',
            default => $this->status
        };
    }

    public function getHasActiveSubscriptionAttribute(): bool
    {
        return $this->hasActiveSubscription();
    }
}
