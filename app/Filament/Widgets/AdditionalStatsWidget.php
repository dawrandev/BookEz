<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdditionalStatsWidget extends BaseWidget
{

    protected function getStats(): array
    {
        $userId = Auth::id();

        $avgServicePrice = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->with('service')
            ->get()
            ->avg('service.price');

        $mostExpensiveService = Service::where('user_id', $userId)
            ->whereHas('bookings', fn($q) => $q->where('status', 'completed'))
            ->orderBy('price', 'desc')
            ->first();

        $repeatCustomers = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->select('client_id')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $totalBookings = Booking::where('user_id', $userId)->count();
        $canceledBookings = Booking::where('user_id', $userId)
            ->where('status', 'canceled')
            ->count();
        $cancellationRate = $totalBookings > 0 ? ($canceledBookings / $totalBookings) * 100 : 0;

        return [
            Stat::make('Средняя Цена Услуги', $avgServicePrice ? number_format($avgServicePrice, 0) : '0')
                ->description('Среднее завершённых услуг')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info')
                ->chart(array_fill(0, 7, $avgServicePrice ?? 0)),

            Stat::make('Самая Дорогая Услуга', $mostExpensiveService ? number_format($mostExpensiveService->price, 0) : '0')
                ->description($mostExpensiveService ? $mostExpensiveService->name : 'Услуга отсутствует')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('Повторные Клиенты', $repeatCustomers)
                ->description('Клиенты пришедшие 2+ раза')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('Отмена', number_format($cancellationRate, 1) . '%')
                ->description("{$canceledBookings}/{$totalBookings} бронирований")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($cancellationRate < 10 ? 'success' : ($cancellationRate < 20 ? 'warning' : 'danger')),
        ];
    }
}
