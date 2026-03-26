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
            // Add new workflow stage tracking fields
            $table->foreignId('current_ix_processor_id')->nullable()->after('current_technical_id')->constrained('admins')->onDelete('set null');
            $table->foreignId('current_ix_legal_id')->nullable()->after('current_ix_processor_id')->constrained('admins')->onDelete('set null');
            $table->foreignId('current_ix_head_id')->nullable()->after('current_ix_legal_id')->constrained('admins')->onDelete('set null');
            $table->foreignId('current_ceo_id')->nullable()->after('current_ix_head_id')->constrained('admins')->onDelete('set null');
            $table->foreignId('current_nodal_officer_id')->nullable()->after('current_ceo_id')->constrained('admins')->onDelete('set null');
            $table->foreignId('current_ix_tech_team_id')->nullable()->after('current_nodal_officer_id')->constrained('admins')->onDelete('set null');
            $table->foreignId('current_ix_account_id')->nullable()->after('current_ix_tech_team_id')->constrained('admins')->onDelete('set null');
            
            // Add workflow-specific fields
            $table->text('resubmission_query')->nullable()->after('rejection_reason');
            $table->string('assigned_port_capacity')->nullable()->after('resubmission_query');
            $table->string('assigned_port_number')->nullable()->after('assigned_port_capacity');
            $table->string('customer_id')->nullable()->after('assigned_port_number');
            $table->string('membership_id')->nullable()->after('customer_id');
            $table->string('assigned_ip')->nullable()->after('membership_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['current_ix_processor_id']);
            $table->dropForeign(['current_ix_legal_id']);
            $table->dropForeign(['current_ix_head_id']);
            $table->dropForeign(['current_ceo_id']);
            $table->dropForeign(['current_nodal_officer_id']);
            $table->dropForeign(['current_ix_tech_team_id']);
            $table->dropForeign(['current_ix_account_id']);
            
            $table->dropColumn([
                'current_ix_processor_id',
                'current_ix_legal_id',
                'current_ix_head_id',
                'current_ceo_id',
                'current_nodal_officer_id',
                'current_ix_tech_team_id',
                'current_ix_account_id',
                'resubmission_query',
                'assigned_port_capacity',
                'assigned_port_number',
                'customer_id',
                'membership_id',
                'assigned_ip',
            ]);
        });
    }
};
