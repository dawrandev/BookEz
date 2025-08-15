<?php

namespace App\Commands\Telegram;

use App\Services\Telegram\ClientService;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Start command';

    public function __construct(protected ClientService $clientService)
    {
        // 
    }

    public function handle()
    {
        try {
            $chatId = $this->getChatId();

            if ($this->clientService->isClientRegistered($chatId)) {
                $client = $this->clientService->getClientByChatId($chatId);
                $this->replyWithMessage([
                    'text' => "Assalawma Aleykum, {$client->full_name}! Siz dizimnen ótkensiz."
                ]);
                return;
            }

            $this->clientService->startRegistration($chatId);
        } catch (Exception $e) {
            Log::error('StartCommand error: ' . $e->getMessage());
            $this->replyWithMessage([
                'text' => 'Qátelik júz berdi. Iltimas qaytadan urınıp kóriń'
            ]);
        }
    }

    private function getChatId(): int
    {
        if ($this->update->getMessage()) {
            return $this->update->getMessage()->getChat()->getId();
        } elseif ($this->update->getCallbackQuery()) {
            return $this->update->getCallbackQuery()->getMessage()->getChat()->getId();
        }

        throw new Exception('Cannot determine chat ID');
    }
}
