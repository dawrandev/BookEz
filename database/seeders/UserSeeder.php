<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'User',
            'login' => 'user',
            'phone' => null,
            'password' => Hash::make('user'),
            'category_id' => null
        ])->assignRole('specialist');

        User::create([
            'name' => 'Admin',
            'login' => 'admin',
            'phone' => null,
            'password' => Hash::make('admin'),
            'category_id' => null
        ])->assignRole('admin');
    }
}
