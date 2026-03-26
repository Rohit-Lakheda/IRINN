<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanVerification extends Model
{
    protected $fillable = [
        'user_id',
        'pan_number',
        'request_id',
        'status',
        'is_verified',
        'verification_data',
        'full_name',
        'date_of_birth',
        'pan_status',
        'name_match',
        'dob_match',
        'error_message',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verification_data' => 'array',
        'date_of_birth' => 'date',
        'name_match' => 'boolean',
        'dob_match' => 'boolean',
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
