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
        Schema::create('application_gst_update_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->string('old_gstin')->nullable();
            $table->string('new_gstin');
            $table->string('old_company_name')->nullable();
            $table->string('new_company_name');
            $table->decimal('similarity_score', 5, 2)->nullable(); // Company name similarity percentage
            $table->json('old_kyc_details')->nullable();
            $table->json('new_kyc_details')->nullable();
            $table->foreignId('gst_verification_id')->nullable()->constrained('gst_verifications')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index('application_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_gst_update_requests');
    }
};
