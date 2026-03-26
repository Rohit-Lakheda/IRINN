<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileUpdateRequest extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'requested_changes',
        'admin_notes',
        'approved_at',
        'rejected_at',
        'approved_by',
        'submitted_data',
        'submitted_at',
        'update_approved',
        'update_approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime:Asia/Kolkata',
        'rejected_at' => 'datetime:Asia/Kolkata',
        'submitted_at' => 'datetime:Asia/Kolkata',
        'update_approved_at' => 'datetime:Asia/Kolkata',
        'requested_changes' => 'array',
        'submitted_data' => 'array',
        'update_approved' => 'boolean',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the user that owns the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get the admin who approved/rejected the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin::class, 'approved_by');
    }
}
