<?php

namespace App\Handlers\Telegram;

use App\Services\Telegram\CategoryService;
use App\Services\Telegram\SpecialistService;
use App\Services\Telegram\UserService;
use App\Services\Telegram\BookingService;
use App\Services\Telegram\LocationService;
use App\Services\Telegram\BookingViewService;
use App\Services\Telegram\ClientService;
use Telegram\Bot\Objects\CallbackQuery;

class CallbackQueryHandler
{
    public function __construct(
        protected UserService $userService,
        protected CategoryService $categoryService,
        protected SpecialistService $specialistService,
        protected BookingService $bookingService,
        protected LocationService $locationService,
        protected BookingViewService $bookingViewService,
        protected ClientService $clientService
    ) {}

    public function handle(CallbackQuery $callbackQuery): void
    {
        $data = $callbackQuery->getData();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();

        $this->clientService->handleCallbackQuery($chatId, $data);

        $handlers = [
            'my_bookings_'         => fn() => $this->handleMyBookingsCallback($chatId, $data),
            'booking_detail_'      => fn() => $this->handleBookingDetailCallback($chatId, $data),
            'cancel_booking_'      => fn() => $this->handleCancelBookingCallback($chatId, $data),
            'specialist_services_' => fn() => $this->handleSpecialistServiceCallback($chatId, $data),
            'specialist_location_' => fn() => $this->handleLocationCallback($chatId, $data),
            'specialist_'          => fn() => $this->handleSpecialistCallback($chatId, $data),
            'category_'            => fn() => $this->handleCategoryCallback($chatId, $data),
            'service_'             => fn() => $this->handleServiceCallback($chatId, $data),
            'book_'                => fn() => $this->handleBookingCallback($chatId, $data),
            'day_'                 => fn() => $this->handleScheduleDayCallback($chatId, $data),
            'specialists'          => fn() => $this->userService->showSpecialists($chatId, $data),
            'categories'           => fn() => $this->categoryService->showCategoriesToUser($chatId),
            'main_menu'            => fn() => $this->handleMainMenuCallback($chatId),
        ];

        foreach ($handlers as $prefix => $callback) {
            if (str_starts_with($data, $prefix)) {
                $callback();
                return;
            }
        }
    }

    private function handleMyBookingsCallback(int $chatId, string $data): void
    {
        $this->bookingViewService->showMyBookings($chatId);
    }

    private function handleBookingDetailCallback(int $chatId, string $data): void
    {
        $bookingId = (int) substr($data, strlen('booking_detail_'));
        $this->bookingViewService->showBookingDetail($chatId, $bookingId);
    }

    private function handleCancelBookingCallback(int $chatId, string $data): void
    {
        $bookingId = (int) substr($data, strlen('cancel_booking_'));
        $this->bookingViewService->cancelBooking($chatId, $bookingId);
    }

    private function handleMainMenuCallback(int $chatId): void
    {
        $this->clientService->showMainMenu($chatId);
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

    private function handleServiceCallback(int $chatId, string $data): void
    {
        $serviceId = (int) substr($data, strlen('service_'));
        $specialistId = $this->getSpecialistIdByService($serviceId);

        $this->bookingService->sendAvailableTimes($chatId, $specialistId, $serviceId);
    }

    private function handleBookingCallback(int $chatId, string $data): void
    {
        $parts = explode('_', $data);
        if (count($parts) < 4) return;

        [, $scheduleId, $serviceId, $time] = $parts;
        $this->bookingService->createBooking($chatId, (int)$scheduleId, (int)$serviceId, $time);
    }

    private function handleScheduleDayCallback(int $chatId, string $data): void
    {
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

    private function handleLocationCallback(int $chatId, string $data): void
    {
        $specialistId = (int) substr($data, strlen('specialist_location_'));
        $this->locationService->sendSpecialistLocation($chatId, $specialistId);
    }
}
