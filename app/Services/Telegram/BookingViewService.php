<?php

namespace App\Services\Telegram;

use App\Models\Booking;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class BookingViewService
{
    /**
     * Foydalanuvchining barcha bronlarini ko'rsatish
     */
    public function showMyBookings(int $chatId)
    {
        $client = $this->findOrCreateClient($chatId);

        $bookings = Booking::with(['service', 'schedule.user', 'user'])
            ->where('client_id', $client->id)
            ->orderByRaw("
                CASE 
                    WHEN status = 'confirmed' THEN 1
                    WHEN status = 'pending' THEN 2
                    WHEN status = 'completed' THEN 3
                    WHEN status = 'canceled' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($bookings->isEmpty()) {
            $this->sendMessage($chatId, "📋 Sizde heshqanday bron joq.\n\n🔍 Bron jaratıw ushın /start buyruǵın beriń.");
            return;
        }

        $text = "📖 Siziń bronlarıńız:\n\n";
        $keyboard = [];

        foreach ($bookings as $index => $booking) {
            $schedule = $booking->schedule;
            $service = $booking->service;
            $specialist = $booking->user;

            $statusEmoji = $this->getStatusEmoji($booking->status);
            $statusText = $this->getStatusText($booking->status);

            $workDate = Carbon::parse($schedule->work_date)->format('d.m.Y');
            $dayName = $this->getDayName($schedule->work_date);

            $text .= "━━━━━━━━━━━━━━━━━━━\n";
            $text .= "📋 Bron #" . ($index + 1) . "\n";
            $text .= "👨‍⚕️ Qánige: " . $specialist->name . "\n";
            $text .= "🔧 Xizmet: " . $service->name . "\n";
            $text .= "🗓 Sáne: " . $workDate . " (" . $dayName . ")\n";
            $text .= "⏰ Waqıt: " . $booking->start_time . " - " . $booking->end_time . "\n";
            $text .= "📊 Jaǵdayı: " . $statusEmoji . " " . $statusText . "\n";

            if ($booking->notes) {
                $text .= "📝 Izoh: " . $booking->notes . "\n";
            }

            $text .= "\n";

            $keyboard[] = [
                [
                    'text' => "📋 Bron #" . ($index + 1) . " - " . $statusEmoji,
                    'callback_data' => "booking_detail_{$booking->id}"
                ]
            ];
        }

        $keyboard[] = [
            ['text' => '🔄 Jańalaw', 'callback_data' => "my_bookings_{$client->id}"],
            ['text' => '🏠 Menyu', 'callback_data' => 'main_menu']
        ];

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    public function showBookingDetail(int $chatId, int $bookingId)
    {
        $booking = Booking::with(['service', 'schedule.user', 'user', 'client'])
            ->find($bookingId);

        if (!$booking) {
            $this->sendMessage($chatId, "❌ Bron tabılmadń");
            return;
        }

        // Faqat o'z bronini ko'ra oladi
        $client = $this->findOrCreateClient($chatId);
        if ($booking->client_id !== $client->id) {
            $this->sendMessage($chatId, "❌ Siz bul brondı kóre almaysız.");
            return;
        }

        $schedule = $booking->schedule;
        $service = $booking->service;
        $specialist = $booking->user;

        $statusEmoji = $this->getStatusEmoji($booking->status);
        $statusText = $this->getStatusText($booking->status);

        $workDate = Carbon::parse($schedule->work_date)->format('d.m.Y');
        $dayName = $this->getDayName($schedule->work_date);
        $createdAt = Carbon::parse($booking->created_at)->format('d.m.Y H:i');

        $startTime = Carbon::parse($booking->start_time)->format('H:i');
        $endTime   = Carbon::parse($booking->end_time)->format('H:i');


        $text = "📋 <b>Bron maǵlıwmatları</b>\n\n";
        $text .= "🆔 ID: #" . $booking->id . "\n";
        $text .= "👨‍⚕️ <b>Qánige:</b> " . $specialist->name . "\n";
        $text .= "🔧 <b>Xizmet:</b> " . $service->name . "\n";
        $text .= "⏱ <b>Dawamlıǵı:</b> " . $service->duration_minutes . " minut\n";
        $text .= "💰 <b>Xizmet baxası:</b> " . number_format($service->price, 0, '.', ' ') . " so'm\n\n";

        $text .= "🗓 <b>Sáne:</b> " . $workDate . " (" . $dayName . ")\n";
        $text .= "⏰ <b>Waqıt:</b> " . $startTime . " - " . $endTime . "\n\n";

        $text .= "📊 <b>Jaǵdayı:</b> " . $statusEmoji . " " . $statusText . "\n";
        $text .= "📅 <b>Jaratılǵan waqtı:</b> " . $createdAt . "\n";

        if ($booking->notes) {
            $text .= "📝 <b>Túsindirme:</b> " . $booking->notes . "\n";
        }

        $keyboard = [];

        if ($booking->status === 'pending') {
            $keyboard[] = [
                ['text' => '❌ Biykarlaw', 'callback_data' => "cancel_booking_{$booking->id}"]
            ];
        } elseif ($booking->status === 'confirmed') {
            $keyboard[] = [
                ['text' => '❌ Biykarlaw', 'callback_data' => "cancel_booking_{$booking->id}"]
            ];
        }

        $keyboard[] = [
            ['text' => '📖 Meniń bronlarım', 'callback_data' => "my_bookings_{$client->id}"],
            ['text' => '🏠 Menyu', 'callback_data' => 'main_menu']
        ];

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    public function cancelBooking(int $chatId, int $bookingId)
    {
        $booking = Booking::with(['service', 'schedule.user', 'client'])
            ->find($bookingId);

        if (!$booking) {
            $this->sendMessage($chatId, "❌ Bron tabılmadń");
            return;
        }

        // Faqat o'z bronini bekor qila oladi
        $client = $this->findOrCreateClient($chatId);
        if ($booking->client_id !== $client->id) {
            $this->sendMessage($chatId, "❌ Siz bul brondı biykarlay almaysız.");
            return;
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            $this->sendMessage($chatId, "❌ Bul brondı biykarlaw múmkin emes. Jaǵdayı: " . $this->getStatusText($booking->status));
            return;
        }

        // work_date ni faqat sana sifatida olish va start_time bilan birlashtirish
        $workDate = Carbon::parse($booking->schedule->work_date)->format('Y-m-d');
        $bookingDateTime = Carbon::parse($workDate . ' ' . $booking->start_time);
        $now = Carbon::now();

        // Debug uchun (keyinchalik o'chirish mumkin)
        Log::info('Booking cancel check:', [
            'booking_datetime' => $bookingDateTime->toDateTimeString(),
            'now' => $now->toDateTimeString(),
            'is_future' => $bookingDateTime->isFuture(),
            'diff_in_hours' => $bookingDateTime->diffInHours($now, false), // false - real difference with sign
            'hours_until_booking' => $now->diffInHours($bookingDateTime, false)
        ]);

        // Agar bron vaqti o'tib ketgan bo'lsa
        if ($bookingDateTime->isPast()) {
            $this->sendMessage($chatId, "❌ O'tib ketgan bronni biykarlaw múmkin emes");
            return;
        }

        // Agar bron vaqtigacha 1 soatdan kam qolgan bo'lsa
        $hoursUntilBooking = $now->diffInHours($bookingDateTime, false);
        if ($hoursUntilBooking < 1) {
            $this->sendMessage($chatId, "❌ Bron waqtınan keminde 1 saat aldın biykarlanıwı kerek");
            return;
        }

        $booking->update(['status' => 'canceled']);
    }

    /**
     * Client topish yoki yaratish
     */
    private function findOrCreateClient(int $chatId): Client
    {
        $client = Client::where('telegram_id', $chatId)->first();

        if (!$client) {
            $client = Client::create([
                'telegram_id' => $chatId,
                'name' => 'Telegram User ' . $chatId,
                'phone' => null,
            ]);
        }

        return $client;
    }

    /**
     * Status uchun emoji olish
     */
    private function getStatusEmoji(string $status): string
    {
        return match ($status) {
            'pending' => '⏳',
            'confirmed' => '✅',
            'canceled' => '❌',
            'completed' => '🎉',
            default => '❓'
        };
    }

    /**
     * Status matnini olish
     */
    private function getStatusText(string $status): string
    {
        return match ($status) {
            'pending' => 'Kútilmekte',
            'confirmed' => 'Qabıllanǵan',
            'canceled' => 'Biykarlanǵan',
            'completed' => 'Juwmaqlanǵan',
            default => 'Belgisiz'
        };
    }

    /**
     * Kun nomini olish
     */
    private function getDayName($date): string
    {
        $dayNames = [
            'Monday' => '1-kún',
            'Tuesday' => '2-kún',
            'Wednesday' => '3-kún',
            'Thursday' => '4-kún',
            'Friday' => '5-kún',
            'Saturday' => '6-kún',
            'Sunday' => '7-kún'
        ];

        return $dayNames[Carbon::parse($date)->format('l')] ?? Carbon::parse($date)->format('l');
    }

    /**
     * Telegram xabar yuborish
     */
    private function sendMessage(int $chatId, string $text): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }
}
