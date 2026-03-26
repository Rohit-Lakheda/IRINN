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
        Schema::create('ticket_escalation_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('ix_head_after_hours')->default(6);
            $table->unsignedInteger('ceo_after_hours')->default(24);
            $table->foreignId('updated_by')->nullable()->constrained('superadmins')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_escalation_settings');
    }
};
