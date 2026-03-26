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
        Schema::create('ix_location_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ix_location_id')->constrained('ix_locations')->onDelete('cascade');
            $table->json('old_data')->nullable(); // Store old location data
            $table->json('new_data')->nullable(); // Store new location data
            $table->string('updated_by')->nullable(); // Who made the change (admin/superadmin name or ID)
            $table->string('change_type'); // 'created', 'updated', 'activated', 'deactivated', 'deleted'
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('ix_location_id');
            $table->index('change_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ix_location_history');
    }
};
