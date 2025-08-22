<?php

namespace App\Services\Telegram;

use App\Models\Category;
use Illuminate\Support\Collection;
use Telegram\Bot\Laravel\Facades\Telegram;

class CategoryService
{
    public function __construct(protected UserService $userService)
    {
        //
    }

    public function showCategoriesToUser(int $chatId): void
    {
        $categories = $this->getAllActiveCategories();

        if ($categories->isEmpty()) {
            $this->sendMessage($chatId, 'Hazirshe kategoriyalar joq');
            return;
        }

        $keyboard = $this->buildCategoryKeyboard($categories);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => '📂 Kategoriyani tanlań:',
            'reply_markup' => $keyboard
        ]);
    }

    public function getAllActiveCategories(): Collection
    {
        return Category::all();
    }

    public function getCategoryById(int $categoryId): ?Category
    {
        return Category::where('id', $categoryId)
            ->first();
    }

    public function handleCategorySelection(int $chatId, int $categoryId): void
    {
        $category = $this->getCategoryById($categoryId);

        if (!$category) {
            $this->sendMessage($chatId, 'Kategoriya tabılmadı.');
            return;
        }

        // Category ID ni to'g'ri formatda uzatish
        $this->showSpecialists($chatId, $category->id);
    }

    private function buildCategoryKeyboard(Collection $categories): string
    {
        $keyboard = [];
        $buttonsPerRow = 2;
        $buttons = [];

        foreach ($categories as $category) {
            $buttons[] = [
                'text' => $category->name . ($category->emoji ? " {$category->emoji}" : ''),
                'callback_data' => "category_{$category->id}"
            ];

            if (count($buttons) >= $buttonsPerRow) {
                $keyboard[] = $buttons;
                $buttons = [];
            }
        }

        if (!empty($buttons)) {
            $keyboard[] = $buttons;
        }

        return json_encode([
            'inline_keyboard' => $keyboard
        ]);
    }

    public function showSpecialists($chatId, $categoryId)
    {
        // Category ID ni "specialists_" prefiksi bilan birga uzatish
        $data = "specialists_{$categoryId}";
        return $this->userService->showSpecialists($chatId, $data);
    }

    private function sendMessage(int $chatId, string $text): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}
