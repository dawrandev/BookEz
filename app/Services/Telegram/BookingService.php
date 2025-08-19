<?php

namespace App\Services\Telegram;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\ScheduleBreak;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Telegram\Bot\Laravel\Facades\Telegram;

class BookingService
{
    public function sendAvailableTimes(int $chatId, int $specialistId, int $serviceId, ?string $date = null)
    {
        $service = Service::findOrFail($serviceId);

        // Agar date berilmagan bo'lsa, eng yaqin ish kunini tanlash
        if (!$date) {
            $schedule = $this->getNextAvailableSchedule($specialistId);
        } else {
            $schedule = Schedule::where('user_id', $specialistId)
                ->where('work_date', $date)
                ->where('is_day_off', false)
                ->first();
        }

        if (!$schedule) {
            $this->sendMessage($chatId, "Bu mutaxassisda ish vaqtlari mavjud emas.");
            return;
        }

        $text = $this->generateScheduleText($specialistId, $service, $schedule);
        $keyboard = $this->generateInlineKeyboard($specialistId, $service, $schedule);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    private function getNextAvailableSchedule(int $specialistId): ?Schedule
    {
        $now = Carbon::now();

        // Bugungi kun va undan keyingi kunlarni qidirish
        return Schedule::where('user_id', $specialistId)
            ->where('is_day_off', false)
            ->where(function ($query) use ($now) {
                // Bugun bo'lsa va ish vaqti hali tugamagan bo'lsa
                $query->where(function ($q) use ($now) {
                    $q->where('work_date', $now->toDateString())
                        ->where('end_time', '>', $now->toTimeString());
                })
                    // Yoki kelajakdagi kunlar
                    ->orWhere('work_date', '>', $now->toDateString());
            })
            ->orderBy('work_date')
            ->orderBy('start_time')
            ->first();
    }

    private function generateScheduleText(int $specialistId, Service $service, Schedule $schedule): string
    {
        $bookings = Booking::where('user_id', $specialistId)
            ->where('schedule_id', $schedule->id)
            ->get();

        $breaks = ScheduleBreak::where('schedule_id', $schedule->id)
            ->orderBy('start_time')
            ->get();

        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $duration = $service->duration_minutes;

        $period = new CarbonPeriod($startTime, '1 hour', $endTime);
        $lines = [];
        $lines[] = "🗓 Sana: " . $schedule->work_date->format('Y-m-d') . " (" . $this->getDayName($schedule->work_date) . ")";
        $lines[] = "⏰ Ish vaqti: " . $startTime->format('H:i') . " - " . $endTime->format('H:i');
        $lines[] = "🔧 Xizmat: " . $service->name . " (" . $duration . " daqiqa)";
        $lines[] = "";

        foreach ($period as $hour) {
            if ($hour->gte($endTime)) break; // End time'dan keyin chiqmasin

            $blockEnd = $hour->copy()->addMinutes($duration);

            // Dam olish vaqtini tekshirish
            $isBreakTime = $this->isBreakTime($hour, $breaks);

            // Bron qilinganlikni tekshirish (faqat dam olish vaqti bo'lmasa)
            $isBooked = false;
            if (!$isBreakTime) {
                $isBooked = $this->isTimeBooked($hour, $blockEnd, $bookings);
            }

            // Status belgilash
            if ($isBreakTime) {
                $breakReason = $this->getBreakReason($hour, $breaks);
                $statusText = "🍽️ " . ($breakReason ?: 'Dam olish');
            } elseif ($isBooked) {
                $statusText = "❌ Bron qilingan";
            } else {
                $statusText = "✅ Bo'sh";
            }

            $lines[] = $hour->format('H:i') . " - " . $statusText;
        }

        return implode("\n", $lines);
    }

    private function generateInlineKeyboard(int $specialistId, Service $service, Schedule $schedule): array
    {
        $bookings = Booking::where('user_id', $specialistId)
            ->where('schedule_id', $schedule->id)
            ->get();

        $breaks = ScheduleBreak::where('schedule_id', $schedule->id)->get();

        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $duration = $service->duration_minutes;

        $period = new CarbonPeriod($startTime, '1 hour', $endTime);
        $availableButtons = [];

        foreach ($period as $hour) {
            if ($hour->gte($endTime)) break;

            $blockEnd = $hour->copy()->addMinutes($duration);

            // Dam olish va bron tekshirish
            $isBreakTime = $this->isBreakTime($hour, $breaks);
            $isBooked = $this->isTimeBooked($hour, $blockEnd, $bookings);

            // Faqat bo'sh vaqtlar uchun tugma yaratish
            if (!$isBreakTime && !$isBooked) {
                $availableButtons[] = [
                    'text' => $hour->format('H:i'),
                    'callback_data' => "book_{$schedule->id}_{$service->id}_{$hour->format('H:i')}"
                ];
            }
        }

        // Tugmalarni 3 taga bo'lib joylashtirish
        $keyboard = [];
        $buttonsPerRow = 3;
        $chunks = array_chunk($availableButtons, $buttonsPerRow);

        foreach ($chunks as $chunk) {
            $keyboard[] = $chunk;
        }

        // Pagination tugmalari
        $navButtons = $this->getPaginationButtons($specialistId, $schedule, $service->id);
        if (!empty($navButtons)) {
            $keyboard[] = $navButtons;
        }

        // Orqaga qaytish tugmasi
        $keyboard[] = [
            ['text' => '🔙 Xizmatlarga qaytish', 'callback_data' => "specialist_services_{$specialistId}"]
        ];

        return $keyboard;
    }

    private function getPaginationButtons(int $specialistId, Schedule $currentSchedule, int $serviceId): array
    {
        $buttons = [];

        // Oldingi kun
        $previous = Schedule::where('user_id', $specialistId)
            ->where('work_date', '<', $currentSchedule->work_date)
            ->where('is_day_off', false)
            ->orderByDesc('work_date')
            ->first();

        // Keyingi kun
        $next = Schedule::where('user_id', $specialistId)
            ->where('work_date', '>', $currentSchedule->work_date)
            ->where('is_day_off', false)
            ->orderBy('work_date')
            ->first();

        if ($previous) {
            $buttons[] = [
                'text' => '⬅️ ' . Carbon::parse($previous->work_date)->format('m/d'),
                'callback_data' => "day_{$previous->work_date}_{$serviceId}"
            ];
        }

        if ($next) {
            $buttons[] = [
                'text' => Carbon::parse($next->work_date)->format('m/d') . ' ➡️',
                'callback_data' => "day_{$next->work_date}_{$serviceId}"
            ];
        }

        return $buttons;
    }

    private function isBreakTime(Carbon $hour, $breaks): bool
    {
        foreach ($breaks as $break) {
            $breakStart = Carbon::parse($break->start_time);
            $breakEnd = Carbon::parse($break->end_time);

            if ($hour->between($breakStart, $breakEnd->subSecond())) {
                return true;
            }
        }
        return false;
    }

    private function getBreakReason(Carbon $hour, $breaks): ?string
    {
        foreach ($breaks as $break) {
            $breakStart = Carbon::parse($break->start_time);
            $breakEnd = Carbon::parse($break->end_time);

            if ($hour->between($breakStart, $breakEnd->subSecond())) {
                return $break->reason;
            }
        }
        return null;
    }

    private function isTimeBooked(Carbon $hour, Carbon $blockEnd, $bookings): bool
    {
        return $bookings->contains(function ($booking) use ($hour, $blockEnd) {
            $bookingStart = Carbon::parse($booking->start_time);
            $bookingEnd = Carbon::parse($booking->end_time);

            // Vaqt oralig'i kesishish tekshirish
            return $hour->lt($bookingEnd) && $blockEnd->gt($bookingStart);
        });
    }

    public function createBooking(int $chatId, int $scheduleId, int $serviceId, string $time)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $service = Service::findOrFail($serviceId);

        $startTime = Carbon::parse($time);
        $endTime = $startTime->copy()->addMinutes($service->duration_minutes);

        // Dam olish vaqti tekshirish
        $breaks = ScheduleBreak::where('schedule_id', $scheduleId)->get();
        if ($this->isBreakTime($startTime, $breaks)) {
            $this->sendMessage($chatId, '❌ Bu vaqt dam olish vaqti. Iltimos boshqa vaqt tanlang.');
            return;
        }

        // Mavjud bronni tekshirish
        $existingBooking = Booking::where('schedule_id', $scheduleId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime->format('H:i'))
                        ->where('end_time', '>', $startTime->format('H:i'));
                });
            })
            ->first();

        if ($existingBooking) {
            $this->sendMessage($chatId, '❌ Bu vaqt allaqachon band. Iltimos boshqa vaqt tanlang.');
            return;
        }

        // Client yaratish yoki topish
        $client = $this->findOrCreateClient($chatId);

        // Yangi bron yaratish
        Booking::create([
            'user_id' => $schedule->user_id,
            'client_id' => $client->id,
            'service_id' => $serviceId,
            'schedule_id' => $scheduleId,
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'status' => 'pending',
        ]);

        $this->sendMessage(
            $chatId,
            "✅ Broningiz muvaffaqiyatli yaratildi!\n\n" .
                "📅 Sana: " . $schedule->work_date->format('Y-m-d') . "\n" .
                "⏰ Vaqt: " . $startTime->format('H:i') . " - " . $endTime->format('H:i') . "\n" .
                "🔧 Xizmat: " . $service->name . "\n" .
                "⏳ Status: Kutilmoqda"
        );
    }

    private function findOrCreateClient(int $chatId)
    {
        // Avval telegram_id bo'yicha qidirish
        $client = \App\Models\Client::where('telegram_id', $chatId)->first();

        if (!$client) {
            // Yangi client yaratish
            $client = \App\Models\Client::create([
                'telegram_id' => $chatId,
                'name' => 'Telegram User ' . $chatId, // default name
                'phone' => null, // keyinroq to'ldirilishi mumkin
            ]);
        }

        return $client;
    }

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

    private function sendMessage(int $chatId, string $text)
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
