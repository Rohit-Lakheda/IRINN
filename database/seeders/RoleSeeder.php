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

        // IRINN workflow roles
        $irinnRoles = [
            [
                'name' => 'IRINN Helpdesk',
                'slug' => 'helpdesk',
                'description' => 'First-line IRINN application review and forwarding',
                'is_active' => true,
            ],
            [
                'name' => 'IRINN Hostmaster',
                'slug' => 'hostmaster',
                'description' => 'IRINN technical / hostmaster stage',
                'is_active' => true,
            ],
            [
                'name' => 'IRINN Billing',
                'slug' => 'billing',
                'description' => 'IRINN billing, discounts, and annual invoices',
                'is_active' => true,
            ],
        ];

        $allRoles = array_merge($legacyRoles, $irinnRoles);

        foreach ($allRoles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
