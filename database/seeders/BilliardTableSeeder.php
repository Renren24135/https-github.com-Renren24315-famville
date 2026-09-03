<?php

namespace Database\Seeders;

use App\Models\BilliardTable;
use Illuminate\Database\Seeder;

class BilliardTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Table 1', 'Table 2', 'Table 3'] as $name) {
            BilliardTable::query()->updateOrCreate(
                ['name' => $name],
                ['status' => 'available']
            );
        }
    }
}
