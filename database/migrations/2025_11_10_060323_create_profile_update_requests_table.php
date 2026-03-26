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
        Schema::create('profile_update_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('registrations')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('requested_changes')->nullable(); // JSON or text field for requested changes
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('registrations')->onDelete('set null');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_update_requests');
    }
};
