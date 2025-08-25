<?php

namespace App\Services\Telegram;

use App\Models\Service;
use App\Models\User;
use Telegram\Bot\Laravel\Facades\Telegram;

class SpecialistService
{
    public function handleSpecialistServicesSection(int $chatId, int $specialistId): void
    {
        $services = $this->getServicesBySpecialistId($specialistId);

        $categoryId = User::where('id', $specialistId)->value('category_id');

        if ($services->isEmpty()) {
            $this->sendMessage($chatId, 'Xizmetler kórsetilmegen');
            return;
        }

        $keyboard = [];
        foreach ($services as $service) {
            $keyboard[] = [
                ['text' => $service->name, 'callback_data' => "service_{$service->id}"]
            ];
        }

        $keyboard[] = [['text' => '🔙Artqa', 'callback_data' => "specialist_{$specialistId}"]];

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => 'Xizmetler:',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }


    public function getServicesBySpecialistId($specialistId)
    {
        return Service::where('user_id', $specialistId)
            ->where('status', 'active')
            ->get();
    }



    private function sendMessage($chatId, $text)
    {
        Telegram::sendMessage([
            'text' => $text,
            'chat_id' => $chatId
        ]);
    }
}
