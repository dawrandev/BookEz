<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Admin rolini yaratish
        $adminRole = Role::create(['name' => 'admin']);

        // Specialist rolini yaratish
        $specialistRole = Role::create(['name' => 'specialist']);

        // Permissions yaratish

    }
}
