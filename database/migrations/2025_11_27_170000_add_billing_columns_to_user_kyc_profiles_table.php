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
        Schema::table('user_kyc_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_kyc_profiles', 'billing_address_source')) {
                $table->string('billing_address_source')->nullable()->after('kyc_user_agent');
            }

            if (! Schema::hasColumn('user_kyc_profiles', 'billing_address')) {
                $table->text('billing_address')->nullable()->after('billing_address_source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_kyc_profiles', 'billing_address')) {
                $table->dropColumn('billing_address');
            }

            if (Schema::hasColumn('user_kyc_profiles', 'billing_address_source')) {
                $table->dropColumn('billing_address_source');
            }
        });
    }
};


