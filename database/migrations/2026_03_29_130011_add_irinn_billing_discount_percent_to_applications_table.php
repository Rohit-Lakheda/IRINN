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
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'irinn_billing_discount_percent')) {
                $table->decimal('irinn_billing_discount_percent', 5, 2)
                    ->default(0)
                    ->after('irinn_resource_fee_amount')
                    ->comment('Applies to all future annual IRINN billing invoices for this application');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'irinn_billing_discount_percent')) {
                $table->dropColumn('irinn_billing_discount_percent');
            }
        });
    }
};
