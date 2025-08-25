<?php

namespace App\Observers;

use App\Models\Booking;
use App\Notifications\TelegramNotificationService;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class BookingObserver
{
    /*************  ✨ Windsurf Command ⭐  *************/
    /*******  c146da3f-1493-4498-8450-7573ffbfcf5d  *******/
    public function __construct(
        protected TelegramNotificationService $telegramNotificationService
    ) {
        Log::info('BookingObserver constructed');
    }

    public function created(Booking $booking): void
    {
        Log::info('Booking created event triggered for ID: ' . $booking->id);
        $this->telegramNotificationService->sendBookingCreated($booking);
    }

    public function updated(Booking $booking): void
    {
        Log::info('Booking updated event triggered for ID: ' . $booking->id, [
            'status' => $booking->status,
            'original_status' => $booking->getOriginal('status'),
            'is_dirty' => $booking->isDirty('status')
        ]);

        if ($booking->isDirty('status')) {
            Log::info('Status changed for Booking ID: ' . $booking->id . ', sending notification.');
            try {
                $this->telegramNotificationService->sendStatusUpdate($booking);
            } catch (Exception $e) {
                Log::error('Failed to send status update notification for Booking ID: ' . $booking->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::info('Status not changed for Booking ID: ' . $booking->id);
        }
    }

    public function deleted(Booking $booking): void
    {
        Log::info('Booking deleted event triggered for ID: ' . $booking->id);
        $telegramId = $booking->client->telegram_id;

        if ($telegramId) {
            $message = "❌ <b>Bronlashingiz o'chirildi</b>\n\n";
            $message .= "📅 Sana: {$booking->schedule->work_date}\n";
            $message .= "⏰ Vaqt: {$booking->start_time} - {$booking->end_time}\n";
            $message .= "🔧 Xizmat: {$booking->service->name}";

            try {
                Telegram::sendMessage([
                    'chat_id' => $telegramId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ]);
                Log::info('Deleted notification sent for Booking ID: ' . $booking->id);
            } catch (Exception $e) {
                Log::error('Booking deleted message error for ID: ' . $booking->id, [
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::warning('No telegram_id for client in Booking ID: ' . $booking->id);
        }
    }

    public function restored(Booking $booking): void
    {
        Log::info('Booking restored event triggered for ID: ' . $booking->id);
    }

    public function forceDeleted(Booking $booking): void
    {
        Log::info('Booking forceDeleted event triggered for ID: ' . $booking->id);
    }
}
