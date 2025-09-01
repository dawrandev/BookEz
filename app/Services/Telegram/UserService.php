<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class UserService
{
    public function __construct(protected LocationService $locationService)
    {
        // 
    }

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

    public function promptSearch(int $chatId, string $data): void
    {
        cache()->put("search_state_{$chatId}", true, now()->addMinutes(5));

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => '🔎 Specialist izlew ushın onıń atın jazıń:'
        ]);
    }

    public function showSpecialistDetails(int $chatId, User $specialist): void
    {
        $categoryId = $specialist->category ? $specialist->category->id : null;

        $text = "👨‍⚕️: <b>{$specialist->name}</b>\n"
            . "<b>{$specialist->category->name}</b>\n"
            . "📞: " . ($specialist->phone ?? 'kiritilmegen') . "\n"
            . "ℹ️: " . ($specialist->description ?? 'kiritilmegen');

        $replyMarkup = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '➕ Bron qılıw', 'callback_data' => "specialist_services_{$specialist->id}"],
                ],
                [
                    ['text' => '🌐 Social Tarmaqlar', 'callback_data' => "socials_{$specialist->id}"],
                    ['text' => '📍 Lokatsiya', 'callback_data' => "specialist_location_{$specialist->id}"]
                ],
                [
                    ['text' => '🔙 Artqa', 'callback_data' => "category_{$categoryId}"]
                ],
            ],
        ]);

        if ($specialist->photo) {
            if (Storage::disk('public')->exists($specialist->photo)) {
                $absPath = Storage::disk('public')->path($specialist->photo);
                $photo   = InputFile::create($absPath, basename($absPath));
            } else {
                $publicUrl = url('storage/' . $specialist->photo);
                $photo     = InputFile::create($publicUrl, basename($specialist->photo));
            }

            Telegram::sendPhoto([
                'chat_id'      => $chatId,
                'photo'        => $photo,
                'caption'      => $text,
                'reply_markup' => $replyMarkup,
                'parse_mode'   => 'HTML',
            ]);

            return;
        }

        Telegram::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'reply_markup' => $replyMarkup,
            'parse_mode'   => 'HTML',
        ]);
    }

    public function getSpecialistById(int $specialistId): ?User
    {
        return User::where('id', $specialistId)->first();
    }

    public function handleSpecialistLocation(int $chatId, string $data): void
    {
        if (str_starts_with($data, 'specialist_location_')) {
            $specialistId = (int) substr($data, strlen('specialist_location_'));
            $this->locationService->sendSpecialistLocation($chatId, $specialistId);
        }
    }

    private function sendMessage(int $chatId, string $text)
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
