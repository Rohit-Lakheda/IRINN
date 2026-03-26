<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrievanceAssignment extends Model
{
    protected $fillable = [
        'category_id',
        'subcategory_id',
        'assigned_role',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the category for this assignment.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(GrievanceCategory::class, 'category_id');
    }

    /**
     * Get the subcategory for this assignment.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(GrievanceSubcategory::class, 'subcategory_id');
    }

    /**
     * Get the role for this assignment.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'assigned_role', 'slug');
    }

    /**
     * Scope to get only active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
