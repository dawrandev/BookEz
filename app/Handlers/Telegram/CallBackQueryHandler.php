<?php

namespace App\Handlers\Telegram;

use App\Services\Telegram\CategoryService;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class CallbackQueryHandler
{
    protected $callbackQuery;
    protected $chatId;
    protected $data;

    public function __construct(protected CategoryService $categoryService)
    {

        $update = Telegram::getWebhookUpdate();
        $this->callbackQuery = $update->getCallbackQuery();
        $this->chatId = $this->callbackQuery->getMessage()->getChat()->getId();
        $this->data = $this->callbackQuery->getData();
    }

    public function handle()
    {
        try {
            $this->answerCallbackQuery();

            if (str_starts_with($this->data, 'category_')) {
                $this->handleCategoryCallback();
                return;
            }

            $this->handleUnknownCallback();
        } catch (Exception $e) {
            Log::error('CallbackQueryHandler error: ' . $e->getMessage());
            $this->sendErrorMessage();
        }
    }

    /**
     * Handle category selection callback
     */
    private function handleCategoryCallback(): void
    {
        $categoryId = (int) str_replace('category_', '', $this->data);
        $this->categoryService->handleCategorySelection($this->chatId, $categoryId);
    }

    /**
     * Handle unknown callback data
     */
    private function handleUnknownCallback(): void
    {
        Telegram::sendMessage([
            'chat_id' => $this->chatId,
            'text' => 'Noma\'lum buyruq.'
        ]);
    }

    /**
     * Answer callback query (tugma bosilganligini tasdiqlash)
     */
    private function answerCallbackQuery(): void
    {
        Telegram::answerCallbackQuery([
            'callback_query_id' => $this->callbackQuery->getId(),
            'text' => '' // Yoki qisqa xabar
        ]);
    }

    /**
     * Send error message
     */
    private function sendErrorMessage(): void
    {
        Telegram::sendMessage([
            'chat_id' => $this->chatId,
            'text' => 'Xatolik yuz berdi. Qaytadan urinib ko\'ring.'
        ]);
    }
}
