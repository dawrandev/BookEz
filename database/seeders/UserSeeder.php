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
            'description' => 'Men 3 jıldan aslam tájiriybege ie professional stilistpen. Shash turmagi, make-up hám imidj jaratıwda kóplegen klientlerim menen isleskenmen.',
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
