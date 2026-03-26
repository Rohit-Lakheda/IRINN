<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GrievanceSubcategory extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $subcategory): void {
            if (empty($subcategory->slug)) {
                $subcategory->slug = Str::slug($subcategory->name);
            }
        });

        static::updating(function (self $subcategory): void {
            if ($subcategory->isDirty('name') && empty($subcategory->slug)) {
                $subcategory->slug = Str::slug($subcategory->name);
            }
        });
    }

    /**
     * Get the category that owns this subcategory.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(GrievanceCategory::class, 'category_id');
    }

    /**
     * Get assignments for this subcategory.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(GrievanceAssignment::class, 'subcategory_id')->orderBy('priority');
    }

    /**
     * Get active assignments for this subcategory.
     */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(GrievanceAssignment::class, 'subcategory_id')
            ->where('is_active', true)
            ->orderBy('priority');
    }

    /**
     * Scope to get only active subcategories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
