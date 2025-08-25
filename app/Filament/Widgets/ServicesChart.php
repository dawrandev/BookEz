<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class ServicesChart extends ChartWidget
{
    protected static ?string $heading = 'Самые популярные услуги'; // Eng Mashhur Xizmatlar
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $userId = auth()->id();

        $services = Service::where('user_id', $userId)
            ->withCount(['bookings' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->having('bookings_count', '>', 0)
            ->orderBy('bookings_count', 'desc')
            ->take(5)
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $services->pluck('bookings_count')->toArray(),
                    'backgroundColor' => [
                        '#EF4444',
                        '#F59E0B',
                        '#10B981',
                        '#3B82F6',
                        '#8B5CF6',
                    ],
                ],
            ],
            'labels' => $services->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
        ];
    }
}
