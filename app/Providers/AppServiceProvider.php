<?php

namespace App\Providers;

use App\Commands\Telegram\FallbackCommand;
use App\Commands\Telegram\HelpCommand;
use App\Commands\Telegram\StartCommand;
use App\Models\Booking;
use App\Notifications\TelegramNotificationService; // To'g'ri namespace
use App\Observers\BookingObserver;
use App\Services\Telegram\LocationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Telegram\Bot\Laravel\Facades\Telegram;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TelegramNotificationService::class);
        $this->app->bind(LocationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Telegram::addCommands([
            StartCommand::class,
            HelpCommand::class,
            FallbackCommand::class
        ]);

        Booking::observe(BookingObserver::class);
    }
}
