<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IxLocationHistory extends Model
{
    protected $table = 'ix_location_history';

    protected $fillable = [
        'ix_location_id',
        'old_data',
        'new_data',
        'updated_by',
        'change_type',
        'notes',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the IX location this history belongs to.
     */
    public function ixLocation(): BelongsTo
    {
        return $this->belongsTo(IxLocation::class, 'ix_location_id');
    }
}
