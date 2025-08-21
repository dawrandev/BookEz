<?php

namespace App\Services\Telegram;

use App\Models\Booking;
use App\Models\Client;
use Carbon\Carbon;
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
            ->limit(10) // Oxirgi 10 ta bron
            ->get();

        if ($bookings->isEmpty()) {
            $this->sendMessage($chatId, "📋 Sizda hech qanday bron yo'q.\n\n🔍 Yangi bron qilish uchun /start buyrug'ini yuboring.");
            return;
        }

        $text = "📖 Sizning bronlaringiz:\n\n";
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
            $text .= "👨‍⚕️ Mutaxassis: " . $specialist->name . "\n";
            $text .= "🔧 Xizmet: " . $service->name . "\n";
            $text .= "🗓 Sáne: " . $workDate . " (" . $dayName . ")\n";
            $text .= "⏰ Waqt: " . $booking->start_time . " - " . $booking->end_time . "\n";
            $text .= "📊 Holat: " . $statusEmoji . " " . $statusText . "\n";

            if ($booking->notes) {
                $text .= "📝 Izoh: " . $booking->notes . "\n";
            }

            $text .= "\n";

            // Har bir bron uchun batafsil ko'rish tugmasi
            $keyboard[] = [
                [
                    'text' => "📋 Bron #" . ($index + 1) . " - " . $statusEmoji,
                    'callback_data' => "booking_detail_{$booking->id}"
                ]
            ];
        }

        // Navigatsiya tugmalari
        $keyboard[] = [
            ['text' => '🔄 Yangilash', 'callback_data' => "my_bookings_{$client->id}"],
            ['text' => '🏠 Bosh sahifa', 'callback_data' => 'main_menu']
        ];

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    /**
     * Bitta bronning batafsil ma'lumotlarini ko'rsatish
     */
    public function showBookingDetail(int $chatId, int $bookingId)
    {
        $booking = Booking::with(['service', 'schedule.user', 'user', 'client'])
            ->find($bookingId);

        if (!$booking) {
            $this->sendMessage($chatId, "❌ Bron topilmadi.");
            return;
        }

        // Faqat o'z bronini ko'ra oladi
        $client = $this->findOrCreateClient($chatId);
        if ($booking->client_id !== $client->id) {
            $this->sendMessage($chatId, "❌ Siz bu bronni ko'ra olmaysiz.");
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

        $text = "📋 <b>Bron tafsilotlari</b>\n\n";
        $text .= "🆔 ID: #" . $booking->id . "\n";
        $text .= "👨‍⚕️ <b>Mutaxassis:</b> " . $specialist->name . "\n";
        $text .= "🔧 <b>Xizmet:</b> " . $service->name . "\n";
        $text .= "⏱ <b>Davomiyligi:</b> " . $service->duration_minutes . " minut\n";
        $text .= "💰 <b>Narx:</b> " . number_format($service->price, 0, '.', ' ') . " so'm\n\n";

        $text .= "🗓 <b>Sáne:</b> " . $workDate . " (" . $dayName . ")\n";
        $text .= "⏰ <b>Waqt:</b> " . $booking->start_time . " - " . $booking->end_time . "\n\n";

        $text .= "📊 <b>Holat:</b> " . $statusEmoji . " " . $statusText . "\n";
        $text .= "📅 <b>Yaratilgan:</b> " . $createdAt . "\n";

        if ($booking->notes) {
            $text .= "📝 <b>Izoh:</b> " . $booking->notes . "\n";
        }

        $keyboard = [];

        // Holat asosida tugmalar
        if ($booking->status === 'pending') {
            $keyboard[] = [
                ['text' => '❌ Bekor qilish', 'callback_data' => "cancel_booking_{$booking->id}"]
            ];
        } elseif ($booking->status === 'confirmed') {
            $keyboard[] = [
                ['text' => '❌ Bekor qilish', 'callback_data' => "cancel_booking_{$booking->id}"]
            ];
        }

        $keyboard[] = [
            ['text' => '📖 Barcha bronlar', 'callback_data' => "my_bookings_{$client->id}"],
            ['text' => '🏠 Bosh sahifa', 'callback_data' => 'main_menu']
        ];

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
    }

    /**
     * Bronni bekor qilish
     */
    public function cancelBooking(int $chatId, int $bookingId)
    {
        $booking = Booking::with(['service', 'schedule.user', 'client'])
            ->find($bookingId);

        if (!$booking) {
            $this->sendMessage($chatId, "❌ Bron topilmadi.");
            return;
        }

        // Faqat o'z bronini bekor qila oladi
        $client = $this->findOrCreateClient($chatId);
        if ($booking->client_id !== $client->id) {
            $this->sendMessage($chatId, "❌ Siz bu bronni bekor qila olmaysiz.");
            return;
        }

        // Faqat pending va confirmed holatidagi bronlarni bekor qilish mumkin
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            $this->sendMessage($chatId, "❌ Bu bronni bekor qilish mumkin emas. Holat: " . $this->getStatusText($booking->status));
            return;
        }

        // Vaqt tekshiruvi - brondan kamida 1 soat oldin bekor qilish kerak
        $bookingDateTime = Carbon::parse($booking->schedule->work_date . ' ' . $booking->start_time);
        $now = Carbon::now();

        if ($bookingDateTime->diffInHours($now) < 1 && $bookingDateTime->isFuture()) {
            $this->sendMessage($chatId, "❌ Bron vaqtidan kamida 1 soat oldin bekor qilish kerak.");
            return;
        }

        $booking->update(['status' => 'canceled']);

        $workDate = Carbon::parse($booking->schedule->work_date)->format('d.m.Y');

        $text = "✅ <b>Bron muvaffaqiyatli bekor qilindi!</b>\n\n";
        $text .= "📋 Bron ID: #" . $booking->id . "\n";
        $text .= "🔧 Xizmet: " . $booking->service->name . "\n";
        $text .= "🗓 Sáne: " . $workDate . "\n";
        $text .= "⏰ Waqt: " . $booking->start_time . " - " . $booking->end_time . "\n";

        $keyboard = [
            [
                ['text' => '📖 Barcha bronlar', 'callback_data' => "my_bookings_{$client->id}"],
                ['text' => '🏠 Bosh sahifa', 'callback_data' => 'main_menu']
            ]
        ];

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            'parse_mode' => 'HTML'
        ]);
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
            'pending' => 'Kutilmoqda',
            'confirmed' => 'Tasdiqlangan',
            'canceled' => 'Bekor qilingan',
            'completed' => 'Yakunlangan',
            default => 'Noma\'lum'
        };
    }

    /**
     * Kun nomini olish
     */
    private function getDayName($date): string
    {
        $dayNames = [
            'Monday' => 'Dushanba',
            'Tuesday' => 'Seshanba',
            'Wednesday' => 'Chorshanba',
            'Thursday' => 'Payshanba',
            'Friday' => 'Juma',
            'Saturday' => 'Shanba',
            'Sunday' => 'Yakshanba'
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
