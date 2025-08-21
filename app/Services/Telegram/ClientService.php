<?php

namespace App\Services\Telegram;

use App\Models\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class ClientService
{
    public function __construct(protected CategoryService $categoryService)
    {
        // 
    }
    public function startRegistration(int $chatId): void
    {
        Cache::put("register_step_$chatId", 'ask_full_name', 300);

        $this->sendMessage($chatId, 'Assalawma Aleykum! Dizimnen ótiw ushın iltimas tolıq atıńız hám familiyańızdı kiritiń');
    }

    public function handleFullNameStep(int $chatId, string $fullName): void
    {
        Cache::put("register_full_name_$chatId", $fullName, 300);
        Cache::put("register_step_$chatId", 'ask_phone', 300);

        $this->requestPhoneNumber($chatId);
    }

    public function handlePhoneStep(int $chatId, string $phone, int $telegramId, ?string $username): void
    {
        $fullName = Cache::get("register_full_name_$chatId");

        try {
            Client::firstOrCreate([
                'telegram_chat_id' => $chatId,
                'telegram_id' => $telegramId,
                'username' => $username,
                'full_name' => $fullName,
                'phone' => $phone
            ]);

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => '✅Siz tabıslı dizimnen óttińiz',
                'reply_markup' => json_encode([
                    'remove_keyboard' => true
                ])
            ]);

            $this->clearRegistrationCache($chatId);

            $this->showMainMenu($chatId);
        } catch (\Exception $e) {
            Log::error("Client creation failed: " . $e->getMessage());
            $this->sendMessage($chatId, 'Dizimde qatelik júz berdi. Iltimas qaytadan urınıp kóriń');
        }
    }

    public function getCurrentStep(int $chatId): ?string
    {
        return Cache::get("register_step_$chatId");
    }

    public function isClientRegistered(int $chatId): bool
    {
        return Client::where('telegram_chat_id', $chatId)->exists();
    }

    public function getClientByChatId(int $chatId): ?Client
    {
        return Client::where('telegram_chat_id', $chatId)->first();
    }

    private function clearRegistrationCache(int $chatId): void
    {
        Cache::forget("register_step_$chatId");
        Cache::forget("register_full_name_$chatId");
    }

    private function requestPhoneNumber(int $chatId): void
    {
        $keyboard = json_encode([
            'keyboard' => [
                [
                    [
                        'text' => '📱 Telefon nomer jiberiw',
                        'request_contact' => true
                    ]
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => '📞Telefon nomerińizdi kiritiń',
            'reply_markup' => $keyboard
        ]);
    }

    public function showCategories(int $chatId): void
    {
        $categoryService = $this->categoryService->showCategoriesToUser($chatId);
    }

    public function showMainMenu(int $chatId)
    {
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

    private function sendMessage(int $chatId, string $text): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
