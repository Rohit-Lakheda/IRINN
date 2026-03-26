<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all pricings for this payment type.
     */
    public function pricings(): HasMany
    {
        return $this->hasMany(IpPricing::class);
    }
}
