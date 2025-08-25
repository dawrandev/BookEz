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

        // Umumiy reyting
        $avgRating = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->avg('rating');

        $ratingCount = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->count();

        // Bugungi daromad (completed bookinglardan)
        $todayRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->with('service')
            ->get()
            ->sum('service.price');

        // Kechagi daromad (o'zgarish uchun)
        $yesterdayRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('completed_at', today()->subDay())
            ->with('service')
            ->get()
            ->sum('service.price');

        // Ushbu oy daromadi (completed bookinglardan)
        $monthRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->with('service')
            ->get()
            ->sum('service.price');

        // O'tgan oy daromadi (o'zgarish uchun)
        $lastMonthRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->subMonth()->month)
            ->whereYear('completed_at', now()->subMonth()->year)
            ->with('service')
            ->get()
            ->sum('service.price');

        // Ushbu yil daromadi
        $yearRevenue = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereYear('completed_at', now()->year)
            ->with('service')
            ->get()
            ->sum('service.price');

        // Umumiy mijozlar
        $totalClients = Booking::where('user_id', $userId)
            ->distinct('client_id')
            ->count('client_id');

        // Ushbu oydagi yangi mijozlar
        $newClientsThisMonth = Booking::where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->distinct('client_id')
            ->count('client_id');

        return [
            Stat::make('Umumiy Reyting', $avgRating ? number_format($avgRating, 1) . '/5' : 'Hozircha yo\'q')
                ->description($ratingCount > 0 ? "{$ratingCount} ta baho" : 'Hech qanday baho yo\'q')
                ->descriptionIcon($avgRating >= 4.5 ? 'heroicon-m-star' : 'heroicon-m-star')
                ->color($avgRating >= 4.5 ? 'success' : ($avgRating >= 4 ? 'warning' : 'danger'))
                ->chart(array_fill(0, 7, $avgRating ?? 0)),

            Stat::make('Bugungi Daromad', number_format($todayRevenue, 0))
                ->description('Bugun tugallangan xizmatlardan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getWeeklyRevenueChart($userId)),

            Stat::make('Ushbu Oy Daromadi', number_format($monthRevenue, 0))
                ->description(now()->format('F') . ' oyida topilgan daromad')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary')
                ->chart($this->getMonthlyChart($userId)),

            Stat::make('Ushbu Yil Daromadi', number_format($yearRevenue, 0))
                ->description("Jami {$totalClients} ta mijoz")
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
