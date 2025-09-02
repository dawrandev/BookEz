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
        $specialist = User::firstOrCreate([
            'login' => 'meyirimov'
        ], [
            'name' => 'Meyirimov',
            'phone' => '+998911233212',
            'password' => Hash::make('meyirimov'),
            'category_id' => 1,
            'description' => '...',
            'status' => 'active'
        ]);
        $specialist->syncRoles(['specialist']);

        $admin = User::firstOrCreate([
            'login' => 'admin'
        ], [
            'name' => 'Admin',
            'password' => Hash::make('admin'),
            'status' => 'active',
        ]);
        $admin->syncRoles(['admin']);
    }
}
