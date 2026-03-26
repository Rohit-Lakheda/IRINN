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
            $table->json('registration_details')->nullable()->after('application_data');
            $table->json('kyc_details')->nullable()->after('registration_details');
            $table->json('authorized_representative_details')->nullable()->after('kyc_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'registration_details',
                'kyc_details',
                'authorized_representative_details',
            ]);
        });
    }
};

