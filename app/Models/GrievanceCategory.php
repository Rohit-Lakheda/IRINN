<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GrievanceCategory extends Model
{
    protected $fillable = [
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
        static::creating(function (self $category): void {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function (self $category): void {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get subcategories for this category.
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(GrievanceSubcategory::class, 'category_id')->orderBy('order');
    }

    /**
     * Get active subcategories for this category.
     */
    public function activeSubcategories(): HasMany
    {
        return $this->hasMany(GrievanceSubcategory::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Get assignments for this category.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(GrievanceAssignment::class, 'category_id')->orderBy('priority');
    }

    /**
     * Get active assignments for this category.
     */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(GrievanceAssignment::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('priority');
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
