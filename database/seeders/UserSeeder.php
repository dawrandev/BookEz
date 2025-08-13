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
            'name' => 'Meyirimov',
            'login' => 'meyirimov',
            'phone' => '+998911233212',
            'password' => Hash::make('meyirimov'),
            'category_id' => 1
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
