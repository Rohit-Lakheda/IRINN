<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NodalOfficerEmail extends Model
{
    protected $fillable = [
        'name',
        'email',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get all active nodal officer emails ordered by order field.
     */
    public static function getActiveEmails(): array
    {
        return static::where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->pluck('email')
            ->toArray();
    }

    /**
     * Get email address for a nodal officer by name.
     */
    public static function getEmailByName(string $name): ?string
    {
        $nodalOfficer = static::where('name', $name)
            ->where('is_active', true)
            ->first();

        return $nodalOfficer ? $nodalOfficer->email : null;
    }
}
