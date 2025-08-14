<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'user_id' => 1,
                'name' => 'Жас балалар шашы',
                'duration_minutes' => 30,
                'price' => '50000'
            ],
            [
                'user_id' => 1,
                'name' => 'Оспиримлер шашы',
                'duration_minutes' => 60,
                'price' => '80000'
            ]
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
