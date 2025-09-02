<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Client;
use App\Models\Booking;
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

        // Количество специалистов
        $specialistsCount = User::role('specialist')->count();

        // Количество клиентов
        $clientsCount = Client::count();

        // Активные специалисты (за последние 30 дней были бронирования)
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

            Stat::make('Сегодняшние услуги', $todayCompletedBookings)
                ->description('Выполненные сегодня')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Услуги за месяц', $monthlyCompletedBookings)
                ->description('Текущий месяц')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}
