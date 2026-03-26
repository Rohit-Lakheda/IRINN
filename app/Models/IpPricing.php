<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpPricing extends Model
{
    protected $fillable = [
        'ip_type',
        'size',
        'addresses',
        'base_price',
        'multiplier',
        'log_base',
        'fixed_price',
        'amount',
        'gst_percentage',
        'igst',
        'cgst',
        'sgst',
        'price',
        'effective_from',
        'effective_until',
        'payment_type_id',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'fixed_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'igst' => 'decimal:2',
        'cgst' => 'decimal:2',
        'sgst' => 'decimal:2',
        'price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
        'addresses' => 'integer',
        'log_base' => 'integer',
    ];

    /**
     * Calculate the fee based on pricing configuration.
     */
    public function calculateFee(): float
    {
        // If fixed price is set, use it
        if ($this->fixed_price !== null) {
            return (float) $this->fixed_price;
        }

        // Otherwise, use the formula: base_price * (multiplier ^ (log2(addresses) - log_base))
        $log2Addresses = log($this->addresses, 2);
        $fee = $this->base_price * pow($this->multiplier, $log2Addresses - $this->log_base);

        return round($fee, 2);
    }

    /**
     * Get pricing by IP type and size.
     */
    public static function getPricing(string $ipType, string $size): ?self
    {
        return self::where('ip_type', $ipType)
            ->where('size', $size)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active pricing configurations.
     */
    public static function getAllActive(): array
    {
        $pricings = self::where('is_active', true)->get();
        $result = [];

        foreach ($pricings as $pricing) {
            $result[$pricing->ip_type][$pricing->size] = [
                'addresses' => $pricing->addresses,
                'price' => $pricing->calculateFee(),
                'base_price' => $pricing->base_price,
                'multiplier' => $pricing->multiplier,
                'log_base' => $pricing->log_base,
                'fixed_price' => $pricing->fixed_price,
            ];
        }

        return $result;
    }

    /**
     * Get all currently effective pricing configurations (based on effective dates).
     */
    public static function getCurrentlyEffective(): array
    {
        $now = now()->toDateString();

        $pricings = self::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $now);
            })
            ->orderBy('ip_type')
            ->orderBy('addresses', 'asc')
            ->get();

        $result = [];

        foreach ($pricings as $pricing) {
            $result[$pricing->ip_type][$pricing->size] = [
                'id' => $pricing->id,
                'size' => $pricing->size,
                'addresses' => $pricing->addresses,
                'amount' => (float) ($pricing->amount ?? 0),
                'price' => $pricing->getFinalPrice(),
                'gst_percentage' => (float) ($pricing->gst_percentage ?? 0),
                'igst' => (float) ($pricing->igst ?? 0),
                'cgst' => (float) ($pricing->cgst ?? 0),
                'sgst' => (float) ($pricing->sgst ?? 0),
                'effective_from' => $pricing->effective_from?->format('Y-m-d'),
                'effective_until' => $pricing->effective_until?->format('Y-m-d'),
            ];
        }

        return $result;
    }

    /**
     * Get the payment type.
     */
    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    /**
     * Get pricing history.
     */
    public function history(): HasMany
    {
        return $this->hasMany(PricingHistory::class, 'pricing_id');
    }

    /**
     * Check if pricing is currently active based on effective dates.
     */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now()->toDateString();

        if ($this->effective_from && $this->effective_from > $now) {
            return false; // Not yet effective
        }

        if ($this->effective_until && $this->effective_until < $now) {
            return false; // Expired
        }

        return true;
    }

    /**
     * Get the final price (prefer new price field, fallback to calculated or fixed).
     */
    public function getFinalPrice(): float
    {
        if ($this->price !== null) {
            return (float) $this->price;
        }

        if ($this->fixed_price !== null) {
            return (float) $this->fixed_price;
        }

        // Fallback to calculated price for backward compatibility
        return $this->calculateFee();
    }
}
