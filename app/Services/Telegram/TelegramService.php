<?php

namespace App\Services\Telegram;

use App\Handlers\Telegram\CallBackQueryHandler;
use App\Handlers\Telegram\MessageHandler;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    public function handle($request)
    {
        try {
            $update = Telegram::getWebhookUpdate();

            if ($update->getCallbackQuery()) {
                app(CallBackQueryHandler::class)->handle($update->getCallbackQuery());
                return;
            }

            if ($update->getMessage()) {
                app(MessageHandler::class)->handle($update->getMessage());
                return;
            }
        } catch (\Exception $e) {
            Log::error('TelegramService error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
        }
    }
}
