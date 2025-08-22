<?php

namespace App\Services\Telegram;

use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramCommandService
{
    public function registerDefaultCommands(): void
    {
        $commands = [
            ['command' => 'start',     'description' => 'Bottı iske túsiriw'],
            ['command' => 'help',      'description' => 'Járdem'],
            ['command' => 'fallback',  'description' => 'Belgisiz buyruq'],
            ['command' => 'main_menu', 'description' => 'Menyu']
        ];

        Telegram::setMyCommands([
            'commands' => $commands,
        ]);
    }
}
