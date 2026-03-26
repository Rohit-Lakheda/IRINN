<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IxMembershipFeeSetting extends Model
{
    protected $table = 'ix_membership_fee_settings';

    protected $fillable = [
        'fee_amount',
        'currency',
        'gst_percentage',
        'updated_by',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    public static function current(): self
    {
        return self::query()->latest()->first() ?? self::query()->create([
            'fee_amount' => 1000,
            'currency' => 'INR',
            'gst_percentage' => 18,
            'updated_by' => null,
        ]);
    }
}
