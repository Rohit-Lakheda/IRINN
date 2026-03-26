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
        Schema::create('udyam_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->string('uam_number')->index();
            $table->string('request_id')->unique();
            $table->string('status')->default('in_progress'); // in_progress, completed, failed
            $table->boolean('is_verified')->default(false);
            $table->json('verification_data')->nullable(); // Full API response data
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'uam_number']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('udyam_verifications');
    }
};
