<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'features',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'price' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Methods
    public function calculateMonths(int $amount): int
    {
        if ($this->price <= 0) {
            return 0;
        }

        return floor($amount / $this->price);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price) . ' UZS';
    }

    public function getFeaturesListAttribute(): string
    {
        if (!$this->features) {
            return 'Нет особенностей';
        }

        return implode(', ', $this->features);
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($plan) {
            if ($plan->is_default) {
                static::where('id', '!=', $plan->id)->update(['is_default' => false]);
            }
        });
    }
}
