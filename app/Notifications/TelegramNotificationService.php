<?php

namespace App\Notifications;

use App\Models\Booking;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramNotificationService
{
    public function sendBookingCreated(Booking $booking): void
    {
        $telegramId = $booking->client->telegram_id;

        if (!$telegramId) {
            Log::warning('Booking Created - Booking ID: ' . $booking->id . ' - Client telegram_id topilmadi');
            return;
        }

        $message = $this->generateCreatedMessage($booking);

        try {
            $response = Telegram::sendMessage([
                'chat_id' => $telegramId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            Log::info('Telegram API Response for created: ', ['response' => $response->toArray()]);
            Log::info('Booking created telegram xabari yuborildi. Booking ID: ' . $booking->id . ' Chat ID: ' . $telegramId);
        } catch (Exception $e) {
            Log::error('Booking created telegram xabarini yuborishda xatolik: ' . $e->getMessage() . ' - Booking ID: ' . $booking->id . ' - Chat ID: ' . $telegramId);
            Log::error('Error details: ', ['exception' => $e->getTraceAsString()]);
        }
    }

    public function sendStatusUpdate(Booking $booking): void
    {
        Log::info('sendStatusUpdate called for Booking ID: ' . $booking->id);

        $telegramId = $booking->client->telegram_id;

        if (!$telegramId) {
            Log::warning('Status Update - Booking ID: ' . $booking->id . ' - Client telegram_id topilmadi');
            return;
        }

        $message = $this->generateStatusMessage($booking);

        try {
            $response = Telegram::sendMessage([
                'chat_id' => $telegramId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            Log::info('Telegram API Response for status update: ', ['response' => $response->toArray()]);
            Log::info('Status update telegram xabari yuborildi. Booking ID: ' . $booking->id . ' Chat ID: ' . $telegramId);
        } catch (Exception $e) {
            Log::error('Status update telegram xabarini yuborishda xatolik: ' . $e->getMessage() . ' - Booking ID: ' . $booking->id . ' - Chat ID: ' . $telegramId);
            Log::error('Error details: ', ['exception' => $e->getTraceAsString()]);
        }
    }

    private function generateCreatedMessage(Booking $booking): string
    {
        $specialist = $booking->user->name;
        $service = $booking->service->name;
        $date = $booking->schedule->work_date;
        $time = $booking->start_time . ' - ' . $booking->end_time;

        $message = "🎯 <b>Yangi bronlash yaratildi!</b>\n\n";
        $message .= "👨‍💼 <b>Specialist:</b> {$specialist}\n";
        $message .= "🔧 <b>Xizmat:</b> {$service}\n";
        $message .= "📅 <b>Sana:</b> {$date}\n";
        $message .= "⏰ <b>Vaqt:</b> {$time}\n";
        $message .= "📊 <b>Holat:</b> ⏳ Kutilmoqda\n\n";
        $message .= "Specialist tez orada sizning bronlashingizni tasdiqlaydi.";

        return $message;
    }

    private function generateStatusMessage(Booking $booking): string
    {
        $statusEmoji = match ($booking->status) {
            'confirmed' => '✅',
            'canceled' => '❌',
            'completed' => '🎉',
            'pending' => '⏳',
            default => '📝'
        };

        $statusText = match ($booking->status) {
            'confirmed' => 'Tasdiqlandi',
            'canceled' => 'Bekor qilindi',
            'completed' => 'Tugatildi',
            'pending' => 'Kutilmoqda',
            default => 'Noma\'lum'
        };

        $specialist = $booking->user->name;
        $service = $booking->service->name;
        $date = $booking->schedule->work_date;
        $time = $booking->start_time . ' - ' . $booking->end_time;

        $message = "{$statusEmoji} <b>Bronlash holati o'zgartirildi</b>\n\n";
        $message .= "📊 <b>Holat:</b> {$statusText}\n";
        $message .= "👨‍💼 <b>Specialist:</b> {$specialist}\n";
        $message .= "🔧 <b>Xizmat:</b> {$service}\n";
        $message .= "📅 <b>Sana:</b> {$date}\n";
        $message .= "⏰ <b>Vaqt:</b> {$time}\n";

        if ($booking->status === 'confirmed') {
            $message .= "\n💚 <b>Sizning bronlashingiz tasdiqlandi!</b>";
            $message .= "\nIltimos belgilangan vaqtda keling.";
        } elseif ($booking->status === 'canceled') {
            $message .= "\n💔 <b>Bronlashingiz ma'lum sababga ko'ra bekor qilindi!</b>";
            $message .= "\nBoshqa vaqt uchun bronlashingiz mumkin.";
        } elseif ($booking->status === 'completed') {
            $message .= "\n🎉 <b>Xizmat tugatildi</b>";
            $message .= "\nRahmat!";
        }

        return $message;
    }
}
