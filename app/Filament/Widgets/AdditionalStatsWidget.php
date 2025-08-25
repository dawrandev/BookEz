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

        // O'rtacha xizmat narxi (completed bookings)
        $avgServicePrice = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->with('service')
            ->get()
            ->avg('service.price');

        // Eng qimmat xizmat
        $mostExpensiveService = Service::where('user_id', $userId)
            ->whereHas('bookings', fn($q) => $q->where('status', 'completed'))
            ->orderBy('price', 'desc')
            ->first();

        // Takroriy mijozlar (2 marta yoki ko'proq kelganlar)
        $repeatCustomers = Booking::where('user_id', $userId)
            ->where('status', 'completed')
            ->select('client_id')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // Bekor qilinish foizi
        $totalBookings = Booking::where('user_id', $userId)->count();
        $canceledBookings = Booking::where('user_id', $userId)
            ->where('status', 'canceled')
            ->count();
        $cancellationRate = $totalBookings > 0 ? ($canceledBookings / $totalBookings) * 100 : 0;

        return [
            Stat::make('O\'rtacha Xizmat Narxi', $avgServicePrice ? number_format($avgServicePrice, 0) : '0')
                ->description('Tugallangan xizmatlar o\'rtachasi')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info')
                ->chart(array_fill(0, 7, $avgServicePrice ?? 0)),

            Stat::make('Eng Qimmat Xizmat', $mostExpensiveService ? number_format($mostExpensiveService->price, 0) : '0')
                ->description($mostExpensiveService ? $mostExpensiveService->name : 'Xizmat yo\'q')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('Takroriy Mijozlar', $repeatCustomers)
                ->description('2+ marta kelgan mijozlar')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('Bekor Qilinish', number_format($cancellationRate, 1) . '%')
                ->description("{$canceledBookings}/{$totalBookings} booking")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($cancellationRate < 10 ? 'success' : ($cancellationRate < 20 ? 'warning' : 'danger')),
        ];
    }
}
