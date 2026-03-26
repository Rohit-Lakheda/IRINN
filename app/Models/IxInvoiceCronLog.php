<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IxInvoiceCronLog extends Model
{
    protected $fillable = [
        'run_id',
        'command_name',
        'is_dry_run',
        'application_id',
        'invoice_id',
        'payment_transaction_id',
        'application_code',
        'billing_period',
        'billing_start_date',
        'billing_end_date',
        'invoice_number',
        'status',
        'skip_reason',
        'error_message',
        'pdf_generated',
        'pdf_path',
        'mail_sent',
        'mail_sent_at',
        'gstin_inactive',
        'einvoice_attempted',
        'einvoice_irn',
        'einvoice_status',
        'einvoice_error_code',
        'einvoice_error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'is_dry_run' => 'boolean',
        'pdf_generated' => 'boolean',
        'mail_sent' => 'boolean',
        'gstin_inactive' => 'boolean',
        'einvoice_attempted' => 'boolean',
        'billing_start_date' => 'date',
        'billing_end_date' => 'date',
        'mail_sent_at' => 'datetime:Asia/Kolkata',
        'started_at' => 'datetime:Asia/Kolkata',
        'finished_at' => 'datetime:Asia/Kolkata',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Application::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PaymentTransaction::class);
    }
}
