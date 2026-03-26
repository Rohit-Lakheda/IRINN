<?php

namespace Database\Seeders;

use App\Models\IxPortPricing;
use Illuminate\Database\Seeder;

class IxPortPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricingMatrix = [
            'metro' => [
                ['capacity' => '100M', 'arc' => 35000, 'mrc' => 3092, 'quarterly' => 8925],
                ['capacity' => '500M', 'arc' => 70000, 'mrc' => 6183, 'quarterly' => 17850],
                ['capacity' => '1Gig', 'arc' => 110000, 'mrc' => 9717, 'quarterly' => 28050],
                ['capacity' => '2Gig', 'arc' => 170000, 'mrc' => 15017, 'quarterly' => 43350],
                ['capacity' => '3Gig', 'arc' => 225000, 'mrc' => 19875, 'quarterly' => 57375],
                ['capacity' => '5Gig', 'arc' => 380000, 'mrc' => 33567, 'quarterly' => 96900],
                ['capacity' => '6Gig', 'arc' => 410000, 'mrc' => 34900, 'quarterly' => 104550],
                ['capacity' => '7Gig', 'arc' => 470000, 'mrc' => 41517, 'quarterly' => 119850],
                ['capacity' => '8Gig', 'arc' => 515000, 'mrc' => 45492, 'quarterly' => 131325],
                ['capacity' => '10Gig', 'arc' => 580000, 'mrc' => 49900, 'quarterly' => 147900],
                ['capacity' => '15Gig', 'arc' => 800000, 'mrc' => 70667, 'quarterly' => 204000],
                ['capacity' => '20Gig', 'arc' => 1000000, 'mrc' => 88333, 'quarterly' => 255000],
                ['capacity' => '25Gig', 'arc' => 1175000, 'mrc' => 103792, 'quarterly' => 299625],
                ['capacity' => '30Gig', 'arc' => 1350000, 'mrc' => 119250, 'quarterly' => 344250],
                ['capacity' => '40Gig', 'arc' => 1600000, 'mrc' => 141333, 'quarterly' => 408000],
                ['capacity' => '50Gig', 'arc' => 1750000, 'mrc' => 154583, 'quarterly' => 446250],
                ['capacity' => '60Gig', 'arc' => 2100000, 'mrc' => 185500, 'quarterly' => 535500],
                ['capacity' => '70Gig', 'arc' => 2500000, 'mrc' => 220833, 'quarterly' => 637500],
                ['capacity' => '100Gig', 'arc' => 2800000, 'mrc' => 247333, 'quarterly' => 714000],
            ],
            'edge' => [
                ['capacity' => '100M', 'arc' => 87500, 'mrc' => 7730, 'quarterly' => 22313],
                ['capacity' => '500M', 'arc' => 175000, 'mrc' => 15458, 'quarterly' => 44625],
                ['capacity' => '1Gig', 'arc' => 275000, 'mrc' => 24293, 'quarterly' => 70125],
                ['capacity' => '2Gig', 'arc' => 425000, 'mrc' => 37543, 'quarterly' => 108375],
                ['capacity' => '3Gig', 'arc' => 562500, 'mrc' => 49688, 'quarterly' => 143438],
                ['capacity' => '5Gig', 'arc' => 950000, 'mrc' => 83918, 'quarterly' => 242250],
                ['capacity' => '6Gig', 'arc' => 1025000, 'mrc' => 87250, 'quarterly' => 261375],
                ['capacity' => '7Gig', 'arc' => 1175000, 'mrc' => 103793, 'quarterly' => 299625],
                ['capacity' => '8Gig', 'arc' => 1287500, 'mrc' => 113730, 'quarterly' => 328313],
                ['capacity' => '10Gig', 'arc' => 1450000, 'mrc' => 124750, 'quarterly' => 369750],
            ],
        ];

        foreach ($pricingMatrix as $nodeType => $records) {
            foreach ($records as $index => $record) {
                IxPortPricing::updateOrCreate(
                    ['node_type' => $nodeType, 'port_capacity' => $record['capacity']],
                    [
                        'price_arc' => $record['arc'],
                        'price_mrc' => $record['mrc'],
                        'price_quarterly' => $record['quarterly'],
                        'currency' => 'INR',
                        'display_order' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
