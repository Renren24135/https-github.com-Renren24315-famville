<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('bookable_type');
            $table->unsignedBigInteger('bookable_id');

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();

            $table->string('source')->default('online');
            $table->string('status')->default('pending');

            $table->date('booking_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->unsignedInteger('scoresheets')->nullable();
            $table->unsignedInteger('hours')->nullable();

            $table->timestamp('session_started_at')->nullable();
            $table->timestamp('session_ends_at')->nullable();

            $table->decimal('total_amount', 8, 2)->default(0);
            $table->string('payment_status')->default('unpaid');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->text('notes')->nullable();
            $table->string('cancel_reason')->nullable();

            $table->timestamps();

            $table->index(['bookable_type', 'bookable_id']);
            $table->index(['booking_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
