<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Legacy roles (kept for backward compatibility, but inactive)
        $legacyRoles = [
            [
                'name' => 'Processor',
                'slug' => 'processor',
                'description' => 'Process user applications and requests (Legacy)',
                'is_active' => false,
            ],
            [
                'name' => 'Finance',
                'slug' => 'finance',
                'description' => 'Handle financial transactions and approvals (Legacy)',
                'is_active' => false,
            ],
            [
                'name' => 'Technical',
                'slug' => 'technical',
                'description' => 'Handle technical issues and support (Legacy)',
                'is_active' => false,
            ],
        ];

        // New IX workflow roles
        $ixRoles = [
            [
                'name' => 'IX Application Processor',
                'slug' => 'ix_processor',
                'description' => 'Process IX applications, forward to legal or allow resubmission',
                'is_active' => true,
            ],
            [
                'name' => 'IX Legal',
                'slug' => 'ix_legal',
                'description' => 'Verify board resolution and agreement, forward to IX head or send back to processor',
                'is_active' => true,
            ],
            [
                'name' => 'IX Head',
                'slug' => 'ix_head',
                'description' => 'Review and forward to CEO or send back to processor',
                'is_active' => true,
            ],
            [
                'name' => 'CEO',
                'slug' => 'ceo',
                'description' => 'Final approver - approve (forward to Nodal officer) or reject',
                'is_active' => true,
            ],
            [
                'name' => 'Nodal Officer',
                'slug' => 'nodal_officer',
                'description' => 'Assign port capacity, forward to tech team, hold, or send back',
                'is_active' => true,
            ],
            [
                'name' => 'IX Tech Team',
                'slug' => 'ix_tech_team',
                'description' => 'Assign IP and make application live, generate customer ID and membership ID',
                'is_active' => true,
            ],
            [
                'name' => 'IX Account',
                'slug' => 'ix_account',
                'description' => 'Generate invoice and verify payment',
                'is_active' => true,
            ],
        ];

        $allRoles = array_merge($legacyRoles, $ixRoles);

        foreach ($allRoles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
