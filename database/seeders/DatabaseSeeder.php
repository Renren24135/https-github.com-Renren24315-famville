<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            StaffUserSeeder::class,
            LaneSeeder::class,
            BilliardTableSeeder::class,
            BilliardRateTierSeeder::class,
            SettingSeeder::class,
            PayMongoSystemUserSeeder::class,
        ]);

        $demoCustomer = User::query()->updateOrCreate(
            ['email' => 'customer@famville.test'],
            [
                'name' => 'Demo Customer',
                'phone' => '09150000003',
                'password' => Hash::make('Customer@12345'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $demoCustomer->syncRoles(['customer']);
    }
}
