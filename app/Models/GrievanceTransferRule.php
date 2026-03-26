<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrievanceTransferRule extends Model
{
    protected $fillable = [
        'from_role',
        'to_role',
        'category_id',
        'subcategory_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the category for this transfer rule.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(GrievanceCategory::class);
    }

    /**
     * Get the subcategory for this transfer rule.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(GrievanceSubcategory::class);
    }

    /**
     * Get the from role.
     */
    public function fromRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'from_role', 'slug');
    }

    /**
     * Get the to role.
     */
    public function toRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'to_role', 'slug');
    }

    /**
     * Scope to get only active transfer rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
