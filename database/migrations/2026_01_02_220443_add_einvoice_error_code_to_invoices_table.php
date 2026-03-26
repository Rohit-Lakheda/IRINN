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
            if (!Schema::hasColumn('invoices', 'einvoice_error_code')) {
                $table->string('einvoice_error_code')->nullable()->after('einvoice_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'einvoice_error_code')) {
                $table->dropColumn('einvoice_error_code');
            }
        });
    }
};
