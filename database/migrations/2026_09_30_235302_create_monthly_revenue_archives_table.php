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
        Schema::create('monthly_revenue_archives', function (Blueprint $table) {
            $table->id();
            // First day of the archived month — unique so re-running the
            // archiving command for the same month safely updates in place
            // instead of creating a duplicate row.
            $table->date('month')->unique();
            $table->unsignedInteger('total_transactions')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('lane_revenue', 12, 2)->default(0);
            $table->decimal('table_revenue', 12, 2)->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_revenue_archives');
    }
};
