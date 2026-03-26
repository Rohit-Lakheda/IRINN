<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Deactivate old roles (don't delete to preserve existing assignments)
        DB::table('roles')
            ->whereIn('slug', ['processor', 'finance', 'technical'])
            ->update(['is_active' => false]);

        // Insert new IX workflow roles
        $newRoles = [
            [
                'name' => 'IX Application Processor',
                'slug' => 'ix_processor',
                'description' => 'Process IX applications, forward to legal or allow resubmission',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'IX Legal',
                'slug' => 'ix_legal',
                'description' => 'Verify board resolution and agreement, forward to IX head or send back to processor',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'IX Head',
                'slug' => 'ix_head',
                'description' => 'Review and forward to CEO or send back to processor',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'CEO',
                'slug' => 'ceo',
                'description' => 'Final approver - approve (forward to Nodal officer) or reject',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nodal Officer',
                'slug' => 'nodal_officer',
                'description' => 'Assign port capacity, forward to tech team, hold, or send back',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'IX Tech Team',
                'slug' => 'ix_tech_team',
                'description' => 'Assign IP and make application live, generate customer ID and membership ID',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'IX Account',
                'slug' => 'ix_account',
                'description' => 'Generate invoice and verify payment',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($newRoles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                $role
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reactivate old roles
        DB::table('roles')
            ->whereIn('slug', ['processor', 'finance', 'technical'])
            ->update(['is_active' => true]);

        // Deactivate new roles
        DB::table('roles')
            ->whereIn('slug', ['ix_processor', 'ix_legal', 'ix_head', 'ceo', 'nodal_officer', 'ix_tech_team', 'ix_account'])
            ->update(['is_active' => false]);
    }
};
