<?php

namespace App\Handlers\Telegram;

use App\Services\Telegram\CategoryService;
use App\Services\Telegram\SpecialistService;
use App\Services\Telegram\UserService;
use App\Services\Telegram\BookingService;
use Telegram\Bot\Objects\CallbackQuery;

class CallbackQueryHandler
{
    public function __construct(
        protected UserService $userService,
        protected CategoryService $categoryService,
        protected SpecialistService $specialistService,
        protected BookingService $bookingService
    ) {}

    public function handle(CallbackQuery $callbackQuery): void
    {
        $data = $callbackQuery->getData();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();

        $handlers = [
            'specialist_services_' => fn() => $this->handleSpecialistServiceCallback($chatId, $data),
            'specialist_'          => fn() => $this->handleSpecialistCallback($chatId, $data),
            'category_'             => fn() => $this->handleCategoryCallback($chatId, $data),
            'service_'              => fn() => $this->handleServiceCallback($chatId, $data),
            'book_'                 => fn() => $this->handleBookingCallback($chatId, $data),
            'day_'                  => fn() => $this->handleScheduleDayCallback($chatId, $data),
            'specialists'           => fn() => $this->userService->showSpecialists($chatId, $data),
            'categories'            => fn() => $this->categoryService->showCategoriesToUser($chatId),
        ];

        foreach ($handlers as $prefix => $callback) {
            if (str_starts_with($data, $prefix)) {
                $callback();
                return;
            }
        }
    }

    private function handleCategoryCallback(int $chatId, string $data): void
    {
        $categoryId = (int) substr($data, strlen('category_'));
        $this->categoryService->handleCategorySelection($chatId, $categoryId);
    }

    private function handleSpecialistCallback(int $chatId, string $data): void
    {
        $specialistId = (int) substr($data, strlen('specialist_'));
        $this->userService->handleSpecialistSection($chatId, $specialistId);
    }

    private function handleSpecialistServiceCallback(int $chatId, string $data): void
    {
        $specialistId = (int) substr($data, strlen('specialist_services_'));
        $this->specialistService->handleSpecialistServicesSection($chatId, $specialistId);
    }

    // Service tugmasi bosilganda BookingService ishga tushadi
    private function handleServiceCallback(int $chatId, string $data): void
    {
        $serviceId = (int) substr($data, strlen('service_'));
        $specialistId = $this->getSpecialistIdByService($serviceId);

        $this->bookingService->sendAvailableTimes($chatId, $specialistId, $serviceId);
    }

    // Inline soat tugmasi bosilganda bron qilish
    private function handleBookingCallback(int $chatId, string $data): void
    {
        // format: book_{scheduleId}_{serviceId}_{time}
        $parts = explode('_', $data);
        if (count($parts) < 4) return;

        [, $scheduleId, $serviceId, $time] = $parts;
        $this->bookingService->createBooking($chatId, (int)$scheduleId, (int)$serviceId, $time);
    }

    // Pagination: keyingi/oldingi kun tugmalari
    private function handleScheduleDayCallback(int $chatId, string $data): void
    {
        // format: day_{work_date}_{serviceId}
        $parts = explode('_', $data);
        if (count($parts) < 3) return;

        [, $workDate, $serviceId] = $parts;
        $specialistId = $this->getSpecialistIdByService((int)$serviceId);

        $this->bookingService->sendAvailableTimes($chatId, $specialistId, (int)$serviceId, $workDate);
    }

    private function getSpecialistIdByService(int $serviceId): int
    {
        return \App\Models\Service::findOrFail($serviceId)->user_id;
    }
}
