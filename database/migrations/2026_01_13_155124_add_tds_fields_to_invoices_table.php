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
            $table->decimal('tds_percentage', 5, 2)->default(0)->after('gst_amount');
            $table->decimal('tds_amount', 10, 2)->default(0)->after('tds_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tds_percentage', 'tds_amount']);
        });
    }
};
