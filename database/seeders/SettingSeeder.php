<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'company_name' => 'Famville Koronadal Bowling and Billiards',
            'company_address' => 'Famville, Brgy. Morales, Koronadal City, South Cotabato',
            'company_phone' => '0912-345-6789',
            'company_email' => 'contact@famville.test',
            'company_facebook' => 'facebook.com/famvillekoronadal',
            'opening_hours' => '2:00 PM - 10:00 PM, Daily',
            'gcash_number' => '0909-582-6296',
            'gcash_account_name' => 'GE*A L.',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
