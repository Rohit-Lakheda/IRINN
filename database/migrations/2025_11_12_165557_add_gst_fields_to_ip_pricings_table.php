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
        Schema::table('ip_pricings', function (Blueprint $table) {
            // Add new GST and pricing fields
            $table->decimal('amount', 10, 2)->nullable()->after('fixed_price');
            $table->decimal('gst_percentage', 5, 2)->nullable()->after('amount');
            $table->decimal('igst', 10, 2)->nullable()->after('gst_percentage');
            $table->decimal('cgst', 10, 2)->nullable()->after('igst');
            $table->decimal('sgst', 10, 2)->nullable()->after('cgst');
            $table->decimal('price', 10, 2)->nullable()->after('sgst');
            
            // Add effective date fields for scheduled pricing
            $table->date('effective_from')->nullable()->after('price');
            $table->date('effective_until')->nullable()->after('effective_from');
            
            // Add payment type reference (will be added after payment_types table exists)
            $table->unsignedBigInteger('payment_type_id')->nullable()->after('effective_until');
            
            // Make old calculation fields nullable (for backward compatibility)
            $table->decimal('base_price', 10, 2)->nullable()->change();
            $table->decimal('multiplier', 5, 2)->nullable()->change();
            $table->integer('log_base')->nullable()->change();
            
            // Remove unique constraint on ip_type and size to allow multiple prices
            $table->dropUnique(['ip_type', 'size']);
            
            // Add index for effective dates
            $table->index(['effective_from', 'effective_until']);
            $table->index('payment_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_pricings', function (Blueprint $table) {
            $table->dropForeign(['payment_type_id']);
            $table->dropIndex(['effective_from', 'effective_until']);
            $table->dropIndex(['payment_type_id']);
            
            $table->dropColumn([
                'amount',
                'gst_percentage',
                'igst',
                'cgst',
                'sgst',
                'price',
                'effective_from',
                'effective_until',
                'payment_type_id',
            ]);
            
            $table->decimal('base_price', 10, 2)->nullable(false)->change();
            $table->decimal('multiplier', 5, 2)->nullable(false)->change();
            $table->integer('log_base')->nullable(false)->change();
            
            $table->unique(['ip_type', 'size']);
        });
    }
};
