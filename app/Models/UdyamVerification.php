<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UdyamVerification extends Model
{
    protected $fillable = [
        'user_id',
        'uam_number',
        'request_id',
        'status',
        'is_verified',
        'verification_data',
        'error_message',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verification_data' => 'array',
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
