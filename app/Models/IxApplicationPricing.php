<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IxApplicationPricing extends Model
{
    protected $fillable = [
        'application_fee',
        'gst_percentage',
        'total_amount',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'application_fee' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Get the currently active pricing.
     */
    public static function getActive(): ?self
    {
        $now = now()->toDateString();

        return self::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $now);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * Calculate total amount from application fee and GST percentage.
     */
    public function calculateTotal(): float
    {
        $gstAmount = ($this->application_fee * $this->gst_percentage) / 100;

        return round($this->application_fee + $gstAmount, 2);
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
            return false;
        }

        if ($this->effective_until && $this->effective_until < $now) {
            return false;
        }

        return true;
    }
}
