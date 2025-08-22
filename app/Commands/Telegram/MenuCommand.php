<?php

namespace App\Commands\Telegram;

use App\Models\Client;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class MenuCommand extends Command
{
    protected string $name = 'main_menu';

    protected string $description = 'Menu';

    protected string $usage = '/menu';

    public function handle()
    {
        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        $client = Client::where('telegram_chat_id', $chatId)->first();

        $keyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '👨‍⚕️ Specialistler', 'callback_data' => 'specialists'],
                    ['text' => '📂 Kategoriyalar', 'callback_data' => 'categories'],
                ],
                [
                    ['text' => '📖Bronlarım', 'callback_data' => "my_bookings_{$client->id}"]
                ]
            ]

        ]);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => '🏠 Bas menyu',
            'reply_markup' => $keyboard
        ]);
    }
}
