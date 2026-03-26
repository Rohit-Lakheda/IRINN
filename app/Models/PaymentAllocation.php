<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'invoice_id',
        'application_id',
        'user_id',
        'allocated_amount',
        'payment_reference',
        'notes',
        'allocated_by',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get the admin who allocated the payment.
     */
    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'allocated_by');
    }
}

