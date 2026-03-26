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
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('billing_start_date')->nullable()->after('billing_period');
            $table->date('billing_end_date')->nullable()->after('billing_start_date');
            $table->index(['application_id', 'billing_period']); // For duplicate check
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['application_id', 'billing_period']);
            $table->dropColumn(['billing_start_date', 'billing_end_date']);
        });
    }
};

