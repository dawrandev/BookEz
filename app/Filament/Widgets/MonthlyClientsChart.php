<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MonthlyClientsChart extends ChartWidget
{
    protected static ?string $heading = 'Ежемесячное количество клиентов (последние 12 месяцев)';
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $userId = Auth::id();
        $data = [];
        $labels = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $clientsCount = Booking::where('user_id', $userId)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->distinct('client_id')
                ->count('client_id');

            $data[] = $clientsCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Количество клиентов',
                    'data' => $data,
                    'borderColor' => '#00eaffff',
                    'backgroundColor' => 'rgba(15, 197, 194, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#00eaffff',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
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
                        'stepSize' => 1,
                        'callback' => 'function(value) { return Math.floor(value) === value ? value : null; }',
                    ],
                ],
            ],
        ];
    }
}
