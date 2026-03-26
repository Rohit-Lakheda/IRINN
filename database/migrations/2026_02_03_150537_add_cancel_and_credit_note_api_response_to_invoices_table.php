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
            $table->json('cancel_api_response')->nullable()->after('credit_note_pdf_path');
            $table->json('credit_note_api_response')->nullable()->after('cancel_api_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['cancel_api_response', 'credit_note_api_response']);
        });
    }
};
