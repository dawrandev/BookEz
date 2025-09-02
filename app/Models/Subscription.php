<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'amount',
        'months_count',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'integer',
        'months_count' => 'integer',
    ];

    const MONTHLY_PRICE = 200000; // Default price

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PENDING = 'pending';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('end_date', '>=', now()->toDateString());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now()->toDateString())
            ->orWhere('status', self::STATUS_EXPIRED);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays($days));
    }

    public function scopeByPlan($query, int $planId)
    {
        return $query->where('subscription_plan_id', $planId);
    }

    /**
     * Attributes
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->end_date >= now()->toDateString();
    }

    public function getRemainingDaysAttribute(): int
    {
        if (!$this->is_active) {
            return 0;
        }

        $remaining = now()->diffInDays($this->end_date, false);
        return max(0, $remaining);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount) . ' UZS';
    }

    public function getFormattedStartDateAttribute(): string
    {
        return $this->start_date->format('d.m.Y');
    }

    public function getFormattedEndDateAttribute(): string
    {
        return $this->end_date->format('d.m.Y');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Активный',
            self::STATUS_EXPIRED => 'Истекший',
            self::STATUS_PENDING => 'Ожидание',
            default => $this->status
        };
    }

    /**
     * Static Methods - Plan bilan ishlash uchun
     */
    public static function calculateMonthsForPlan(int $planId, int $amount): int
    {
        $plan = SubscriptionPlan::find($planId);
        if (!$plan) {
            throw new \InvalidArgumentException('Plan topilmadi');
        }

        if ($plan->price <= 0) {
            throw new \InvalidArgumentException('Plan narxi noto\'g\'ri');
        }

        return floor($amount / $plan->price);
    }

    public static function createSubscriptionWithPlan(int $userId, int $planId, int $amount, ?string $notes = null): self
    {
        $user = User::find($userId);
        $plan = SubscriptionPlan::find($planId);

        if (!$user) {
            throw new \InvalidArgumentException('User topilmadi');
        }

        if (!$plan) {
            throw new \InvalidArgumentException('Plan topilmadi');
        }

        if (!$plan->is_active) {
            throw new \InvalidArgumentException('Plan faol emas');
        }

        $monthsCount = self::calculateMonthsForPlan($planId, $amount);
        $startDate = now();
        $endDate = $startDate->copy()->addMonths($monthsCount);

        return self::create([
            'user_id' => $userId,
            'subscription_plan_id' => $planId,
            'amount' => $amount,
            'months_count' => $monthsCount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => self::STATUS_ACTIVE,
            'notes' => $notes,
        ]);
    }

    /**
     * Eski metodlar (backward compatibility uchun)
     */
    public static function calculateMonthsForUser(int $userId, int $amount): int
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User topilmadi');
        }

        $monthlyPrice = $user->getMonthlyPrice();
        return floor($amount / $monthlyPrice);
    }

    public static function createSubscription(int $userId, int $amount, ?string $notes = null): self
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User topilmadi');
        }

        return $user->createSubscription($amount, $notes);
    }

    /**
     * Instance Methods
     */
    public function extend(int $additionalAmount): bool
    {
        if (!$this->subscriptionPlan) {
            throw new \InvalidArgumentException('Subscription plan topilmadi');
        }

        $additionalMonths = floor($additionalAmount / $this->subscriptionPlan->price);

        if ($additionalMonths <= 0) {
            throw new \InvalidArgumentException('Qo\'shimcha summa plan narxidan kam');
        }

        $this->update([
            'amount' => $this->amount + $additionalAmount,
            'months_count' => $this->months_count + $additionalMonths,
            'end_date' => Carbon::parse($this->end_date)->addMonths($additionalMonths),
            'status' => self::STATUS_ACTIVE,
        ]);

        return true;
    }

    public function expire(): bool
    {
        return $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function activate(): bool
    {
        if ($this->end_date < now()) {
            return false; // Vaqti o'tgan subscriptionni aktivlashtirish mumkin emas
        }

        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->remaining_days <= $days && $this->remaining_days > 0;
    }

    /**
     * Model Events
     */
    protected static function boot()
    {
        parent::boot();

        // Saqlashdan oldin statusni tekshirish
        static::saving(function ($subscription) {
            if ($subscription->end_date < now()->toDateString()) {
                $subscription->status = self::STATUS_EXPIRED;
            }
        });

        // Subscription yaratilganda user ga plan ni biriktirish
        static::created(function ($subscription) {
            if ($subscription->subscriptionPlan && $subscription->user) {
                $subscription->user->update([
                    'subscription_plan_id' => $subscription->subscription_plan_id
                ]);
            }
        });
    }
}
