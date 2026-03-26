<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingHistory extends Model
{
    protected $table = 'pricing_history';

    protected $fillable = [
        'pricing_id',
        'payment_type_id',
        'old_data',
        'new_data',
        'effective_from',
        'effective_until',
        'updated_by',
        'change_type',
        'notes',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the pricing this history belongs to.
     */
    public function pricing(): BelongsTo
    {
        return $this->belongsTo(IpPricing::class);
    }

    /**
     * Get the payment type.
     */
    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }
}
