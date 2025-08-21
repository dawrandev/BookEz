<?php

namespace App\Services\Telegram;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class LocationService
{
    public function sendSpecialistLocation(int $chatId, int $specialistId): void
    {
        $specialist = $this->getSpecialistById($specialistId);

        if (!$specialist) {
            $this->sendMessage($chatId, "Specialist tabılmadı. ID: {$specialistId}");
            return;
        }

        if (!$specialist->location) {
            $this->sendMessage($chatId, 'Specialist lokatsiyası kiritilmegen');
            return;
        }

        if (is_string($specialist->location)) {
            $location = json_decode($specialist->location, true);
        } else {
            $location = $specialist->location;
        }

        if (!isset($location['lat']) || !isset($location['lng'])) {
            $this->sendMessage($chatId, 'Lokatsiya maǵlıwmatları nadurıs');
            return;
        }

        $this->sendLocation($chatId, $location['lat'], $location['lng'], $specialist);
    }

    private function sendLocation(int $chatId, float $latitude, float $longitude, User $specialist): void
    {
        try {
            $replyMarkup = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '🔙 Artqa', 'callback_data' => "specialist_{$specialist->id}"]
                    ]
                ]
            ]);

            Telegram::sendLocation([
                'chat_id' => $chatId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'reply_markup' => $replyMarkup
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function getSpecialistById(int $specialistId): ?User
    {
        return User::with('category')->where('id', $specialistId)->first();
    }

    private function sendMessage(int $chatId, string $text, string $parseMode = null): void
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text
        ];

        if ($parseMode) {
            $params['parse_mode'] = $parseMode;
        }

        Telegram::sendMessage($params);
    }
}
