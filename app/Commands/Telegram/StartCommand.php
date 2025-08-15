<?php

namespace App\Commands\Telegram;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Start command';
    protected $chatId;

    public function handle()
    {
        try {
            if ($this->update->getMessage()) {
                $this->chatId = $this->update->getMessage()->getChat()->getId();
            } elseif ($this->update->getCallbackQuery()) {
                $this->chatId = $this->update->getCallbackQuery()->getMessage()->getChat()->getId();
            }

            Cache::put("register_step_$this->chatId", 'ask_full_name', 300);

            $this->replyWithMessage([
                'text' => 'Assalawma Aleykum! Dizimnen ótiw ushın iltimas tolıq atıńız hám familiyańızdı kiritiń'
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            $this->telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => 'Qátelik júz berdi. Iltimas qaytadan urınıp kóriń'
            ]);
        }
    }
}
