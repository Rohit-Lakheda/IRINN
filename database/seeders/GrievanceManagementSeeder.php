<?php

namespace Database\Seeders;

use App\Models\GrievanceAssignment;
use App\Models\GrievanceCategory;
use App\Models\GrievanceSubcategory;
use App\Models\GrievanceTransferRule;
use Illuminate\Database\Seeder;

class GrievanceManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $categories = [
            [
                'name' => 'Network and Connectivity',
                'slug' => 'network_connectivity',
                'description' => 'Issues related to network connectivity, link down, speed issues, etc.',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Billing',
                'slug' => 'billing',
                'description' => 'Billing related issues and queries',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Request',
                'slug' => 'request',
                'description' => 'Service requests like MAC change, upgrade/downgrade, profile change',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'name' => 'Feedback / Suggestion',
                'slug' => 'feedback_suggestion',
                'description' => 'Feedback and suggestions',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Other grievances',
                'is_active' => true,
                'order' => 5,
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = GrievanceCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );

            // Create subcategories based on category
            $subcategories = match ($category->slug) {
                'network_connectivity' => [
                    ['name' => 'Link Down', 'slug' => 'link_down', 'order' => 1],
                    ['name' => 'Speed Issue', 'slug' => 'speed_issue', 'order' => 2],
                    ['name' => 'Packet Drop Issue', 'slug' => 'packet_drop_issue', 'order' => 3],
                    ['name' => 'Specific Website Issue', 'slug' => 'specific_website_issue', 'order' => 4],
                    ['name' => 'Other', 'slug' => 'other', 'order' => 5],
                ],
                'billing' => [
                    ['name' => 'Billing Issue', 'slug' => 'billing_issue', 'order' => 1],
                    ['name' => 'Other', 'slug' => 'other', 'order' => 2],
                ],
                'request' => [
                    ['name' => 'MAC Change', 'slug' => 'mac_change', 'order' => 1],
                    ['name' => 'Upgrade/Downgrade', 'slug' => 'upgrade_downgrade', 'order' => 2],
                    ['name' => 'Profile Change', 'slug' => 'profile_change', 'order' => 3],
                    ['name' => 'Other', 'slug' => 'other', 'order' => 4],
                ],
                'feedback_suggestion' => [
                    ['name' => 'Other', 'slug' => 'other', 'order' => 1],
                ],
                'other' => [
                    ['name' => 'Other', 'slug' => 'other', 'order' => 1],
                ],
                default => [],
            };

            foreach ($subcategories as $subcategoryData) {
                GrievanceSubcategory::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'slug' => $subcategoryData['slug'],
                    ],
                    array_merge($subcategoryData, [
                        'category_id' => $category->id,
                        'is_active' => true,
                    ])
                );
            }

            // Create assignments based on category (IRINN: helpdesk, hostmaster, billing)
            $assignments = match ($category->slug) {
                'network_connectivity' => [
                    ['assigned_role' => 'hostmaster', 'priority' => 1],
                ],
                'billing' => [
                    ['assigned_role' => 'billing', 'priority' => 1],
                ],
                'request' => [
                    // Per subcategory below
                ],
                'feedback_suggestion' => [
                    ['assigned_role' => 'helpdesk', 'priority' => 1],
                ],
                'other' => [
                    ['assigned_role' => 'helpdesk', 'priority' => 1],
                ],
                default => [],
            };

            foreach ($assignments as $assignmentData) {
                GrievanceAssignment::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'subcategory_id' => null,
                        'assigned_role' => $assignmentData['assigned_role'],
                    ],
                    array_merge($assignmentData, [
                        'category_id' => $category->id,
                        'is_active' => true,
                    ])
                );
            }

            // Create subcategory-specific assignments for 'request' category
            if ($category->slug === 'request') {
                $requestSubcategories = $category->subcategories;
                foreach ($requestSubcategories as $subcategory) {
                    $subcategoryAssignments = match ($subcategory->slug) {
                        'mac_change', 'upgrade_downgrade' => [
                            ['assigned_role' => 'hostmaster', 'priority' => 1],
                        ],
                        'profile_change' => [
                            ['assigned_role' => 'billing', 'priority' => 1],
                        ],
                        default => [
                            ['assigned_role' => 'helpdesk', 'priority' => 1],
                        ],
                    };

                    foreach ($subcategoryAssignments as $assignmentData) {
                        GrievanceAssignment::updateOrCreate(
                            [
                                'category_id' => $category->id,
                                'subcategory_id' => $subcategory->id,
                                'assigned_role' => $assignmentData['assigned_role'],
                            ],
                            array_merge($assignmentData, [
                                'category_id' => $category->id,
                                'subcategory_id' => $subcategory->id,
                                'is_active' => true,
                            ])
                        );
                    }
                }
            }
        }

        GrievanceAssignment::whereIn('assigned_role', [
            'nodal_officer', 'ix_account', 'ix_head', 'ix_processor', 'ix_tech_team', 'ceo',
        ])->delete();

        GrievanceTransferRule::query()->where(function ($q): void {
            $legacy = ['nodal_officer', 'ix_account', 'ix_head', 'ix_processor', 'ix_tech_team', 'ceo'];
            $q->whereIn('from_role', $legacy)->orWhereIn('to_role', $legacy);
        })->delete();

        // IRINN: each role can forward to the other two (applies to all categories when category_id is null).
        $transferRules = [
            ['from_role' => 'helpdesk', 'to_role' => 'hostmaster', 'category_id' => null, 'subcategory_id' => null],
            ['from_role' => 'helpdesk', 'to_role' => 'billing', 'category_id' => null, 'subcategory_id' => null],
            ['from_role' => 'hostmaster', 'to_role' => 'helpdesk', 'category_id' => null, 'subcategory_id' => null],
            ['from_role' => 'hostmaster', 'to_role' => 'billing', 'category_id' => null, 'subcategory_id' => null],
            ['from_role' => 'billing', 'to_role' => 'helpdesk', 'category_id' => null, 'subcategory_id' => null],
            ['from_role' => 'billing', 'to_role' => 'hostmaster', 'category_id' => null, 'subcategory_id' => null],
        ];

        foreach ($transferRules as $rule) {
            GrievanceTransferRule::updateOrCreate(
                [
                    'from_role' => $rule['from_role'],
                    'to_role' => $rule['to_role'],
                    'category_id' => $rule['category_id'],
                    'subcategory_id' => $rule['subcategory_id'],
                ],
                array_merge($rule, ['is_active' => true])
            );
        }
    }
}
