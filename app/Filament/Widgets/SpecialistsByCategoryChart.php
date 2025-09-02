<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SpecialistsByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Специалисты по категориям';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Category::withCount(['users as specialists_count' => function ($query) {
            $query->role('specialist');
        }])
            ->having('specialists_count', '>', 0)
            ->orderBy('specialists_count', 'desc')
            ->get();

        $labels = $data->pluck('name')->toArray();
        $counts = $data->pluck('specialists_count')->toArray();

        $colors = [
            'rgb(239, 68, 68)',   // красный
            'rgb(245, 158, 11)',  // янтарный
            'rgb(34, 197, 94)',   // зеленый
            'rgb(59, 130, 246)',  // синий
            'rgb(147, 51, 234)',  // фиолетовый
            'rgb(236, 72, 153)',  // розовый
            'rgb(14, 165, 233)',  // голубой
            'rgb(168, 85, 247)',  // пурпурный
            'rgb(234, 88, 12)',   // оранжевый
            'rgb(6, 182, 212)',   // циан
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Количество специалистов',
                    'data' => $counts,
                    'backgroundColor' => array_slice($colors, 0, count($counts)),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
