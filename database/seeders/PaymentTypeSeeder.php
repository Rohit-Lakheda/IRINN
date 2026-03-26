<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentTypes = [
            [
                'name' => 'IP Pricing',
                'slug' => 'ip-pricing',
                'description' => 'IP Address pricing management',
                'is_active' => true,
                'sort_order' => 1,
            ],
            // Add more payment types here in the future
        ];

        foreach ($paymentTypes as $type) {
            PaymentType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
