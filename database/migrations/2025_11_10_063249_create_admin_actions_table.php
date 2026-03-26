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
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->string('action_type'); // approved, rejected, replied, sent_message, etc.
            $table->string('actionable_type'); // Registration, ProfileUpdateRequest, Message, etc.
            $table->unsignedBigInteger('actionable_id'); // ID of the related model
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['admin_id', 'created_at']);
            $table->index(['actionable_type', 'actionable_id']);
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};
