<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MonthlyAllClientsChart extends ChartWidget
{
    protected static ?string $heading = 'Клиенты, добавленные по месяцам';

    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = '2';

    protected function getData(): array
    {
        // Данные за последние 12 месяцев
        $data = Client::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $months = [];
        $counts = [];

        // Заполняем последние 12 месяцев
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('Y-m');
            $monthName = $date->format('M Y');

            $months[] = $monthName;

            // Есть ли данные за этот месяц?
            $found = $data->first(function ($item) use ($date) {
                return $item->year == $date->year && $item->month == $date->month;
            });

            $counts[] = $found ? $found->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Количество клиентов',
                    'data' => $counts,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
