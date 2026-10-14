<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PayMongoSystemUserSeeder extends Seeder
{
    /**
     * `transactions.cashier_id` is a required foreign key — every row
     * assumes a human cashier confirmed the payment. A PayMongo webhook
     * confirms payment automatically, with nobody logged in, so it needs
     * a real `users` row to attribute the transaction to. This account
     * can never log in (is_active false, random unusable password, no
     * role) — it exists purely to satisfy the foreign key and to make
     * PayMongo-collected payments show up clearly in reports as their
     * own line instead of being misattributed to a human cashier.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'paymongo@system.famville.local'],
            [
                'name' => 'PayMongo (Automatic)',
                'phone' => null,
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
                'is_active' => false,
            ]
        );
    }
}
