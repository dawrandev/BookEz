<?php

namespace App\Services\Telegram;

class UserService
{
    public function showSpecialists($chatId, $category)
    {
        $specialists = $this->getActiveSpecialists($category->id, $chatId);

        if ($specialists->isEmpty()) {
            $this->sendmessage($chatId, 'Hazirshe specialistlar joq');
            return;
        }
    }

    public function getActiveSpecialists($categoryId, $chatId)
    {
        // 
    }
}
