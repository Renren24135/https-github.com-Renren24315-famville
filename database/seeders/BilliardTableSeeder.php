<?php

namespace Database\Seeders;

use App\Models\BilliardTable;
use Illuminate\Database\Seeder;

class BilliardTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['T-01', 'T-02', 'T-03', 'T-04', 'T-05', 'T-06', 'T-07', 'T-08'] as $name) {
            BilliardTable::query()->updateOrCreate(
                ['name' => $name],
                ['status' => 'available']
            );
        }
    }
}
