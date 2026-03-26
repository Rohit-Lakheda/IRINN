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
        Schema::create('payment_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('verified_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->string('verification_type')->default('initial'); // initial, recurring
            $table->string('billing_period')->nullable(); // e.g., "2025-01", "2025-Q1", "2025"
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('payment_method')->nullable(); // manual, payu, etc.
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->useCurrent();
            $table->timestamps();
            
            $table->index('application_id');
            $table->index('verified_by');
            $table->index('billing_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_verification_logs');
    }
};
