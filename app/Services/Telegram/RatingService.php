<?php

namespace App\Services\Telegram;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class RatingService
{
    public function __construct()
    {
        //
    }

    public function handleRatingCallback(int $chatId, string $data): void
    {
        if (str_starts_with($data, 'rating_')) {
            $bookingId = (int) substr($data, strlen('rating_'));
            $this->sendRatingButtons($chatId, $bookingId);
            return;
        }

        if (str_starts_with($data, 'rate_')) {
            $parts = explode('_', $data);
            if (count($parts) >= 3) {
                $bookingId = (int) $parts[1];
                $rating = (int) $parts[2];
                $this->saveRating($chatId, $bookingId, $rating);
            }
            return;
        }

        if (str_starts_with($data, 'skip_rating_')) {
            $bookingId = (int) substr($data, strlen('skip_rating_'));
            $this->skipRating($chatId, $bookingId);
            return;
        }
    }

    public function saveRating(int $chatId, int $bookingId, int $rating): void
    {
        try {
            $booking = Booking::find($bookingId);

            if (!$booking) {
                $this->sendMessage($chatId, "❌ Bron topilmadi");
                return;
            }

            if ($booking->client->telegram_chat_id !== $chatId) {
                $this->sendMessage($chatId, "❌ Siz bu bronni baholay olmaysiz");
                return;
            }

            $booking->addRating($rating);

            Log::info('Rating saved', [
                'booking_id' => $bookingId,
                'rating' => $rating,
                'chat_id' => $chatId
            ]);

            $keyboard = [
                [
                    ['text' => '🏠 Bosh menyu', 'callback_data' => 'main_menu'],
                    ['text' => '🔄 Yangi bron', 'callback_data' => 'specialists']
                ]
            ];

            $message = "✅ Baholaganingiz uchun rahmat!\n\n";
            $message .= "Xizmatimizdan yana foydalanishingizni kutamiz.";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save rating', [
                'booking_id' => $bookingId,
                'rating' => $rating,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);

            $this->sendMessage($chatId, "❌ Baholashda xatolik yuz berdi");
        }
    }

    public function skipRating(int $chatId, int $bookingId): void
    {
        try {
            $keyboard = [
                [
                    ['text' => '🏠 Bosh menyu', 'callback_data' => 'main_menu'],
                    ['text' => '🔄 Yangi bron', 'callback_data' => 'specialists']
                ]
            ];

            $message = "✅ <b>Yaxshi!</b>\n\n";
            $message .= "Vaqtingiz uchun rahmat!\n";
            $message .= "Xizmatlarimizdan yana foydalanishingizni kutamiz.";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);

            Log::info('Rating skipped', ['booking_id' => $bookingId, 'chat_id' => $chatId]);
        } catch (\Exception $e) {
            Log::error('Failed to send skip rating message', [
                'booking_id' => $bookingId,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendRatingButtons(int $chatId, int $bookingId): void
    {
        $keyboard = [
            [['text' => '⭐️', 'callback_data' => "rate_{$bookingId}_1"]],
            [['text' => '⭐️⭐️', 'callback_data' => "rate_{$bookingId}_2"]],
            [['text' => '⭐️⭐️⭐️', 'callback_data' => "rate_{$bookingId}_3"]],
            [['text' => '⭐️⭐️⭐️⭐️', 'callback_data' => "rate_{$bookingId}_4"]],
            [['text' => '⭐️⭐️⭐️⭐️⭐️', 'callback_data' => "rate_{$bookingId}_5"]],
            [['text' => '⏭ O‘tkazib yuborish', 'callback_data' => "skip_rating_{$bookingId}"]],
        ];

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "❓ Xizmatni baholang:",
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
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
