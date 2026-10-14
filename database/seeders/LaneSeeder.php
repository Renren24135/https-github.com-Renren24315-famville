<?php

namespace Database\Seeders;

use App\Models\Lane;
use Illuminate\Database\Seeder;

class LaneSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Lane 01', 'Lane 02', 'Lane 03', 'Lane 04', 'Lane 05', 'Lane 06', 'Lane 07', 'Lane 08', 'Lane 09', 'Lane 10'] as $name) {
            Lane::query()->updateOrCreate(
                ['name' => $name],
                ['price_per_scoresheet' => 250.00, 'status' => 'available']
            );
        }
    }
}
