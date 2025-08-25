<?php

namespace App\Services\Telegram;

use App\Models\Booking;
use App\Notifications\TelegramNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class RatingService
{
    public function __construct(
        protected TelegramNotificationService $telegramNotificationService
    ) {
        //
    }

    public function handleRatingCallback(int $chatId, string $data): void
    {
        if (str_starts_with($data, 'rating_')) {
            $bookingId = (int) substr($data, strlen('rating_'));
            $this->telegramNotificationService->sendRatingRequest($chatId, $bookingId);
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

        if (str_starts_with($data, 'skip_feedback_')) {
            $bookingId = (int) substr($data, strlen('skip_feedback_'));
            $this->skipFeedback($chatId, $bookingId);
            return;
        }
    }

    public function saveRating(int $chatId, int $bookingId, int $rating): void
    {
        try {
            $booking = Booking::find($bookingId);

            if (!$booking) {
                $this->sendMessage($chatId, "❌ Bron tabılmadı");
                return;
            }

            // Client tekshirish
            if ($booking->client->telegram_chat_id !== $chatId) {
                $this->sendMessage($chatId, "❌ Siz bul brondı bahalay almaysız");
                return;
            }

            // Rating saqlash
            $booking->addRating($rating);

            Log::info('Rating saved', [
                'booking_id' => $bookingId,
                'rating' => $rating,
                'chat_id' => $chatId
            ]);

            // Feedback so'rash
            $this->telegramNotificationService->sendFeedbackRequest($chatId, $bookingId, $rating);

            // Feedback kutish state'ini o'rnatish
            Cache::put("waiting_feedback_{$chatId}", $bookingId, 600); // 10 minut

        } catch (\Exception $e) {
            Log::error('Failed to save rating', [
                'booking_id' => $bookingId,
                'rating' => $rating,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);

            $this->sendMessage($chatId, "❌ Bahalawda qatelik júz berdi");
        }
    }

    public function handleFeedbackText(int $chatId, string $feedbackText): bool
    {
        $bookingId = Cache::get("waiting_feedback_{$chatId}");

        if (!$bookingId) {
            return false; // Bu feedback emas
        }

        try {
            $booking = Booking::find($bookingId);

            if (!$booking) {
                $this->sendMessage($chatId, "❌ Bron tabılmadı");
                return true;
            }

            // Feedback saqlash
            $booking->update(['feedback' => $feedbackText]);

            Log::info('Feedback saved', [
                'booking_id' => $bookingId,
                'chat_id' => $chatId,
                'feedback_length' => strlen($feedbackText)
            ]);

            // Cache tozalash
            Cache::forget("waiting_feedback_{$chatId}");

            // Rahmat xabari
            $this->telegramNotificationService->sendFeedbackThanks($chatId);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save feedback', [
                'booking_id' => $bookingId,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);

            $this->sendMessage($chatId, "❌ Pikir bildiriwde qatelik júz berdi");
            return true;
        }
    }

    public function skipRating(int $chatId, int $bookingId): void
    {
        try {
            $keyboard = [
                [
                    ['text' => '🏠 Bas menyu', 'callback_data' => 'main_menu'],
                    ['text' => '🔄 Jańadan bron', 'callback_data' => 'specialists']
                ]
            ];

            $message = "✅ <b>Jaqsı!</b>\n\n";
            $message .= "Sizdiń waqıtıńız ushın rahmet!\n";
            $message .= "Kelajekde bizdiń xizmetimizdi qollanıp turıńız.";

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

    public function skipFeedback(int $chatId, int $bookingId): void
    {
        try {
            // Cache tozalash
            Cache::forget("waiting_feedback_{$chatId}");

            // Rahmat xabari
            $this->telegramNotificationService->sendFeedbackThanks($chatId);

            Log::info('Feedback skipped', ['booking_id' => $bookingId, 'chat_id' => $chatId]);
        } catch (\Exception $e) {
            Log::error('Failed to skip feedback', [
                'booking_id' => $bookingId,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function isWaitingForFeedback(int $chatId): bool
    {
        return Cache::has("waiting_feedback_{$chatId}");
    }

    private function sendMessage(int $chatId, string $text): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
