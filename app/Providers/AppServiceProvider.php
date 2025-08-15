<?php

namespace App\Providers;

use App\Commands\Telegram\FallbackCommand;
use App\Commands\Telegram\HelpCommand;
use App\Commands\Telegram\StartCommand;
use Illuminate\Support\ServiceProvider;
use Telegram\Bot\Laravel\Facades\Telegram;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}
