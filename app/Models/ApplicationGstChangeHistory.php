<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationGstChangeHistory extends Model
{
    protected $table = 'application_gst_change_history';

    protected $fillable = [
        'application_id',
        'user_id',
        'old_gstin',
        'new_gstin',
        'old_kyc_details',
        'new_kyc_details',
        'changed_by_type',
        'changed_by_id',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'old_kyc_details' => 'array',
        'new_kyc_details' => 'array',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the application this history belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user this history belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Accessor to resolve the admin/user who made the change.
     */
    public function getChangedByAttribute(): ?Model
    {
        if (! $this->changed_by_id || ! $this->changed_by_type) {
            return null;
        }

        if ($this->changed_by_type === 'admin') {
            return Admin::find($this->changed_by_id);
        }

        if ($this->changed_by_type === 'superadmin') {
            return SuperAdmin::find($this->changed_by_id);
        }

        if ($this->changed_by_type === 'user') {
            return Registration::find($this->changed_by_id);
        }

        return null;
    }

    /**
     * Log a GST change.
     */
    public static function log(
        int $applicationId,
        int $userId,
        ?string $oldGstin,
        string $newGstin,
        ?array $oldKycDetails = null,
        ?array $newKycDetails = null,
        string $changedByType = 'user',
        ?int $changedById = null,
        ?string $notes = null
    ): self {
        return self::create([
            'application_id' => $applicationId,
            'user_id' => $userId,
            'old_gstin' => $oldGstin,
            'new_gstin' => $newGstin,
            'old_kyc_details' => $oldKycDetails,
            'new_kyc_details' => $newKycDetails,
            'changed_by_type' => $changedByType,
            'changed_by_id' => $changedById,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $notes,
        ]);
    }
}
