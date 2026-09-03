<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@famville.test'],
            [
                'name' => 'Famville Admin',
                'phone' => '09150000001',
                'password' => Hash::make('Admin@12345'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['admin']);

        $cashier = User::query()->updateOrCreate(
            ['email' => 'cashier@famville.test'],
            [
                'name' => 'Famville Cashier',
                'phone' => '09150000002',
                'password' => Hash::make('Cashier@12345'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $cashier->syncRoles(['cashier']);
    }
}
