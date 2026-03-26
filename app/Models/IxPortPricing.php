<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IxPortPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'node_type',
        'port_capacity',
        'price_arc',
        'price_mrc',
        'price_quarterly',
        'currency',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'price_arc' => 'decimal:2',
        'price_mrc' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForNodeType($query, string $nodeType)
    {
        return $query->where('node_type', $nodeType);
    }

    public function getAmountForPlan(string $plan): ?float
    {
        $amount = match ($plan) {
            'arc', 'annual' => $this->price_arc,
            'mrc', 'monthly' => $this->price_mrc,
            'quarterly' => $this->price_quarterly,
            default => null,
        };
        
        // Return null if pricing is not set (null or 0)
        if ($amount === null || (float)$amount <= 0) {
            return null;
        }
        
        return (float) $amount;
    }

    /**
     * Check if pricing exists for a specific plan.
     */
    public function hasPricingForPlan(string $plan): bool
    {
        return $this->getAmountForPlan($plan) !== null;
    }

    /**
     * Get available billing plans for this port capacity.
     */
    public function getAvailablePlans(): array
    {
        $plans = [];
        
        if ($this->hasPricingForPlan('arc')) {
            $plans[] = 'arc';
        }
        if ($this->hasPricingForPlan('mrc')) {
            $plans[] = 'mrc';
        }
        if ($this->hasPricingForPlan('quarterly')) {
            $plans[] = 'quarterly';
        }
        
        return $plans;
    }

    /**
     * Check if this port capacity has any valid pricing.
     */
    public function hasAnyPricing(): bool
    {
        return !empty($this->getAvailablePlans());
    }
}
