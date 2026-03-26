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
            // JSON column to store SignedInvoice and SignedQRCode
            $table->json('einvoice_signed_data')->nullable()->after('pdf_path');
            
            // Common e-invoice API response fields (excluding SignedInvoice and SignedQRCode)
            $table->string('einvoice_irn')->nullable()->after('einvoice_signed_data');
            $table->string('einvoice_ack_no')->nullable()->after('einvoice_irn');
            $table->string('einvoice_ack_date')->nullable()->after('einvoice_ack_no');
            $table->string('einvoice_status')->nullable()->after('einvoice_ack_date');
            $table->text('einvoice_error_message')->nullable()->after('einvoice_status');
            $table->json('einvoice_response')->nullable()->after('einvoice_error_message'); // Store full API response for reference
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'einvoice_signed_data',
                'einvoice_irn',
                'einvoice_ack_no',
                'einvoice_ack_date',
                'einvoice_status',
                'einvoice_error_message',
                'einvoice_response',
            ]);
        });
    }
};
