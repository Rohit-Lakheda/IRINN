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
        Schema::table('plan_change_requests', function (Blueprint $table) {
            $table->boolean('adjustment_applied')->default(false)->after('effective_from');
            $table->foreignId('adjustment_invoice_id')->nullable()->after('adjustment_applied')->constrained('invoices')->onDelete('set null');
            $table->index('adjustment_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_change_requests', function (Blueprint $table) {
            $table->dropForeign(['adjustment_invoice_id']);
            $table->dropIndex(['adjustment_applied']);
            $table->dropColumn(['adjustment_applied', 'adjustment_invoice_id']);
        });
    }
};

