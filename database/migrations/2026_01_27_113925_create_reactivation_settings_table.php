<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration may have partially run previously (table created but FK failed).
        // Make it idempotent.
        if (! Schema::hasTable('reactivation_settings')) {
            Schema::create('reactivation_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('fee_amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('INR');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('reactivation_settings', 'updated_by')) {
            Schema::table('reactivation_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('currency');
            });
        }

        // Add FK if missing
        $existingFk = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'reactivation_settings'
              AND COLUMN_NAME = 'updated_by'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        if (! $existingFk) {
            Schema::table('reactivation_settings', function (Blueprint $table) {
                $table->foreign('updated_by', 'reactivation_settings_updated_by_foreign')
                    ->references('id')
                    ->on('superadmins')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactivation_settings');
    }
};
