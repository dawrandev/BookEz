<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Oylik Daromad (so\'nggi 12 oy)';
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $userId = Auth::id();
        $data = [];
        $labels = [];

        // So'nggi 12 oy uchun ma'lumotlarni yig'amiz
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');

            // Agar completed_at mavjud bo'lsa uni ishlatamiz, aks holda updated_at
            $bookings = Booking::where('user_id', $userId)
                ->where('status', 'completed')
                ->where(function ($query) use ($date) {
                    $query->where(function ($q) use ($date) {
                        // completed_at mavjud bo'lsa uni ishlatamiz
                        $q->whereNotNull('completed_at')
                            ->whereMonth('completed_at', $date->month)
                            ->whereYear('completed_at', $date->year);
                    })->orWhere(function ($q) use ($date) {
                        // completed_at yo'q bo'lsa updated_at ishlatamiz
                        $q->whereNull('completed_at')
                            ->whereMonth('updated_at', $date->month)
                            ->whereYear('updated_at', $date->year);
                    });
                })
                ->with('service')
                ->get();

            $revenue = $bookings->sum(function ($booking) {
                return optional($booking->service)->price ?? 10000;
            });

            $data[] = (float) $revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Daromad',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 2,
                    'borderRadius' => 4,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat().format(value); }',
                    ],
                ],
            ],
        ];
    }
}
