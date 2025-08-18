<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Collection;
use Telegram\Bot\Laravel\Facades\Telegram;

class UserService
{
    public function showSpecialists(int $chatId, string $data)
    {
        $categoryId = null;
        if (str_starts_with($data, 'specialists_')) {
            $categoryId = (int) substr($data, strlen('specialists_'));
        }

        $specialists = $this->getActiveSpecialists($categoryId);

        if ($specialists->isEmpty()) {
            $this->sendMessage($chatId, 'Hazirshe specialistlar joq');
            return;
        }

        $keyboard = $this->specialistBuilderKeyboard($specialists);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $categoryId
                ? "📂 Kategoriya boyınsha specialistler:"
                : "👨‍⚕️ Barlıq specialistler:",
            'reply_markup' => $keyboard
        ]);
    }

    public function getActiveSpecialists(int $categoryId = null)
    {
        $query = User::role('specialist')
            ->where('status', 'active');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->get();
    }


    public function specialistBuilderKeyboard(Collection $specialists): string
    {
        $keyboard = [];
        $buttonsPerRow = 2;
        $buttons = [];

        foreach ($specialists as $specialist) {
            $buttons[] = [
                'text' => $specialist->name,
                'callback_data' => "specialist_{$specialist->id}"
            ];

            if (count($buttons) >= $buttonsPerRow) {
                $keyboard[] = $buttons;
                $buttons = [];
            }
        }

        if (!empty($buttons)) {
            $keyboard[] = $buttons;
        }
        return json_encode([
            'inline_keyboard' => $keyboard
        ]);
    }

    public function handleSpecialistSection(int $chatId, int $specialistId): void
    {
        $specialist = $this->getSpecialistById($specialistId);

        if (!$specialist) {
            $this->sendMessage($chatId, 'Specialist tabılmadı');
            return;
        }

        $this->showSpecialistDetails($chatId, $specialist);
    }

    public function showSpecialistDetails(int $chatId, User $specialist): void
    {
        $text = "👨‍⚕️ Specialist haqqında:\n\n"
            . "📝 Atı: {$specialist->name}\n"
            . "📞 Telefon: " . ($specialist->phone ?? 'joq') . "\n"
            . "ℹ️ Tavsif: " . ($specialist->description ?? 'kiritilmegen');

        $keyboards = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '➕Bron qılıw', 'callback_data' => "specialist_services_{$specialist->id}"]
                ],
                [
                    ['text' => '📖Bronlardı kóriw', 'callback_data' => "specialist_bookings_{$specialist->id}"],
                    ['text' => '📍Lokatsiya', 'callback_data' => "specialist_{$specialist->id}"]
                ],
                [
                    ['text' => '🔙Artqa', 'callback_data' => "specialists"]
                ]
            ]
        ]);


        Telegram::sendMessage([
            'text' => $text,
            'chat_id' => $chatId,
            'reply_markup' => $keyboards
        ]);
    }

    public function getSpecialistById(int $specialistId): ?User
    {
        return User::where('id', $specialistId)->first();
    }

    private function sendMessage(int $chatId, string $text)
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
