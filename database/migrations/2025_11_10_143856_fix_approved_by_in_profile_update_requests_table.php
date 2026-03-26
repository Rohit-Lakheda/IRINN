<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old foreign key constraint
        Schema::table('profile_update_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
        });

        // Change the column to reference admins table
        Schema::table('profile_update_requests', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->change();
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_update_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
        });

        Schema::table('profile_update_requests', function (Blueprint $table) {
            $table->foreign('approved_by')->references('id')->on('registrations')->onDelete('set null');
        });
    }
};
