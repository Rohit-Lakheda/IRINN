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
            $table->decimal('forwarded_amount', 10, 2)->nullable()->after('carry_forward_amount');
            $table->date('forwarded_to_invoice_date')->nullable()->after('forwarded_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['forwarded_amount', 'forwarded_to_invoice_date']);
        });
    }
};

