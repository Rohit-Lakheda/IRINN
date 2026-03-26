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
        Schema::table('admin_actions', function (Blueprint $table) {
            // Make admin_id nullable to support SuperAdmin actions
            $table->foreignId('admin_id')->nullable()->change();
            // Add superadmin_id field
            $table->foreignId('superadmin_id')->nullable()->after('admin_id')->constrained('superadmins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_actions', function (Blueprint $table) {
            $table->dropForeign(['superadmin_id']);
            $table->dropColumn('superadmin_id');
            $table->foreignId('admin_id')->nullable(false)->change();
        });
    }
};
