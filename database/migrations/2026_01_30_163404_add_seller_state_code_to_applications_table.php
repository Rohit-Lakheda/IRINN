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
            if (!Schema::hasColumn('applications', 'seller_state_code')) {
                $table->string('seller_state_code', 2)->nullable()->after('gst_verification_id')->comment('Manually assigned seller GST state code for invoice generation');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'seller_state_code')) {
                $table->dropColumn('seller_state_code');
            }
        });
    }
};
