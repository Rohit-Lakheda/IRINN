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
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('cascade');
            $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable(); // mime type
            $table->integer('file_size')->nullable(); // in bytes
            $table->timestamps();
            
            $table->index('ticket_id');
            $table->index('ticket_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};

