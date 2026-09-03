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
            'opening_hours' => '10:00 AM - 10:00 PM, Daily',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
