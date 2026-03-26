<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationReactivationRequest extends Model
{
    protected $fillable = [
        'application_id',
        'user_id',
        'status',
        'user_notes',
        'admin_notes',
        'approved_by',
        'approved_at',
        'invoice_id',
        'paid_at',
        'reactivation_date',
    ];

    protected $casts = [
        'approved_at' => 'datetime:Asia/Kolkata',
        'paid_at' => 'datetime:Asia/Kolkata',
        'reactivation_date' => 'date',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }
}
