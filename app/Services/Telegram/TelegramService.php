<?php

namespace App\Services\Telegram;

use App\Handlers\Telegram\CallBackQueryHandler;
use App\Handlers\Telegram\MessageHandler;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    public function handle($request)
    {
        $update = Telegram::getWebhookUpdate();

        if ($update->getMessage()) {
            app(MessageHandler::class)->handle(($update->getMessage()));
        }

        if ($update->getCallbackQuery()) {
            app(CallBackQueryHandler::class)->handle($update->getCallbackQuery());
        }
    }
}
