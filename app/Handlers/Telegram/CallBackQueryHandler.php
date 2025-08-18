<?php

namespace App\Handlers\Telegram;

use App\Services\Telegram\CategoryService;
use App\Services\Telegram\SpecialistService;
use App\Services\Telegram\UserService;
use Telegram\Bot\Objects\CallbackQuery;

class CallbackQueryHandler
{
    public function __construct(
        protected UserService $userService,
        protected CategoryService $categoryService,
        protected SpecialistService $specialistService
    ) {
        //
    }

    public function handle(CallbackQuery $callbackQuery): void
    {
        $data = $callbackQuery->getData();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();

        $handlers = [
            'category_'   => fn() => $this->handleCategoryCallback($chatId, $data),
            'specialist_' => fn() => $this->handleSpecialistCallback($chatId, $data),
            'specialists' => fn() => $this->userService->showSpecialists($chatId, $data),
            'categories' => fn() => $this->categoryService->showCategoriesToUser($chatId),
            'specialist_services_' => fn() => $this->handleSpecialistServiceCallback($chatId, $data)
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
        $categoryId = substr($data, strlen('category_'));
        $this->categoryService->handleCategorySelection($chatId, $categoryId);
    }

    private function handleSpecialistCallback(int $chatId, string $data): void
    {
        $specialistId = substr($data, strlen('specialist_'));
        $this->userService->handleSpecialistSection($chatId, $specialistId);
    }

    private function handleSpecialistServiceCallback(int $chatId, string $data): void
    {
        $specialistId = substr($data, strlen('specialist_services_'));
        $this->specialistService->handleSpecialistServicesSection($chatId, $specialistId);
    }
}
