<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentVerificationLog extends Model
{
    protected $fillable = [
        'application_id',
        'verified_by',
        'verification_type',
        'billing_period',
        'payment_id',
        'amount',
        'amount_captured',
        'currency',
        'payment_method',
        'notes',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_captured' => 'decimal:2',
        'verified_at' => 'datetime:Asia/Kolkata',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the admin who verified the payment.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }
}
