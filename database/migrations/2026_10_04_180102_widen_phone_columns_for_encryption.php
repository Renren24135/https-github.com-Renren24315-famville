<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen phone columns from varchar(255) to text before applying
     * Laravel's 'encrypted' cast — an encrypted value runs 3-4x longer
     * than the plaintext phone number and will not reliably fit a
     * varchar(255) column.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->text('customer_phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('customer_phone')->nullable()->change();
        });
    }
};
