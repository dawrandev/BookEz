<?php

namespace App\Handlers\Telegram;

use Telegram\Bot\Laravel\Facades\Telegram;

class CallBackQueryHandler
{
    public function handle($callbackQuery)
    {
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $data   = $callbackQuery->getData();

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => "Siz tanladingiz: {$data}"
        ]);
    }
}
