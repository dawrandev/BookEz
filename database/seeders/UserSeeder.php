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
            'category_id' => 1,
            'description' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit. Nostrum pariatur, dolor magnam mollitia sunt eos voluptatibus? Perferendis, deserunt, iusto explicabo saepe repellat quae libero neque, magni labore voluptate ducimus exercitationem.',
            'status' => 'active'
        ])->assignRole('specialist');

        User::firstOrCreate(
            ['login' => 'admin'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin'),
                'status' => 'active',
            ]
        )->syncRoles(['admin']);
    }
}
