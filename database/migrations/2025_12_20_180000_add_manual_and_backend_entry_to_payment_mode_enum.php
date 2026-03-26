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
        // Modify the ENUM to include 'manual' and 'backend_entry'
        DB::statement("ALTER TABLE `payment_transactions` MODIFY COLUMN `payment_mode` ENUM('test', 'live', 'manual', 'backend_entry') DEFAULT 'test'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM values
        // Note: This will fail if there are existing records with 'manual' or 'backend_entry'
        DB::statement("ALTER TABLE `payment_transactions` MODIFY COLUMN `payment_mode` ENUM('test', 'live') DEFAULT 'test'");
    }
};
