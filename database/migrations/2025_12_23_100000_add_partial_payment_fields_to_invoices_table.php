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
            $table->decimal('paid_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('balance_amount', 10, 2)->nullable()->after('paid_amount');
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending')->after('status');
            $table->decimal('carry_forward_amount', 10, 2)->default(0)->after('balance_amount');
            $table->boolean('has_carry_forward')->default(false)->after('carry_forward_amount');
            $table->index('payment_status');
        });
        
        // Update existing records: set balance_amount = total_amount for pending invoices
        \Illuminate\Support\Facades\DB::statement("UPDATE invoices SET balance_amount = total_amount WHERE status = 'pending'");
        \Illuminate\Support\Facades\DB::statement("UPDATE invoices SET balance_amount = 0, payment_status = 'paid' WHERE status = 'paid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'paid_amount',
                'balance_amount',
                'payment_status',
                'carry_forward_amount',
                'has_carry_forward',
            ]);
        });
    }
};

