<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanChangeHistory extends Model
{
    protected $table = 'plan_change_history';

    protected $fillable = [
        'plan_change_request_id',
        'application_id',
        'old_data',
        'new_data',
        'action',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the plan change request this history belongs to.
     */
    public function planChangeRequest(): BelongsTo
    {
        return $this->belongsTo(PlanChangeRequest::class, 'plan_change_request_id');
    }

    /**
     * Get the application this history belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
