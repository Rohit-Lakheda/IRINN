<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstVerification extends Model
{
    protected $fillable = [
        'user_id',
        'gstin',
        'request_id',
        'status',
        'is_verified',
        'verification_data',
        'legal_name',
        'trade_name',
        'pan',
        'state',
        'registration_date',
        'gst_type',
        'company_status',
        'primary_address',
        'constitution_of_business',
        'error_message',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verification_data' => 'array',
        'registration_date' => 'date',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the user that owns the verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }
}
