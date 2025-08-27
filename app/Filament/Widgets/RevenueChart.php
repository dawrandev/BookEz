<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Еженедельный доход';
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $userId = 1;
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');

            $bookings = Booking::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereDate('updated_at', $date->format('Y-m-d'))
                ->with('service')
                ->get();

            $revenue = $bookings->sum(function ($booking) {
                return $booking->service ? $booking->service->price : 10000;
            });

            $data[] = (float) $revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Доход',
                    'data' => $data,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
