<?php

namespace App\Services\Telegram;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

class SocialNetworksService
{
    public function showSpecialistSocials(int $chatId, int $specialistId): void
    {
        $specialist = $this->getSpecialistById($specialistId);

        if (!$specialist) {
            $this->sendmessage($chatId, "Specialist tabılmadı. ID: {$specialistId}");
            return;
        }
        if (!$specialist->socials) {
            $this->sendMessage($chatId, 'Specialist social tarmaqlardı kiritpegen');
            return;
        }
        $this->sendSocials($chatId, $specialist);
    }

    private function sendSocials(int $chatId, User $specialist): void
    {
        try {
            $buttons = [];

            foreach ($specialist->socials as $social) {
                $buttons[] = [
                    [
                        'text' => $social['platform'],
                        'url' => $social['url']
                    ]
                ];
            }

            $buttons[] = [
                [
                    'text' => '🔙 Artqa',
                    'callback_data' => "specialist_{$specialist->id}"
                ]
            ];

            $replyMarkup = json_encode([
                'inline_keyboard' => $buttons
            ]);

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "🌐 Social Tarmaqlar:\n",
                'reply_markup' => $replyMarkup
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function getSpecialistById(int $specialistId)
    {
        return User::role('specialist')->find($specialistId);
    }

    private function sendMessage(int $chatId, string $text): void
    {
        try {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $text
            ]);
        } catch (Throwable $th) {
            throw $th;
        }
    }
}
