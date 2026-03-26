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
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('email_otp', 6)->nullable()->after('email');
            $table->boolean('email_verified')->default(false)->after('email_otp');
            $table->string('mobile_otp', 6)->nullable()->after('mobile');
            $table->boolean('mobile_verified')->default(false)->after('mobile_otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['email_otp', 'email_verified', 'mobile_otp', 'mobile_verified']);
        });
    }
};
