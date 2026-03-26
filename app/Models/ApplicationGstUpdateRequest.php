<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationGstUpdateRequest extends Model
{
    protected $fillable = [
        'application_id',
        'user_id',
        'old_gstin',
        'new_gstin',
        'old_company_name',
        'new_company_name',
        'similarity_score',
        'old_kyc_details',
        'new_kyc_details',
        'gst_verification_id',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'old_kyc_details' => 'array',
        'new_kyc_details' => 'array',
        'similarity_score' => 'decimal:2',
        'reviewed_at' => 'datetime:Asia/Kolkata',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the application this request belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get the admin who reviewed the request.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * Get the GST verification.
     */
    public function gstVerification(): BelongsTo
    {
        return $this->belongsTo(GstVerification::class, 'gst_verification_id');
    }
}
