<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Registration extends Model
{
    protected $fillable = [
        'registrationid',
        'pancardno',
        'registration_type',
        'pan_verified',
        'fullname',
        'email',
        'email_otp',
        'email_verified',
        'mobile',
        'mobile_otp',
        'mobile_verified',
        'password',
        'dateofbirth',
        'registrationdate',
        'registrationtime',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'dateofbirth' => 'date',
        'registrationdate' => 'date',
        'pan_verified' => 'boolean',
        'email_verified' => 'boolean',
        'mobile_verified' => 'boolean',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Generate a unique registration ID
     */
    public static function generateRegistrationId(): string
    {
        do {
            $registrationId = 'REG'.strtoupper(Str::random(8));
        } while (self::where('registrationid', $registrationId)->exists());

        return $registrationId;
    }

    /**
     * Get the messages for the user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    /**
     * Get unread messages count.
     */
    public function unreadMessagesCount(): int
    {
        return $this->messages()->where('is_read', false)->count();
    }

    /**
     * Get the profile update requests for the user.
     */
    public function profileUpdateRequests()
    {
        return $this->hasMany(ProfileUpdateRequest::class, 'user_id');
    }

    /**
     * Get pending profile update request.
     */
    public function pendingProfileUpdateRequest()
    {
        return $this->profileUpdateRequests()->where('status', 'pending')->first();
    }

    /**
     * Get the applications for the user.
     */
    public function applications()
    {
        return $this->hasMany(Application::class, 'user_id');
    }

    /**
     * Get the PAN verification for the user.
     */
    public function panVerification()
    {
        return $this->hasOne(PanVerification::class, 'user_id');
    }

    /**
     * Get the wallet for the user.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }
}
