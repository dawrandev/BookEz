<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class AdminPanelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerNavigation(function () {
                return [
                    \App\Filament\Resources\BookingResource::class => 1,
                    \App\Filament\Resources\ServiceResource::class => 2,
                    \App\Filament\Resources\UserResource::class => 3,
                    \App\Filament\Resources\ClientResource::class => 4,
                    \App\Filament\Resources\ProfileResource::class => 5,
                    \App\Filament\Resources\CompletedBookingResource::class => 6,
                    \App\Filament\Resources\ScheduleResource::class => 7,
                    \App\Filament\Resources\UserResource::class => 8,
                ];
            });
        });
    }
}
