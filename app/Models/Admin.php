<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Admin extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_id',
        'is_super_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_super_admin' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the roles for the admin.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role');
    }

    /**
     * Get all admin actions.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(AdminAction::class);
    }

    /**
     * Get applications that this admin has read.
     */
    public function readApplications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'admin_application_reads', 'admin_id', 'application_id')
            ->withPivot('read_at', 'role')
            ->withTimestamps();
    }

    /**
     * Check if admin has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if admin has any of the given roles.
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Generate a unique admin ID.
     */
    public static function generateAdminId(): string
    {
        do {
            $adminId = 'ADM'.strtoupper(Str::random(8));
        } while (self::where('admin_id', $adminId)->exists());

        return $adminId;
    }
}
