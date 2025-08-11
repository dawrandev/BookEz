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
        $permissions = [
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'view_permissions',
            'assign_roles',
            'manage_settings',
            'view_reports',
            'create_reports',
            'edit_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Admin ga barcha permissions berish
        $adminRole->givePermissionTo($permissions);

        // Specialist ga cheklangan permissions berish
        $specialistRole->givePermissionTo([
            'view_users',
            'view_reports',
            'create_reports',
            'edit_reports'
        ]);
    }
}
