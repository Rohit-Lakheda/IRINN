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
        Schema::create('plan_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->string('current_port_capacity')->nullable();
            $table->string('new_port_capacity');
            $table->string('current_billing_plan')->nullable();
            $table->string('new_billing_plan')->nullable();
            $table->decimal('current_amount', 10, 2)->nullable();
            $table->decimal('new_amount', 10, 2)->nullable();
            $table->decimal('adjustment_amount', 10, 2)->default(0); // Positive for upgrade, negative for downgrade
            $table->enum('change_type', ['upgrade', 'downgrade'])->default('upgrade');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable(); // User's reason for change
            $table->text('admin_notes')->nullable(); // Admin notes
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('effective_from')->nullable(); // When the change should take effect
            $table->timestamps();
            
            $table->index('application_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('change_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_change_requests');
    }
};
