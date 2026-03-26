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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->string('application_id')->unique(); // Unique application identifier
            $table->string('status')->default('pending'); // pending, processor_approved, finance_approved, finance_review, processor_review, approved, rejected
            $table->json('application_data')->nullable(); // Store application form data (will be populated when form is created)
            $table->text('rejection_reason')->nullable(); // Reason for rejection
            $table->foreignId('current_processor_id')->nullable()->constrained('admins')->onDelete('set null');
            $table->foreignId('current_finance_id')->nullable()->constrained('admins')->onDelete('set null');
            $table->foreignId('current_technical_id')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
