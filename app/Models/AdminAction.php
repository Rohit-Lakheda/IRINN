<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminAction extends Model
{
    protected $fillable = [
        'admin_id',
        'superadmin_id',
        'action_type',
        'actionable_type',
        'actionable_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the admin that performed the action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the SuperAdmin that performed the action.
     */
    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'superadmin_id');
    }

    /**
     * Get the parent actionable model (polymorphic).
     */
    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log an admin action.
     */
    public static function log(int $adminId, string $actionType, Model $actionable, ?string $description = null, ?array $metadata = null): self
    {
        return self::create([
            'admin_id' => $adminId,
            'superadmin_id' => null,
            'action_type' => $actionType,
            'actionable_type' => get_class($actionable),
            'actionable_id' => $actionable->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a SuperAdmin action.
     */
    public static function logSuperAdmin(int $superAdminId, string $actionType, Model $actionable, ?string $description = null, ?array $metadata = null): self
    {
        return self::create([
            'admin_id' => null,
            'superadmin_id' => $superAdminId,
            'action_type' => $actionType,
            'actionable_type' => get_class($actionable),
            'actionable_id' => $actionable->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log an admin login/logout action (without actionable model).
     */
    public static function logAdminActivity(int $adminId, string $actionType, ?string $description = null, ?array $metadata = null): self
    {
        return self::create([
            'admin_id' => $adminId,
            'superadmin_id' => null,
            'action_type' => $actionType,
            'actionable_type' => null,
            'actionable_id' => null,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
