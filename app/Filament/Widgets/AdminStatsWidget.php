<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Client;
use App\Models\Booking;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AdminStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $specialistsCount = User::role('specialist')->count();

        $clientsCount = Client::whereHas('bookings', function ($query) {
            $query->where('status', 'completed');
        })->count();

        $activeSpecialists = User::role('specialist')
            ->whereHas('bookings', function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->count();

        $todayCompletedBookings = Booking::where('bookings.status', 'completed')
            ->whereHas('schedule', function ($query) use ($today) {
                $query->whereDate('work_date', $today);
            })
            ->count();

        $monthlyCompletedBookings = Booking::where('bookings.status', 'completed')
            ->whereHas('schedule', function ($query) use ($thisMonth) {
                $query->where('work_date', '>=', $thisMonth);
            })
            ->count();

        $monthlyRevenue = Booking::where('bookings.status', 'completed')
            ->whereHas('schedule', function ($query) use ($thisMonth) {
                $query->where('work_date', '>=', $thisMonth);
            })
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->sum('services.price');

        $totalSubscriptionAmount = Subscription::sum('amount');

        $monthlySubscriptionAmount = Subscription::whereMonth('created_at', $thisMonth->month)
            ->whereYear('created_at', $thisMonth->year)
            ->sum('amount');

        $activeSubscriptionAmount = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->sum('amount');

        return [
            Stat::make('Всего специалистов', $specialistsCount)
                ->description('Зарегистрированы в системе')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Всего клиентов', $clientsCount)
                ->description('Зарегистрированные клиенты')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Активные специалисты', $activeSpecialists)
                ->description('За последние 30 дней')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('warning'),

            Stat::make('Общая сумма подписок', number_format($totalSubscriptionAmount) . ' UZS')
                ->description('Все подписки')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Подписки за месяц', number_format($monthlySubscriptionAmount) . ' UZS')
                ->description('Текущий месяц')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Сегодняшние услуги', $todayCompletedBookings)
                ->description('Выполненные сегодня')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
        ];
    }
}
