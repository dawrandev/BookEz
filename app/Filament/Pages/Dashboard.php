<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdditionalStatsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\ServicesChart;
use App\Filament\Widgets\LatestBookings;
use App\Filament\Widgets\MonthlyClientsChart;
use App\Filament\Widgets\MonthlyRevenueChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Инфо панель';
    protected static ?string $title = 'Инфопанель';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            AdditionalStatsWidget::class,
            MonthlyRevenueChart::class,
            RevenueChart::class,
            ServicesChart::class,
            MonthlyClientsChart::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }
}
