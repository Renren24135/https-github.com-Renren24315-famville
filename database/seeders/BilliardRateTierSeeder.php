<?php

namespace Database\Seeders;

use App\Models\BilliardRateTier;
use Illuminate\Database\Seeder;

class BilliardRateTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['label' => 'Afternoon (2PM - 5PM)', 'start_time' => '14:00:00', 'end_time' => '17:00:00', 'rate_per_hour' => 50.00],
            ['label' => 'Evening (6PM - 10PM)', 'start_time' => '18:00:00', 'end_time' => '22:00:00', 'rate_per_hour' => 100.00],
        ];

        foreach ($tiers as $tier) {
            BilliardRateTier::query()->updateOrCreate(
                ['label' => $tier['label']],
                $tier + ['is_active' => true]
            );
        }
    }
}
