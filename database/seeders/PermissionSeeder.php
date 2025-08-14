<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::firstOrCreate([
            'name' => 'view-any Role',
            'guard_name' => 'web',
        ]);

        Permission::firstOrCreate([
            'name' => 'view-any Permission',
            'guard_name' => 'web',
        ]);

        $permissions = [
            'view-any Service (web)',
            'create Service (web)',
            'update Service (web)',
            'delete Service (web)',
            'delete-any Service (web)',
            'force-delete Service (web)',
            'force-delete-any Service (web)',
            'replicate Service (web)',
            'reorder Service (web)',
            'restore Service (web)',
            'restore-any Service (web)',
            'view Service (web)',

            'view-any Schedule (web)',
            'create Schedule (web)',
            'update Schedule (web)',
            'delete Schedule (web)',
            'delete-any Schedule (web)',
            'force-delete Schedule (web)',
            'force-delete-any Schedule (web)',
            'replicate Schedule (web)',
            'reorder Schedule (web)',
            'restore Schedule (web)',
            'restore-any Schedule (web)',
            'view Schedule (web)',

            'view-any ScheduleBreak (web)',
            'create ScheduleBreak (web)',
            'update ScheduleBreak (web)',
            'delete ScheduleBreak (web)',
            'delete-any ScheduleBreak (web)',
            'force-delete ScheduleBreak (web)',
            'force-delete-any ScheduleBreak (web)',
            'replicate ScheduleBreak (web)',
            'reorder ScheduleBreak (web)',
            'restore ScheduleBreak (web)',
            'restore-any ScheduleBreak (web)',
            'view ScheduleBreak (web)',

            'view-any Client (web)',
            'view-any Schedule (web)',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $specialistRole = Role::firstOrCreate(['name' => 'specialist', 'guard_name' => 'web']);

        $specialistRole->syncPermissions($permissions);

        $this->command->info('Permissions created and assigned to specialist role successfully!');
    }
}
