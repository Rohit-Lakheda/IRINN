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
            $table->string('service_status')->default('live')->after('billing_cycle'); // live|suspended|disconnected
            $table->date('billing_resume_date')->nullable()->after('service_status'); // next billing should restart from here

            $table->date('suspended_from')->nullable()->after('billing_resume_date');
            $table->date('disconnected_at')->nullable()->after('suspended_from'); // last billable date
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'service_status',
                'billing_resume_date',
                'suspended_from',
                'disconnected_at',
            ]);
        });
    }
};
