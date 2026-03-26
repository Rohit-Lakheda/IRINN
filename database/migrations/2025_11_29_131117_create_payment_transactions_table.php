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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
            $table->string('transaction_id')->unique();
            $table->string('payment_id')->nullable();
            $table->enum('payment_mode', ['test', 'live'])->default('test');
            $table->enum('payment_status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('INR');
            $table->string('product_info')->nullable();
            $table->text('response_message')->nullable();
            $table->json('payu_response')->nullable();
            $table->string('hash')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'payment_status']);
            $table->index('transaction_id');
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
