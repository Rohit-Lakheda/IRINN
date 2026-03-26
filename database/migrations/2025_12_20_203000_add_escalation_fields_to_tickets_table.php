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
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('escalation_level', ['none', 'ix_head', 'ceo'])->default('none')->after('priority');
            $table->foreignId('escalated_to')->nullable()->after('escalation_level')->constrained('admins')->onDelete('set null');
            $table->timestamp('escalated_at')->nullable()->after('escalated_to');
            $table->text('escalation_notes')->nullable()->after('escalated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['escalated_to']);
            $table->dropColumn(['escalation_level', 'escalated_to', 'escalated_at', 'escalation_notes']);
        });
    }
};
