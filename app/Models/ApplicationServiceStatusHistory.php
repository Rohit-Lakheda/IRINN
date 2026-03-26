<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationServiceStatusHistory extends Model
{
    protected $fillable = [
        'application_id',
        'status',
        'effective_from',
        'effective_to',
        'changed_by_type',
        'changed_by_id',
        'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
