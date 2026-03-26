<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterOtp extends Model
{
    protected $fillable = [
        'otp',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Check if the provided OTP is a valid master OTP
     */
    public static function isValidMasterOtp(string $otp): bool
    {
        return self::where('otp', $otp)
            ->where('is_active', true)
            ->exists();
    }
}
