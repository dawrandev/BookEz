<?php

namespace App\Handlers\Telegram;

use App\Services\Telegram\ClientService;
use App\Services\Telegram\SearchService;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class MessageHandler
{
    protected $message;
    protected $chatId;
    protected $text;

    public function __construct(
        protected ClientService $clientService,
        protected SearchService $searchService
    ) {
        $update = Telegram::getWebhookUpdate();
        $this->message = $update->getMessage();
        $this->chatId = $this->message->getChat()->getId();
        $this->text = trim($this->message->getText() ?? '');
    }

    public function handle()
    {
        try {
            if ($this->message->getContact()) {
                $this->handleContactMessage();
                return;
            }

            if (str_starts_with($this->text, '/')) {
                return;
            }

            if ($this->searchService->isSearchModeActive($this->chatId)) {
                Log::info("Search mode active for chat: {$this->chatId}, query: {$this->text}");
                $this->searchService->handleSearchQuery($this->chatId, $this->text);
                Log::info("Search handled, returning...");
                return;
            }

            $step = $this->clientService->getCurrentStep($this->chatId);

            if ($step === 'ask_full_name') {
                $this->clientService->handleFullNameStep($this->chatId, $this->text);
                return;
            }

            $this->sendDefaultResponse();
        } catch (Exception $e) {
            Log::error('MessageHandler error: ' . $e->getMessage());
            $this->sendErrorMessage();
        }
    }

    private function handleContactMessage(): void
    {
        $contact = $this->message->getContact();
        $phone = $contact->getPhoneNumber();
        $telegramId = $this->message->getFrom()->getId();
        $username = $this->message->getFrom()->getUsername();

        $this->clientService->handlePhoneStep($this->chatId, $phone, $telegramId, $username);
    }

    private function sendDefaultResponse(): void
    {
        Telegram::sendMessage([
            'chat_id' => $this->chatId,
            'text' => "Siz text jiberdińiz: {$this->text}"
        ]);
    }

    private function sendErrorMessage(): void
    {
        Telegram::sendMessage([
            'chat_id' => $this->chatId,
            'text' => 'Qátelik júz berdi. Iltimas qaytadan urınıp kóriń'
        ]);
    }
}
