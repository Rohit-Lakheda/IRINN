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
        if (! Schema::hasColumn('admin_application_reads', 'role')) {
            Schema::table('admin_application_reads', function (Blueprint $table) {
                // Add role column to track read status per admin role.
                // We intentionally do NOT change the existing unique index
                // because it is referenced by a foreign key constraint in MySQL.
                // Existing rows (with role = null) will be treated as global reads
                // for all roles of that admin.
                $table->string('role')->nullable()->after('application_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('admin_application_reads', 'role')) {
            Schema::table('admin_application_reads', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
