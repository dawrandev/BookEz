<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdditionalStatsWidget;
use App\Filament\Widgets\AdminStatsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\ServicesChart;
use App\Filament\Widgets\LatestBookings;
use App\Filament\Widgets\MonthlyAllClientsChart;
use App\Filament\Widgets\MonthlyClientsChart;
use App\Filament\Widgets\MonthlyRevenueChart;
use App\Filament\Widgets\MonthlySpecialistsChart;
use App\Filament\Widgets\SpecialistStatsWidget;
use App\Filament\Widgets\SpecialistBookingsWidget;
use App\Filament\Widgets\SpecialistsByCategoryChart;
use App\Filament\Widgets\SpecialistScheduleWidget;
use App\Filament\Widgets\SubscriptionStatsWidget;
use App\Models\Subscription;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Инфопанель';
    protected static ?string $title = 'Инфопанель';

    public function getWidgets(): array
    {
        $user = Auth::user();

        if ($user->hasRole('specialist')) {
            return [
                StatsOverviewWidget::class,
                AdditionalStatsWidget::class,
                MonthlyRevenueChart::class,
                [
                    'widget' => RevenueChart::class,
                    'columnSpan' => 6,
                ],
                [
                    'widget' => ServicesChart::class,
                    'columnSpan' => 6,
                ],
                MonthlyClientsChart::class,
            ];
        }

        if ($user->hasRole('admin')) {
            return [
                AdminStatsWidget::class,
                SubscriptionStatsWidget::class,
                MonthlyAllClientsChart::class,
                MonthlySpecialistsChart::class,
                SpecialistsByCategoryChart::class,
            ];
        }
    }

    public function getColumns(): int | string | array
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return [
                'sm' => 1,
                'md' => 2,
                'xl' => 4,
            ];
        }

        if ($user->hasRole('specialist')) {
            return [
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ];
        }

        return [
            'sm' => 1,
            'md' => 2,
        ];
    }

    public function getTitle(): string
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return 'Админ панель';
        }

        if ($user->hasRole('specialist')) {
            return 'Специалист панели';
        }

        return 'Инфопанель';
    }
}
