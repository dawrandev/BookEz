<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        $avgRating = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->avg('rating');

        $ratingCount = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->count();

        $todayRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->with('service')
            ->get()
            ->sum('service.price');

        $yesterdayRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('completed_at', today()->subDay())
            ->with('service')
            ->get()
            ->sum('service.price');

        $monthRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->with('service')
            ->get()
            ->sum('service.price');

        $lastMonthRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->subMonth()->month)
            ->whereYear('completed_at', now()->subMonth()->year)
            ->with('service')
            ->get()
            ->sum('service.price');

        $yearRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereYear('completed_at', now()->year)
            ->with('service')
            ->get()
            ->sum('service.price');

        $totalClients = Booking::where('user_id', $userId)
            ->distinct('client_id')
            ->count('client_id');

        $newClientsThisMonth = Booking::where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->distinct('client_id')
            ->count('client_id');

        return [
            Stat::make('Общий Рейтинг', $avgRating ? number_format($avgRating, 1) . '/5' : 'Пока нет')
                ->description($ratingCount > 0 ? "{$ratingCount} оценок" : 'Нет оценок')
                ->descriptionIcon($avgRating >= 4.5 ? 'heroicon-m-star' : 'heroicon-m-star')
                ->color($avgRating >= 4.5 ? 'success' : ($avgRating >= 4 ? 'warning' : 'danger'))
                ->chart(array_fill(0, 7, $avgRating ?? 0)),

            Stat::make('Сегодняшний Доход', number_format($todayRevenue, 0))
                ->description('Из завершённых услуг сегодня')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getWeeklyRevenueChart($userId)),

            Stat::make('Доход Этого Месяца', number_format($monthRevenue, 0))
                ->description(now()->format('F') . ' доход за месяц')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary')
                ->chart($this->getMonthlyChart($userId)),

            Stat::make('Доход Этого Года', number_format($yearRevenue, 0))
                ->description("Всего {$totalClients} клиентов")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart($this->getYearlyChart($userId)),
        ];
    }

    private function getWeeklyRevenueChart($userId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = Booking::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereDate('completed_at', $date)
                ->with('service')
                ->get()
                ->sum('service.price');
            $data[] = $revenue;
        }
        return $data;
    }

    private function getMonthlyChart($userId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = Booking::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereDate('completed_at', $date)
                ->with('service')
                ->get()
                ->sum('service.price');
            $data[] = $revenue;
        }
        return $data;
    }

    private function getYearlyChart($userId): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = Booking::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereMonth('completed_at', $date->month)
                ->whereYear('completed_at', $date->year)
                ->with('service')
                ->get()
                ->sum('service.price');
            $data[] = $revenue;
        }
        return $data;
    }
}
