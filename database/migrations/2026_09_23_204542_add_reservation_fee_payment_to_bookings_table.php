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
            $table->string('reservation_fee_method')->nullable()->after('reservation_fee');
            $table->string('reservation_fee_reference')->nullable()->after('reservation_fee_method');
            $table->string('reservation_fee_status')->default('unpaid')->after('reservation_fee_reference');
            $table->timestamp('reservation_fee_paid_at')->nullable()->after('reservation_fee_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'reservation_fee_method',
                'reservation_fee_reference',
                'reservation_fee_status',
                'reservation_fee_paid_at',
            ]);
        });
    }
};
