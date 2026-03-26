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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id')->unique(); // Unique ticket identifier (e.g., TKT-2025-001)
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->enum('type', ['technical', 'billing', 'general_complaint', 'feedback', 'suggestion', 'request', 'enquiry'])->default('general_complaint');
            $table->string('subject')->nullable();
            $table->text('description');
            $table->enum('status', ['open', 'assigned', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('admins')->onDelete('set null');
            $table->foreignId('assigned_by')->nullable()->constrained('superadmins')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('ticket_id');
            $table->index('status');
            $table->index('type');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

