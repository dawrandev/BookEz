<?php

namespace App\Notifications;

use App\Models\Booking;
use Carbon\Carbon;
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
        } catch (Exception $e) {
            Log::error('Booking created error sending telegram message: ' . $e->getMessage() . ' - Booking ID: ' . $booking->id . ' - Chat ID: ' . $telegramId);
            Log::error('Error details: ', ['exception' => $e->getTraceAsString()]);
        }
    }

    public function sendStatusUpdate(Booking $booking): void
    {
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
        } catch (Exception $e) {
            Log::error('Status update error sending telegram message: ' . $e->getMessage() . ' - Booking ID: ' . $booking->id . ' - Chat ID: ' . $telegramId);
            Log::error('Error details: ', ['exception' => $e->getTraceAsString()]);
        }
    }

    private function generateCreatedMessage(Booking $booking): string
    {
        $specialist = $booking->user->name;
        $service = $booking->service->name;

        $date = Carbon::parse($booking->schedule->work_date)->format('Y-m-d');

        $start = Carbon::parse($booking->start_time)->format('H:i');
        $end   = Carbon::parse($booking->end_time)->format('H:i');
        $time = $start . ' - ' . $end;

        $message = "🎯 <b>Bron jaratıldı!</b>\n\n";
        $message .= "👨‍💼 <b>Qániyge:</b> {$specialist}\n";
        $message .= "🔧 <b>Xizmet:</b> {$service}\n";
        $message .= "📅 <b>Sáne:</b> {$date}\n";
        $message .= "⏰ <b>Waqıt:</b> {$time}\n";
        $message .= "📊 <b>Jaǵday:</b> ⏳ Kutilmoqda\n\n";
        $message .= "Qániyge tez arada siziń bronıńızdı qabıl qıladı.";

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
            'confirmed' => 'Tastıyıqlandı',
            'canceled' => 'Biykar qılındı',
            'completed' => 'Juwmaqlandı',
            'pending' => 'Kútilmekte',
            default => 'Belgisiz'
        };

        $specialist = $booking->user->name;
        $service = $booking->service->name;

        $date = Carbon::parse($booking->schedule->work_date)->format('Y-m-d');
        $start = Carbon::parse($booking->start_time)->format('H:i');
        $end   = Carbon::parse($booking->end_time)->format('H:i');
        $time = $start . ' - ' . $end;

        $message = "{$statusEmoji} <b>Bronlaw jaǵdayı ózgertildi</b>\n\n";
        $message .= "📊 <b>Jaǵday:</b> {$statusText}\n";
        $message .= "👨‍💼 <b>Qániyge:</b> {$specialist}\n";
        $message .= "🔧 <b>Xizmet:</b> {$service}\n";
        $message .= "📅 <b>Sáne:</b> {$date}\n";
        $message .= "⏰ <b>Waqıt:</b> {$time}\n";

        if ($booking->status === 'confirmed') {
            $message .= "\n💚 <b>Siziń bronıńız qabıllandı!</b>";
            $message .= "\nIltimas waqtında keliń.";
        } elseif ($booking->status === 'canceled') {
            $message .= "\n💔 <b>Bronıńız málim sebepke kóre biykarlandı!</b>";
            $message .= "\nBasqa waqıt ushın bronlawıńız múmkin";
        } elseif ($booking->status === 'completed') {
            $message .= "\n🎉 <b>Xizmet juwmaqlandı</b>";
            $message .= "\nRáxmet";
        }

        return $message;
    }
}
