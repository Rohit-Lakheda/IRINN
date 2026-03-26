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
            $table->string('manual_payment_id')->nullable()->after('payu_payment_link');
            $table->text('manual_payment_notes')->nullable()->after('manual_payment_id');
            $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['manual_payment_id', 'manual_payment_notes', 'paid_by']);
        });
    }
};

