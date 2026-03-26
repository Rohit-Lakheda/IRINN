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
            $table->string('credit_note_irn')->nullable()->after('credit_note_api_response');
            $table->string('credit_note_ack_no')->nullable()->after('credit_note_irn');
            $table->datetime('credit_note_ack_date')->nullable()->after('credit_note_ack_no');
            $table->date('credit_note_doc_date')->nullable()->after('credit_note_ack_date');
            $table->string('credit_note_status')->nullable()->after('credit_note_doc_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            //
        });
    }
};
