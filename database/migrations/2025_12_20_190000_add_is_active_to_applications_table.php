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
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('membership_id');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')->constrained('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['deactivated_by']);
            $table->dropColumn(['is_active', 'deactivated_at', 'deactivated_by']);
        });
    }
};
