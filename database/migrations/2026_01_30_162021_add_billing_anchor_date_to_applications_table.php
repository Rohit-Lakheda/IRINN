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
            if (! Schema::hasColumn('applications', 'billing_anchor_date')) {
                $table->date('billing_anchor_date')->nullable()->after('service_activation_date')->comment('Date (usually 1st of month) from which billing cycles are anchored');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('billing_anchor_date');
        });
    }
};
