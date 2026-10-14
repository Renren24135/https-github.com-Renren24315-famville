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
        Schema::create('cash_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('count_1')->default(0);
            $table->unsignedInteger('count_5')->default(0);
            $table->unsignedInteger('count_10')->default(0);
            $table->unsignedInteger('count_20')->default(0);
            $table->unsignedInteger('count_100')->default(0);
            $table->unsignedInteger('count_200')->default(0);
            $table->unsignedInteger('count_500')->default(0);
            $table->unsignedInteger('count_1000')->default(0);
            $table->decimal('total_counted', 10, 2);
            $table->decimal('expected_amount', 10, 2)->default(0);
            $table->decimal('difference', 10, 2)->default(0);
            $table->timestamp('counted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_counts');
    }
};
