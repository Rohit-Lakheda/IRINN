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
        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->string('status_from'); // Previous status
            $table->string('status_to'); // New status
            $table->string('changed_by_type'); // admin, superadmin
            $table->unsignedBigInteger('changed_by_id'); // ID of admin/superadmin
            $table->text('notes')->nullable(); // Notes or rejection reason
            $table->timestamps();
            
            $table->index('application_id');
            $table->index(['changed_by_type', 'changed_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_status_history');
    }
};
