<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Telegram\TelegramCommandService;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramSetupCommands extends Command
{
    protected $signature = 'telegram:setup-commands';
    protected $description = 'Telegram bot komandalarini ro‘yxatdan o‘tkazish';

    public function handle(TelegramCommandService $service): void
    {
        $service->registerDefaultCommands();

        $current = Telegram::getMyCommands();
        $this->info('✅ Telegram commands are registered');
    }
}
