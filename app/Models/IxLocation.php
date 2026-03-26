<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class IxLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'state',
        'city',
        'node_type',
        'switch_details',
        'ports',
        'nodal_officer',
        'zone',
        'p2p_capacity',
        'p2p_provider',
        'connected_main_node',
        'p2p_arc',
        'colocation_provider',
        'colocation_arc',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'ports' => 'integer',
        'p2p_arc' => 'decimal:2',
        'colocation_arc' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $location): void {
            if (empty($location->slug)) {
                $location->slug = Str::slug($location->name);
            }
        });

        static::updating(function (self $location): void {
            if ($location->isDirty('name')) {
                $location->slug = Str::slug($location->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForState($query, ?string $state)
    {
        if (! $state) {
            return $query;
        }

        return $query->where('state', $state);
    }

    /**
     * Get the history for this location.
     */
    public function history(): HasMany
    {
        return $this->hasMany(IxLocationHistory::class, 'ix_location_id')->orderBy('created_at', 'desc');
    }
}
