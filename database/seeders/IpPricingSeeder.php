<?php

namespace Database\Seeders;

use App\Models\IpPricing;
use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class IpPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentType = PaymentType::where('slug', 'ip-pricing')->first();

        // IPv4 Pricing - /24
        $ipv4_24 = IpPricing::updateOrCreate(
            [
                'ip_type' => 'ipv4',
                'size' => '/24',
                'effective_from' => now()->toDateString(),
            ],
            [
                'addresses' => 256,
                'base_price' => 27500,
                'multiplier' => 1.35,
                'log_base' => 8,
                'fixed_price' => null,
                'amount' => 27500, // Using base_price as amount
                'gst_percentage' => 18, // Default 18% GST
                'igst' => 4950, // 18% of 27500
                'cgst' => null,
                'sgst' => null,
                'price' => 32450, // Amount + IGST
                'effective_from' => now()->toDateString(),
                'effective_until' => null,
                'payment_type_id' => $paymentType->id ?? null,
                'is_active' => true,
            ]
        );

        // IPv4 Pricing - /23
        $ipv4_23 = IpPricing::updateOrCreate(
            [
                'ip_type' => 'ipv4',
                'size' => '/23',
                'effective_from' => now()->toDateString(),
            ],
            [
                'addresses' => 512,
                'base_price' => 27500,
                'multiplier' => 1.35,
                'log_base' => 8,
                'fixed_price' => null,
                'amount' => 27500, // Using base_price as amount
                'gst_percentage' => 18, // Default 18% GST
                'igst' => 4950, // 18% of 27500
                'cgst' => null,
                'sgst' => null,
                'price' => 32450, // Amount + IGST
                'effective_from' => now()->toDateString(),
                'effective_until' => null,
                'payment_type_id' => $paymentType->id ?? null,
                'is_active' => true,
            ]
        );

        // IPv6 Pricing - /48
        $ipv6_48 = IpPricing::updateOrCreate(
            [
                'ip_type' => 'ipv6',
                'size' => '/48',
                'effective_from' => now()->toDateString(),
            ],
            [
                'addresses' => 256,
                'base_price' => 24199,
                'multiplier' => 1.35,
                'log_base' => 22,
                'fixed_price' => 24199, // Fixed price for /48
                'amount' => 24199, // Using fixed_price as amount
                'gst_percentage' => 18, // Default 18% GST
                'igst' => 4355.82, // 18% of 24199
                'cgst' => null,
                'sgst' => null,
                'price' => 28554.82, // Amount + IGST
                'effective_from' => now()->toDateString(),
                'effective_until' => null,
                'payment_type_id' => $paymentType->id ?? null,
                'is_active' => true,
            ]
        );

        // IPv6 Pricing - /32
        $ipv6_32 = IpPricing::updateOrCreate(
            [
                'ip_type' => 'ipv6',
                'size' => '/32',
                'effective_from' => now()->toDateString(),
            ],
            [
                'addresses' => 16777216,
                'base_price' => 24199,
                'multiplier' => 1.35,
                'log_base' => 22,
                'fixed_price' => null,
                'amount' => 24199, // Using base_price as amount
                'gst_percentage' => 18, // Default 18% GST
                'igst' => 4355.82, // 18% of 24199
                'cgst' => null,
                'sgst' => null,
                'price' => 28554.82, // Amount + IGST
                'effective_from' => now()->toDateString(),
                'effective_until' => null,
                'payment_type_id' => $paymentType->id ?? null,
                'is_active' => true,
            ]
        );
    }
}
