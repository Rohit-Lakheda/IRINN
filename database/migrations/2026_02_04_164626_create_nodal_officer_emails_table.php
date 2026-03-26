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
        Schema::create('nodal_officer_emails', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Chirag Vasani", "Jignesh Patel"
            $table->string('email'); // e.g., "chirag.vasani@nixi.in"
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // For ordering in admin panel
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodal_officer_emails');
    }
};
