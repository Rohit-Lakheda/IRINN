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
        Schema::table('profile_update_requests', function (Blueprint $table) {
            $table->json('submitted_data')->nullable()->after('requested_changes');
            $table->timestamp('submitted_at')->nullable()->after('submitted_data');
            $table->boolean('update_approved')->default(false)->after('submitted_at');
            $table->timestamp('update_approved_at')->nullable()->after('update_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_update_requests', function (Blueprint $table) {
            $table->dropColumn(['submitted_data', 'submitted_at', 'update_approved', 'update_approved_at']);
        });
    }
};
