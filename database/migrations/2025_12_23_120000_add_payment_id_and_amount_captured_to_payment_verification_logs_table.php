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
        Schema::table('payment_verification_logs', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->after('billing_period');
            $table->decimal('amount_captured', 10, 2)->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_verification_logs', function (Blueprint $table) {
            $table->dropColumn(['payment_id', 'amount_captured']);
        });
    }
};

