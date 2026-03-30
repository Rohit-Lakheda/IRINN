<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'application_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'billing_period',
        'billing_start_date',
        'billing_end_date',
        'invoice_purpose',
        'line_items',
        'amount',
        'gst_amount',
        'tds_percentage',
        'tds_amount',
        'paid_tds_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'carry_forward_amount',
        'has_carry_forward',
        'forwarded_amount',
        'forwarded_to_invoice_date',
        'currency',
        'status',
        'payu_payment_link',
        'manual_payment_id',
        'manual_payment_notes',
        'payment_receipt_path',
        'billing_payment_proof_path',
        'tds_certificate_path',
        'tds_updated_at',
        'pdf_path',
        'generated_by',
        'sent_at',
        'paid_at',
        'paid_by',
        'einvoice_signed_data',
        'einvoice_irn',
        'einvoice_ack_no',
        'einvoice_ack_date',
        'einvoice_status',
        'einvoice_error_code',
        'einvoice_error_message',
        'einvoice_response',
        'seller_gstin',
        'credit_note_pdf_path',
        'cancel_api_response',
        'credit_note_api_response',
        'credit_note_irn',
        'credit_note_ack_no',
        'credit_note_ack_date',
        'credit_note_doc_date',
        'credit_note_status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'billing_start_date' => 'date',
        'billing_end_date' => 'date',
        'line_items' => 'array',
        'amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'tds_percentage' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'paid_tds_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'carry_forward_amount' => 'decimal:2',
        'has_carry_forward' => 'boolean',
        'forwarded_amount' => 'decimal:2',
        'forwarded_to_invoice_date' => 'date',
        'tds_updated_at' => 'datetime:Asia/Kolkata',
        'sent_at' => 'datetime:Asia/Kolkata',
        'paid_at' => 'datetime:Asia/Kolkata',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
        'einvoice_signed_data' => 'array',
        'einvoice_response' => 'array',
        'cancel_api_response' => 'array',
        'credit_note_api_response' => 'array',
        'credit_note_ack_date' => 'datetime:Asia/Kolkata',
        'credit_note_doc_date' => 'date',
    ];

    /**
     * Get the application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the admin who generated the invoice.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'generated_by');
    }

    /**
     * Admin who marked invoice as paid manually.
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'paid_by');
    }

    /**
     * Get payment allocations for this invoice.
     */
    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid' || ($this->paid_amount >= $this->total_amount && $this->total_amount > 0);
    }

    /**
     * Check if invoice has partial payment.
     */
    public function hasPartialPayment(): bool
    {
        return $this->payment_status === 'partial' || ($this->paid_amount > 0 && $this->paid_amount < $this->total_amount);
    }

    /**
     * Get remaining balance.
     */
    public function getRemainingBalance(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function hasCreditNote(): bool
    {
        return ! empty($this->credit_note_pdf_path) || $this->status === 'credit_note';
    }

    public function isCreditNote(): bool
    {
        return $this->status === 'credit_note';
    }

    /** Cancel allowed only within 24 hours of generation and when IRN exists. */
    public function canBeCancelled(): bool
    {
        if ($this->isCancelled() || $this->isCreditNote() || $this->hasCreditNote()) {
            return false;
        }
        if (empty($this->einvoice_irn)) {
            return false;
        }
        $cutoff = $this->created_at->copy()->addHours(24);

        return now()->lte($cutoff);
    }

    /** Credit note allowed after 24 hours when IRN exists and not already cancelled/has credit note. */
    public function canGenerateCreditNote(): bool
    {
        if ($this->isCancelled() || $this->isCreditNote() || $this->hasCreditNote()) {
            return false;
        }
        if (empty($this->einvoice_irn)) {
            return false;
        }
        $cutoff = $this->created_at->copy()->addHours(24);

        return now()->gt($cutoff);
    }

    /** True if invoice is cancelled or has credit note (only download allowed). */
    public function isCancelledOrHasCreditNote(): bool
    {
        return $this->isCancelled() || $this->isCreditNote() || $this->hasCreditNote();
    }

    /**
     * Scope: only invoices that count for payment totals (exclude cancelled and credit note status).
     */
    public function scopeActiveForTotals(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'credit_note')
            ->whereNull('credit_note_pdf_path');
    }
}
