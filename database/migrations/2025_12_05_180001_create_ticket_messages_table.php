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
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->enum('sender_type', ['user', 'admin', 'superadmin']); // Who sent the message
            $table->foreignId('sender_id')->nullable(); // ID of the sender (user_id, admin_id, or superadmin_id)
            $table->text('message');
            $table->boolean('is_internal')->default(false); // Internal notes visible only to admins
            $table->timestamps();
            
            $table->index('ticket_id');
            $table->index(['sender_type', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};

