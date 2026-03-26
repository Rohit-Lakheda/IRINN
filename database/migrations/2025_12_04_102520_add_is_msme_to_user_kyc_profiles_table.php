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
            if (! Schema::hasColumn('user_kyc_profiles', 'is_msme')) {
                $table->boolean('is_msme')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_kyc_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_kyc_profiles', 'is_msme')) {
                $table->dropColumn('is_msme');
            }
        });
    }
};
