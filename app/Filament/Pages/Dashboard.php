<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\ServicesChart;
use App\Filament\Widgets\LatestBookings;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Панель управления';
    protected static ?string $title = 'Панель управления';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            RevenueChart::class,
            ServicesChart::class,
            LatestBookings::class,
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
