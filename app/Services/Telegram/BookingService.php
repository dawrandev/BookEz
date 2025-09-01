<?php

namespace App\Services\Telegram;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\ScheduleBreak;
use App\Models\Service;
use App\Models\User;
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
            $this->sendMessage($chatId, "Bul qániygede jumıs waqtı kiritilmegen!");
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
        // Faqat faol bronlarni olish (canceled bo'lmaganlarini)
        $bookings = Booking::where('user_id', $specialistId)
            ->where('schedule_id', $schedule->id)
            ->whereIn('status', ['pending', 'confirmed', 'completed']) // canceled ni chiqarib tashlash
            ->get();

        $breaks = ScheduleBreak::where('schedule_id', $schedule->id)
            ->orderBy('start_time')
            ->get();

        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $duration = $service->duration_minutes;

        $period = new CarbonPeriod($startTime, '1 hour', $endTime);
        $lines = [];
        $lines[] = "🗓 Sáne: " . $schedule->work_date->format('Y-m-d') . " (" . $this->getDayName($schedule->work_date) . ")";
        $lines[] = "⏰ Jumıs waqtı: " . $startTime->format('H:i') . " - " . $endTime->format('H:i');
        $lines[] = "🔧 Xizmet: " . $service->name . " (" . $duration . " minut)";
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
                $statusText = "💤 " . ($breakReason ?: 'Dem alıs');
            } elseif ($isBooked) {
                $statusText = "❌ Bron qılınǵan";
            } else {
                $statusText = "✅ Bos";
            }

            $lines[] = $hour->format('H:i') . " - " . $statusText;
        }

        return implode("\n", $lines);
    }

    private function generateInlineKeyboard(int $specialistId, Service $service, Schedule $schedule): array
    {
        // Faqat faol bronlarni olish (canceled bo'lmaganlarini)
        $bookings = Booking::where('user_id', $specialistId)
            ->where('schedule_id', $schedule->id)
            ->whereIn('status', ['pending', 'confirmed', 'completed']) // canceled ni chiqarib tashlash
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
            ['text' => '🔙 Xizmetler', 'callback_data' => "specialist_services_{$specialistId}"]
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
            $this->sendMessage($chatId, '❌ Bul waqıt dem alıs waqtı. Iltimas basqa waqıt tanlań.');
            return;
        }

        // Faqat faol bronlar bilan konflikt tekshirish (canceled bo'lmaganlar bilan)
        $existingBooking = Booking::where('schedule_id', $scheduleId)
            ->whereIn('status', ['pending', 'confirmed', 'completed']) // canceled ni chiqarib tashlash
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime->format('H:i'))
                        ->where('end_time', '>', $startTime->format('H:i'));
                });
            })
            ->first();

        if ($existingBooking) {
            $this->sendMessage($chatId, '❌ Bul waqıt bánt. Iltimas basqa waqıt tanlań.');
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

        $specialist = User::find($schedule->user_id);
        if ($specialist) {
            Notification::make()
                ->title('Jańa bron jaratıldı')
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

    private function sendMessage(int $chatId, string $text)
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
