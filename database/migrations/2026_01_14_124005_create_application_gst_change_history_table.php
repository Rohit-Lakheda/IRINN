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
        Schema::create('application_gst_change_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->string('old_gstin')->nullable();
            $table->string('new_gstin');
            $table->json('old_kyc_details')->nullable(); // Old kyc_details snapshot
            $table->json('new_kyc_details')->nullable(); // New kyc_details snapshot
            $table->string('changed_by_type')->default('user'); // 'user', 'admin', 'superadmin', 'system'
            $table->unsignedBigInteger('changed_by_id')->nullable(); // User/Admin ID
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('application_id');
            $table->index('user_id');
            $table->index('changed_by_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_gst_change_history');
    }
};
