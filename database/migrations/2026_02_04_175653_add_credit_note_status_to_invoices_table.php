<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to add 'credit_note' status
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('pending', 'paid', 'overdue', 'cancelled', 'credit_note') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending'");
    }
};
