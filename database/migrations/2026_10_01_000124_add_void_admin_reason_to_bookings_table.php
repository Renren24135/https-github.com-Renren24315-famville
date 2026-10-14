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
            // cancel_reason already carries the cashier's reason for
            // flagging the void — this is the admin's own explanation for
            // the final decision, which is what actually gets emailed to
            // the customer.
            $table->string('void_admin_reason', 500)->nullable()->after('cancel_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('void_admin_reason');
        });
    }
};
