<?php

namespace App\Services\Telegram;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\ScheduleBreak;
use App\Models\Service;
use App\Notifications\TelegramNotificationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class BookingService
{
    public function __construct(
        protected TelegramNotificationService $telegramNotificationService
    ) {
        //
    }

    public function sendAvailableTimes(int $chatId, int $specialistId, int $serviceId, ?string $date = null)
    {
        $service = Service::findOrFail($serviceId);

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

        return Schedule::where('user_id', $specialistId)
            ->where('is_day_off', false)
            ->where(function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    $q->where('work_date', $now->toDateString())
                        ->where('end_time', '>', $now->toTimeString());
                })
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
            if ($hour->gte($endTime)) break;

            $blockEnd = $hour->copy()->addMinutes($duration);

            $isBreakTime = $this->isBreakTime($hour, $breaks);

            $isBooked = false;
            if (!$isBreakTime) {
                $isBooked = $this->isTimeBooked($hour, $blockEnd, $bookings);
            }

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

            $isBreakTime = $this->isBreakTime($hour, $breaks);
            $isBooked = $this->isTimeBooked($hour, $blockEnd, $bookings);

            if (!$isBreakTime && !$isBooked) {
                $availableButtons[] = [
                    'text' => $hour->format('H:i'),
                    'callback_data' => "book_{$schedule->id}_{$service->id}_{$hour->format('H:i')}"
                ];
            }
        }

        $keyboard = [];
        $buttonsPerRow = 3;
        $chunks = array_chunk($availableButtons, $buttonsPerRow);

        foreach ($chunks as $chunk) {
            $keyboard[] = $chunk;
        }

        $navButtons = $this->getPaginationButtons($specialistId, $schedule, $service->id);
        if (!empty($navButtons)) {
            $keyboard[] = $navButtons;
        }

        $keyboard[] = [
            ['text' => '🔙 Xizmatlarga qaytish', 'callback_data' => "specialist_services_{$specialistId}"]
        ];

        return $keyboard;
    }

    private function getPaginationButtons(int $specialistId, Schedule $currentSchedule, int $serviceId): array
    {
        $buttons = [];

        $previous = Schedule::where('user_id', $specialistId)
            ->where('work_date', '<', $currentSchedule->work_date)
            ->where('is_day_off', false)
            ->orderByDesc('work_date')
            ->first();

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

            return $hour->lt($bookingEnd) && $blockEnd->gt($bookingStart);
        });
    }

    public function createBooking(int $chatId, int $scheduleId, int $serviceId, string $time)
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $service = Service::findOrFail($serviceId);

        $startTime = Carbon::parse($time);
        $endTime = $startTime->copy()->addMinutes($service->duration_minutes);

        $breaks = ScheduleBreak::where('schedule_id', $scheduleId)->get();
        if ($this->isBreakTime($startTime, $breaks)) {
            $this->sendMessage($chatId, '❌ Bu vaqt dam olish vaqti. Iltimos boshqa vaqt tanlang.');
            return;
        }

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

        $client = $this->findOrCreateClient($chatId);

        $booking = Booking::create([
            'user_id' => $schedule->user_id,
            'client_id' => $client->id,
            'service_id' => $serviceId,
            'schedule_id' => $scheduleId,
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'status' => 'pending',
        ]);

        // Specialistga Filament notification yuborish
        $specialist = \App\Models\User::find($schedule->user_id);
        if ($specialist) {
            Notification::make()
                ->title('Yangi bron yaratildi')
                ->body("
                    Клиент: {$client->full_name}\n
                    Услуга: {$service->name}\n
                    Дата: {$schedule->work_date->format('d.m.Y')}\n
                    Время: {$startTime->format('H:i')} - {$endTime->format('H:i')}\n
                    Статус: В ожидании
                ")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Просмотреть')
                        ->url(route('filament.admin.resources.bookings.view', $booking))
                        ->button(),
                ])
                ->success()
                ->sendToDatabase($specialist);

            Log::info('Filament notification sent to specialist', [
                'booking_id' => $booking->id,
                'specialist_id' => $specialist->id,
            ]);

            Log::info('Attempting to send notification to specialist', ['specialist_id' => $specialist->id]);
        } else {
            Log::warning('Specialist not found for booking ID: ' . $booking->id);
        }

        // Foydalanuvchiga Telegram xabari
        $this->sendMessage(
            $chatId,
            "✅ Broningiz muvaffaqiyatli yaratildi!\n\n" .
                "📅 Sana: " . $schedule->work_date->format('Y-m-d') . "\n" .
                "⏰ Vaqt: " . $startTime->format('H:i') . " - " . $endTime->format('H:i') . "\n" .
                "🔧 Xizmat: " . $service->name . "\n" .
                "⏳ Status: Kutilmoqda\n\n" .
                "Specialist tez orada sizning bronlashingizni ko'rib chiqadi."
        );
    }

    private function findOrCreateClient(int $chatId)
    {
        $client = \App\Models\Client::where('telegram_id', $chatId)->first();

        if (!$client) {
            $client = \App\Models\Client::create([
                'telegram_id' => $chatId,
                'name' => 'Telegram User ' . $chatId,
                'phone' => null,
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
