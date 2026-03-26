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
        $now = now();

        // Remove any existing records for these workflow roles to avoid duplicates
        DB::table('roles')
            ->whereIn('slug', ['ix_processor', 'ix_head', 'ix_account'])
            ->delete();

        // Insert three workflow roles mapped to the new phases
        DB::table('roles')->insert([
            [
                'name' => 'Helpdesk',
                'slug' => 'ix_processor',
                'description' => 'Helpdesk: first-level IX application review and forwarding to Hostmaster.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Hostmaster',
                'slug' => 'ix_head',
                'description' => 'Hostmaster: technical and policy review, forwards applications to Billing.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Billing',
                'slug' => 'ix_account',
                'description' => 'Billing: final stage to generate invoices and verify payments.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->whereIn('slug', ['ix_processor', 'ix_head', 'ix_account'])
            ->delete();
    }
};

