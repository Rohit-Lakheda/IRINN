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
            $table->foreignId('gst_verification_id')->nullable()->after('pan_card_no')->constrained('gst_verifications')->onDelete('set null');
            $table->foreignId('udyam_verification_id')->nullable()->after('gst_verification_id')->constrained('udyam_verifications')->onDelete('set null');
            $table->foreignId('mca_verification_id')->nullable()->after('udyam_verification_id')->constrained('mca_verifications')->onDelete('set null');
            $table->foreignId('roc_iec_verification_id')->nullable()->after('mca_verification_id')->constrained('roc_iec_verifications')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['gst_verification_id']);
            $table->dropForeign(['udyam_verification_id']);
            $table->dropForeign(['mca_verification_id']);
            $table->dropForeign(['roc_iec_verification_id']);
            $table->dropColumn(['gst_verification_id', 'udyam_verification_id', 'mca_verification_id', 'roc_iec_verification_id']);
        });
    }
};
