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
        Schema::create('plan_change_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_change_request_id')->constrained('plan_change_requests')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->json('old_data')->nullable(); // Old port capacity, billing plan, amount
            $table->json('new_data')->nullable(); // New port capacity, billing plan, amount
            $table->string('action'); // 'requested', 'approved', 'rejected', 'cancelled', 'applied'
            $table->string('performed_by')->nullable(); // User/Admin name or ID
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('plan_change_request_id');
            $table->index('application_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_change_history');
    }
};
