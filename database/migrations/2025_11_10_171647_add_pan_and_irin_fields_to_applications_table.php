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
            // Add PAN field to link with registrations table
            $table->string('pan_card_no', 10)->nullable()->after('user_id');
            $table->string('application_type')->default('IRINN')->after('application_id'); // IRINN, IX, etc.
            
            // Add index on PAN for faster lookups
            $table->index('pan_card_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['pan_card_no']);
            $table->dropColumn(['pan_card_no', 'application_type']);
        });
    }
};
