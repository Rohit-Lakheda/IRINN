<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanChangeRequest extends Model
{
    protected $fillable = [
        'application_id',
        'user_id',
        'current_port_capacity',
        'new_port_capacity',
        'current_billing_plan',
        'new_billing_plan',
        'current_amount',
        'new_amount',
        'adjustment_amount',
        'change_type',
        'status',
        'reason',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'effective_from',
        'adjustment_applied',
        'adjustment_invoice_id',
    ];

    protected $casts = [
        'current_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'effective_from' => 'datetime',
        'adjustment_applied' => 'boolean',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the application this request belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get the admin who reviewed the request.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * Get the history for this plan change request.
     */
    public function history(): HasMany
    {
        return $this->hasMany(PlanChangeHistory::class, 'plan_change_request_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the invoice where adjustment was applied.
     */
    public function adjustmentInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'adjustment_invoice_id');
    }

    /**
     * Check if this is a capacity change (upgrade/downgrade) vs billing cycle change.
     */
    public function isCapacityChange(): bool
    {
        return $this->current_port_capacity !== $this->new_port_capacity;
    }

    /**
     * Check if this is a billing cycle change only.
     */
    public function isBillingCycleChangeOnly(): bool
    {
        return $this->current_port_capacity === $this->new_port_capacity 
            && $this->current_billing_plan !== $this->new_billing_plan;
    }
}
