<?php

namespace Database\Seeders;

use App\Models\Lane;
use Illuminate\Database\Seeder;

class LaneSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Lane 1', 'Lane 2'] as $name) {
            Lane::query()->updateOrCreate(
                ['name' => $name],
                ['price_per_scoresheet' => 250.00, 'status' => 'available']
            );
        }
    }
}
