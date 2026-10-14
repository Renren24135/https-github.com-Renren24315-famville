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
            // Optional — a customer who already sent the GCash balance ahead
            // of arrival can leave a reference here for the cashier to spot;
            // unlike reservation_fee_reference it's never required, since
            // balance_payment_method is only a stated preference.
            $table->string('balance_payment_reference')->nullable()->after('balance_payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('balance_payment_reference');
        });
    }
};
