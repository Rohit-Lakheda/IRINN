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
        Schema::table('ticket_escalation_settings', function (Blueprint $table) {
            $table->string('level_1_role_slug')->default('ix_head')->after('ix_head_after_hours');
            $table->string('level_2_role_slug')->default('ceo')->after('ceo_after_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_escalation_settings', function (Blueprint $table) {
            $table->dropColumn(['level_1_role_slug', 'level_2_role_slug']);
        });
    }
};
