<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('cash_tendered', 8, 2)->nullable()->after('amount');
            $table->decimal('change_due', 8, 2)->nullable()->after('cash_tendered');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['cash_tendered', 'change_due']);
        });
    }
};
