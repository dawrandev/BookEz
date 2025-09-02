<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalActiveSubscriptions = Subscription::active()->count();
        $totalExpiredSubscriptions = Subscription::expired()->count();
        $thisMonthRevenue = Subscription::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        $expiringSoon = Subscription::active()
            ->where('end_date', '<=', now()->addDays(7))
            ->count();

        return [
            Stat::make('Активные подписки', $totalActiveSubscriptions)
                ->description('Сейчас активны')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Доход за месяц', number_format($thisMonthRevenue) . ' UZS')
                ->description('Текущий месяц')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Истекают скоро', $expiringSoon)
                ->description('В течение 7 дней')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Истекшие', $totalExpiredSubscriptions)
                ->description('Требуют продления')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
