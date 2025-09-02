<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Стандарт',
                'description' => 'Базовый тариф для специалистов',
                'price' => 200000, // 200,000 UZS
                'features' => [
                    'Доступ к платформе',
                    'Базовая поддержка',
                    'Стандартные инструменты'
                ],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Премиум',
                'description' => 'Расширенный тариф с дополнительными возможностями',
                'price' => 350000, // 350,000 UZS
                'features' => [
                    'Все возможности Стандарт',
                    'Приоритетная поддержка',
                    'Расширенные инструменты',
                    'Аналитика'
                ],
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'VIP',
                'description' => 'Максимальный тариф для топ-специалистов',
                'price' => 500000, // 500,000 UZS
                'features' => [
                    'Все возможности Премиум',
                    'Персональный менеджер',
                    'Эксклюзивные инструменты',
                    'Детальная аналитика',
                    'Индивидуальные настройки'
                ],
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Льготный',
                'description' => 'Специальный тариф со скидкой',
                'price' => 150000, // 150,000 UZS
                'features' => [
                    'Базовые возможности',
                    'Ограниченная поддержка'
                ],
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 0,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}

// DatabaseSeeder.php da chaqirish:
// $this->call(SubscriptionPlanSeeder::class);