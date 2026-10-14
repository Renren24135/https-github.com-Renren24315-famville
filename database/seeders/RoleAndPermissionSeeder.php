<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage-lanes',
            'manage-tables',
            'manage-rates',
            'manage-closures',
            'manage-bookings',
            'process-pos',
            'view-reports',
            'send-reports',
            'manage-users',
            'manage-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($permissions);

        $cashier = Role::findOrCreate('cashier', 'web');
        $cashier->syncPermissions([
            'manage-bookings',
            'process-pos',
            'view-reports',
            'send-reports',
        ]);

        Role::findOrCreate('customer', 'web');
    }
}
