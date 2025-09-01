<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class SearchService
{
    public function __construct(protected UserService $userService)
    {
        //
    }

    public function promptSearch(int $chatId): void
    {
        cache()->put("search_state_{$chatId}", true, now()->addMinutes(5));

        $text = "🔍 <b>Izlew</b>\n\n";
        $text .= "Siz tómendegiler boyınsha izley alasız:\n";
        $text .= "• 👨‍⚕️ Specialist atı\n";
        $text .= "• 📂 Kategoriya atı\n";
        $text .= "• 🛠 Xizmet atı\n";
        $text .= "• 📞 Telefon nomeri\n\n";
        $text .= "Izlew sózin kiritiń:";

        $keyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '❌ Bekor qılıw', 'callback_data' => 'main_menu']
                ]
            ]
        ]);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'HTML'
        ]);
    }

    public function handleSearchQuery(int $chatId, string $query): void
    {
        try {
            cache()->forget("search_state_{$chatId}");

            $query = trim($query);

            if (strlen($query) < 2) {
                $this->sendMessage($chatId, '🔎 Iltimas keminde 2 hárip kiritiń');
                return;
            }

            $specialists = $this->searchSpecialists($query);

            if ($specialists->isEmpty()) {
                $this->sendSearchNotFound($chatId, $query);
                return;
            }

            $this->showSearchResults($chatId, $specialists, $query);
        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            $this->sendMessage($chatId, '❌ Izlew waqtında qátelik júz berdi. Qayta urınıp kóriń.');
        }
    }

    public function searchSpecialists(string $query): Collection
    {
        return User::role('specialist')
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($query) {
                        $categoryQuery->where('name', 'LIKE', "%{$query}%");
                    })
                    ->orWhereHas('services', function ($serviceQuery) use ($query) {
                        $serviceQuery->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%");
                    });
            })
            ->with(['category', 'services'])
            ->get();
    }

    private function showSearchResults(int $chatId, Collection $specialists, string $query): void
    {
        $text = "🔍 <b>'{$query}' boyınsha izlew nátiyjeleri:</b>\n";
        $text .= "Tabılǵan specialistlar sanı: {$specialists->count()}\n\n";

        $keyboard = $this->userService->specialistBuilderKeyboard($specialists);

        $keyboardArray = json_decode($keyboard, true);
        $keyboardArray['inline_keyboard'][] = [
            ['text' => '🔍 Qayta izlew', 'callback_data' => 'search'],
            ['text' => '🏠 Menyu', 'callback_data' => 'main_menu']
        ];

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($keyboardArray),
            'parse_mode' => 'HTML'
        ]);
    }

    private function sendSearchNotFound(int $chatId, string $query): void
    {
        $text = "❌ <b>'{$query}' boyınsha heshqanday specialist tabılmadı</b>\n\n";
        $text .= "Máslahatlar:\n";
        $text .= "• Jazıwdaģı qáteliklerdi tekserip kóriń\n";
        $text .= "• Basqa hárip kombiniatsiyaların sınap kóriń\n";
        $text .= "• Kategoriya atın jazıp kóriń\n";

        $keyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '🔍 Qayta izlew', 'callback_data' => 'search']
                ],
                [
                    ['text' => '👥 Barlıq specialistler', 'callback_data' => 'specialists'],
                    ['text' => '📂 Kategoriyalar', 'callback_data' => 'categories']
                ],
                [
                    ['text' => '🏠 Menyu', 'callback_data' => 'main_menu']
                ]
            ]
        ]);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard,
            'parse_mode' => 'HTML'
        ]);
    }

    public function isSearchModeActive(int $chatId): bool
    {
        return cache()->has("search_state_{$chatId}");
    }

    private function sendMessage(int $chatId, string $text): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }

    public function searchByCategory(string $categoryName): Collection
    {
        return User::role('specialist')
            ->where('status', 'active')
            ->whereHas('category', function ($query) use ($categoryName) {
                $query->where('name', 'LIKE', "%{$categoryName}%");
            })
            ->with(['category', 'services'])
            ->get();
    }

    public function searchByService(string $serviceName): Collection
    {
        return User::role('specialist')
            ->where('status', 'active')
            ->whereHas('services', function ($query) use ($serviceName) {
                $query->where('name', 'LIKE', "%{$serviceName}%")
                    ->orWhere('description', 'LIKE', "%{$serviceName}%");
            })
            ->with(['category', 'services'])
            ->get();
    }
}
