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
        Schema::create('admin_application_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            // Ensure one admin can only mark one application as read once
            $table->unique(['admin_id', 'application_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_application_reads');
    }
};
