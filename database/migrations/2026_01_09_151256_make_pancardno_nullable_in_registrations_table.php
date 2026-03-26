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
        Schema::table('registrations', function (Blueprint $table) {
            // First, remove the unique constraint
            $table->dropUnique(['pancardno']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            // Make the column nullable (this allows NULL values)
            $table->string('pancardno', 10)->nullable()->change();
        });

        // Now update existing empty strings to NULL (after column is nullable)
        \DB::table('registrations')
            ->where('pancardno', '')
            ->update(['pancardno' => null]);

        Schema::table('registrations', function (Blueprint $table) {
            // Add back the unique constraint (NULL values are allowed in unique columns)
            $table->unique('pancardno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Remove unique constraint
            $table->dropUnique(['pancardno']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            // Make column NOT NULL and add unique constraint back
            $table->string('pancardno', 10)->nullable(false)->change();
        });

        Schema::table('registrations', function (Blueprint $table) {
            // Add back unique constraint
            $table->unique('pancardno');
        });
    }
};
