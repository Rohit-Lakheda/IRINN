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
        Schema::create('gst_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->string('gstin', 15)->index();
            $table->string('request_id')->unique();
            $table->string('status')->default('in_progress'); // in_progress, completed, failed
            $table->boolean('is_verified')->default(false);
            $table->json('verification_data')->nullable(); // Full API response data
            $table->string('legal_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('pan')->nullable();
            $table->string('state')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('gst_type')->nullable();
            $table->string('company_status')->nullable();
            $table->text('primary_address')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'gstin']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gst_verifications');
    }
};
