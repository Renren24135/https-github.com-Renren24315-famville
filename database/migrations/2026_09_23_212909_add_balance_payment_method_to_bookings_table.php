<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // The customer's stated preference for paying the remaining
            // balance (the session price) at the counter — separate from
            // reservation_fee_method, which is what pays the 10% fee itself.
            $table->string('balance_payment_method')->nullable()->after('reservation_fee_paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('balance_payment_method');
        });
    }
};
