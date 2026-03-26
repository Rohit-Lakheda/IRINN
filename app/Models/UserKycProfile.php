<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKycProfile extends Model
{
    protected $fillable = [
        'user_id',
        'is_msme',
        'organisation_type',
        'organisation_type_other',
        'organisation_license_path',
        'affiliate_type',
        'affiliate_verification_mode',
        'affiliate_document_path',
        'gstin',
        'gst_verification_id',
        'udyam_verification_id',
        'mca_verification_id',
        'udyam_number',
        'cin',
        'gst_verified',
        'udyam_verified',
        'mca_verified',
        'contact_name',
        'contact_dob',
        'contact_pan',
        'contact_email',
        'contact_mobile',
        'contact_name_pan_dob_verified',
        'contact_email_verified',
        'contact_mobile_verified',
        'status',
        'completed_at',
        'kyc_ip_address',
        'kyc_user_agent',
        'billing_address_source',
        'billing_address',
        // IRINN-specific management representative
        'management_name',
        'management_dob',
        'management_pan',
        'management_email',
        'management_mobile',
        'management_din',
        'management_pan_verified',
        'management_email_verified',
        'management_mobile_verified',
        'management_din_verified',
        // IRINN-specific authorised representative
        'authorized_name',
        'authorized_dob',
        'authorized_pan',
        'authorized_email',
        'authorized_mobile',
        'authorized_pan_verified',
        'authorized_email_verified',
        'authorized_mobile_verified',
        // WHOIS / public contact
        'whois_source',
        // Billing person meta
        'billing_person_name',
        'billing_person_email',
        'billing_person_mobile',
        'billing_address_type',
    ];

    protected $casts = [
        'is_msme' => 'boolean',
        'gst_verified' => 'boolean',
        'udyam_verified' => 'boolean',
        'mca_verified' => 'boolean',
        'contact_name_pan_dob_verified' => 'boolean',
        'contact_email_verified' => 'boolean',
        'contact_mobile_verified' => 'boolean',
        'contact_dob' => 'date',
        'management_dob' => 'date',
        'authorized_dob' => 'date',
        'management_pan_verified' => 'boolean',
        'management_email_verified' => 'boolean',
        'management_mobile_verified' => 'boolean',
        'management_din_verified' => 'boolean',
        'authorized_pan_verified' => 'boolean',
        'authorized_email_verified' => 'boolean',
        'authorized_mobile_verified' => 'boolean',
        'completed_at' => 'datetime:Asia/Kolkata',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    public function gstVerification(): BelongsTo
    {
        return $this->belongsTo(GstVerification::class, 'gst_verification_id');
    }

    public function udyamVerification(): BelongsTo
    {
        return $this->belongsTo(UdyamVerification::class, 'udyam_verification_id');
    }

    public function mcaVerification(): BelongsTo
    {
        return $this->belongsTo(McaVerification::class, 'mca_verification_id');
    }
}
