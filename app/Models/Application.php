<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'pan_card_no',
        'application_id',
        'application_type',
        'status',
        'application_data',
        'registration_details',
        'kyc_details',
        'authorized_representative_details',
        'rejection_reason',
        'resubmission_query',
        'current_processor_id',
        'current_finance_id',
        'current_technical_id',
        'current_ix_processor_id',
        'current_ix_legal_id',
        'current_ix_head_id',
        'current_ceo_id',
        'current_nodal_officer_id',
        'current_ix_tech_team_id',
        'current_ix_account_id',
        'assigned_port_capacity',
        'assigned_port_number',
        'customer_id',
        'membership_id',
        'assigned_ip',
        'service_activation_date',
        'billing_anchor_date',
        'irinn_resources_allocated',
        'billing_cycle',
        'service_status',
        'billing_resume_date',
        'suspended_from',
        'disconnected_at',
        'is_active',
        'deactivated_at',
        'deactivated_by',
        'gst_verification_id',
        'seller_state_code',
        'udyam_verification_id',
        'mca_verification_id',
        'roc_iec_verification_id',
        'submitted_at',
        'approved_at',
        'irinn_form_version',
        'irinn_current_stage',
        'irinn_company_type',
        'irinn_cin_number',
        'irinn_udyam_number',
        'irinn_registration_document_path',
        'irinn_organisation_name',
        'irinn_organisation_address',
        'irinn_organisation_postcode',
        'irinn_industry_type',
        'irinn_account_name',
        'irinn_has_gst_number',
        'irinn_billing_gstin',
        'irinn_ca_declaration_path',
        'irinn_billing_legal_name',
        'irinn_billing_pan',
        'irinn_billing_address',
        'irinn_billing_postcode',
        'irinn_mr_name',
        'irinn_mr_designation',
        'irinn_mr_email',
        'irinn_mr_mobile',
        'irinn_mr_din',
        'irinn_tp_name',
        'irinn_tp_designation',
        'irinn_tp_email',
        'irinn_tp_mobile',
        'irinn_abuse_name',
        'irinn_abuse_designation',
        'irinn_abuse_email',
        'irinn_abuse_mobile',
        'irinn_br_name',
        'irinn_br_designation',
        'irinn_br_email',
        'irinn_br_mobile',
        'irinn_asn_required',
        'irinn_ipv4_resource_size',
        'irinn_ipv4_resource_addresses',
        'irinn_ipv6_resource_size',
        'irinn_ipv6_resource_addresses',
        'irinn_resource_fee_amount',
        'irinn_billing_discount_percent',
        'irinn_upstream_provider_name',
        'irinn_upstream_as_number',
        'irinn_upstream_mobile',
        'irinn_upstream_email',
        'irinn_sign_name',
        'irinn_sign_dob',
        'irinn_sign_pan',
        'irinn_sign_email',
        'irinn_sign_mobile',
        'irinn_signature_proof_path',
        'irinn_board_resolution_path',
        'irinn_kyc_network_diagram_path',
        'irinn_kyc_equipment_invoice_path',
        'irinn_kyc_bandwidth_proof_path',
        'irinn_kyc_irinn_agreement_path',
        'irinn_other_doc_1_label',
        'irinn_other_doc_1_path',
        'irinn_other_doc_2_label',
        'irinn_other_doc_2_path',
        'irinn_other_doc_3_label',
        'irinn_other_doc_3_path',
        'irinn_other_doc_4_label',
        'irinn_other_doc_4_path',
        'irinn_other_doc_5_label',
        'irinn_other_doc_5_path',
    ];

    protected $casts = [
        'application_data' => 'array',
        'irinn_has_gst_number' => 'boolean',
        'irinn_asn_required' => 'boolean',
        'irinn_sign_dob' => 'date',
        'irinn_resource_fee_amount' => 'decimal:2',
        'irinn_billing_discount_percent' => 'decimal:2',
        'registration_details' => 'array',
        'kyc_details' => 'array',
        'authorized_representative_details' => 'array',
        'is_active' => 'boolean',
        'service_activation_date' => 'date',
        'billing_anchor_date' => 'date',
        'irinn_resources_allocated' => 'boolean',
        'billing_resume_date' => 'date',
        'suspended_from' => 'date',
        'disconnected_at' => 'date',
        'deactivated_at' => 'datetime:Asia/Kolkata',
        'submitted_at' => 'datetime:Asia/Kolkata',
        'approved_at' => 'datetime:Asia/Kolkata',
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the user that owns the application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get the processor admin (legacy - for backward compatibility).
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_processor_id');
    }

    /**
     * Get the finance admin (legacy - for backward compatibility).
     */
    public function finance(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_finance_id');
    }

    /**
     * Get the technical admin (legacy - for backward compatibility).
     */
    public function technical(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_technical_id');
    }

    /**
     * Get the IX processor admin.
     */
    public function ixProcessor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_ix_processor_id');
    }

    /**
     * Get the IX legal admin.
     */
    public function ixLegal(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_ix_legal_id');
    }

    /**
     * Get the IX head admin.
     */
    public function ixHead(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_ix_head_id');
    }

    /**
     * Get the CEO admin.
     */
    public function ceo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_ceo_id');
    }

    /**
     * Get the nodal officer admin.
     */
    public function nodalOfficer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_nodal_officer_id');
    }

    /**
     * Get the IX tech team admin.
     */
    public function ixTechTeam(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_ix_tech_team_id');
    }

    /**
     * Get the IX account admin.
     */
    public function ixAccount(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'current_ix_account_id');
    }

    /**
     * Get the status history.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }

    public function serviceStatusHistories(): HasMany
    {
        return $this->hasMany(ApplicationServiceStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function reactivationRequests(): HasMany
    {
        return $this->hasMany(ApplicationReactivationRequest::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the payment transactions for this application.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get plan change requests for this application.
     */
    public function planChangeRequests(): HasMany
    {
        return $this->hasMany(PlanChangeRequest::class);
    }

    /**
     * Get the port capacity that is currently in effect for display (admin/user).
     * Uses the latest approved plan change with effective_from <= today if present,
     * so the updated plan is shown even before the auto-update cron has run.
     */
    public function getEffectivePortCapacity(): ?string
    {
        $today = \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d');

        $effectiveChange = PlanChangeRequest::where('application_id', $this->id)
            ->where('status', 'approved')
            ->whereNotNull('effective_from')
            ->whereDate('effective_from', '<=', $today)
            ->whereColumn('current_port_capacity', '!=', 'new_port_capacity')
            ->orderBy('effective_from', 'desc')
            ->first();

        if ($effectiveChange && $effectiveChange->new_port_capacity) {
            return $effectiveChange->new_port_capacity;
        }

        return $this->assigned_port_capacity
            ?? \Illuminate\Support\Arr::get($this->application_data ?? [], 'port_selection.capacity');
    }

    /**
     * Get the payment verification logs for this application.
     */
    public function paymentVerificationLogs(): HasMany
    {
        return $this->hasMany(PaymentVerificationLog::class);
    }

    /**
     * Get the invoices for this application.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the admin who deactivated this member.
     */
    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'deactivated_by');
    }

    /**
     * Get the GST verification.
     */
    public function gstVerification(): BelongsTo
    {
        return $this->belongsTo(GstVerification::class);
    }

    /**
     * Get the GST change history for this application.
     */
    public function gstChangeHistory(): HasMany
    {
        return $this->hasMany(ApplicationGstChangeHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the GST update requests for this application.
     */
    public function gstUpdateRequests(): HasMany
    {
        return $this->hasMany(ApplicationGstUpdateRequest::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the pending GST update request for this application.
     */
    public function pendingGstUpdateRequest(): HasOne
    {
        return $this->hasOne(ApplicationGstUpdateRequest::class)
            ->where('status', 'pending')
            ->latest();
    }

    /**
     * Get the UDYAM verification.
     */
    public function udyamVerification(): BelongsTo
    {
        return $this->belongsTo(UdyamVerification::class);
    }

    /**
     * Get the MCA verification.
     */
    public function mcaVerification(): BelongsTo
    {
        return $this->belongsTo(McaVerification::class);
    }

    /**
     * Get the ROC IEC verification.
     */
    public function rocIecVerification(): BelongsTo
    {
        return $this->belongsTo(RocIecVerification::class);
    }

    /**
     * Generate a unique application ID.
     */
    public static function generateApplicationId(): string
    {
        do {
            $applicationId = 'APP'.strtoupper(Str::random(8));
        } while (self::where('application_id', $applicationId)->exists());

        return $applicationId;
    }

    /**
     * Check if application is visible to IX Processor.
     */
    public function isVisibleToIxProcessor(): bool
    {
        return in_array($this->status, ['submitted', 'resubmitted', 'processor_resubmission', 'legal_sent_back', 'head_sent_back']);
    }

    /**
     * Check if application is visible to IX Legal.
     */
    public function isVisibleToIxLegal(): bool
    {
        return $this->status === 'processor_forwarded_legal';
    }

    /**
     * Check if application is visible to IX Head.
     */
    public function isVisibleToIxHead(): bool
    {
        return in_array($this->status, ['legal_forwarded_head', 'ceo_sent_back_head']);
    }

    /**
     * Check if application is visible to CEO.
     */
    public function isVisibleToCeo(): bool
    {
        return $this->status === 'head_forwarded_ceo';
    }

    /**
     * Check if application is visible to Nodal Officer.
     */
    public function isVisibleToNodalOfficer(): bool
    {
        return $this->status === 'ceo_approved';
    }

    /**
     * Check if application is visible to IX Tech Team.
     */
    public function isVisibleToIxTechTeam(): bool
    {
        return $this->status === 'port_assigned';
    }

    /**
     * Check if application is visible to IX Account.
     */
    public function isVisibleToIxAccount(): bool
    {
        // IX Account can see all LIVE applications (is_active = true) regardless of status
        // They can always verify payment and generate invoices for live applications
        return $this->is_active && in_array($this->status, ['ip_assigned', 'invoice_pending', 'payment_verified', 'approved']);
    }

    /**
     * Whether the IRINN application stores the multi-step form in normalized columns (not legacy JSON parts).
     */
    public function hasIrinnNormalizedData(): bool
    {
        if ($this->application_type !== 'IRINN') {
            return false;
        }

        return filled($this->irinn_organisation_name)
            || filled($this->irinn_account_name)
            || filled($this->irinn_ipv4_resource_size)
            || filled($this->irinn_ipv6_resource_size);
    }

    /**
     * Document download keys for stored files on the applications table (irinn_*_path columns).
     */
    public static function isIrinnStoredPathDocumentKey(string $key): bool
    {
        return str_starts_with($key, 'irinn_')
            && str_ends_with($key, '_path')
            && in_array($key, (new self)->getFillable(), true);
    }

    /**
     * Get status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        // IRINN simplified workflow statuses
        if ($this->application_type === 'IRINN') {
            return match ($this->status) {
                'draft' => 'Draft',
                'pending' => 'Pending',
                'submitted' => 'Helpdesk',
                'helpdesk' => 'Helpdesk',
                'hostmaster' => 'Hostmaster',
                'billing' => 'Billing',
                'billing_approved' => 'Billing approved',
                'resubmission_requested' => 'Resubmission requested',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => ucfirst((string) ($this->status ?: 'Unknown')),
            };
        }

        $statuses = [
            'draft' => 'Draft',
            'submitted' => 'Application Submitted',
            'resubmitted' => 'Application Resubmitted',
            'payment_pending' => 'Payment Pending',
            'processor_resubmission' => 'Resubmission Requested',
            'processor_forwarded_legal' => 'Forwarded to Legal',
            'legal_forwarded_head' => 'Forwarded to IX Head',
            'legal_sent_back' => 'Sent back to Processor',
            'head_forwarded_ceo' => 'Forwarded to CEO',
            'ceo_sent_back_head' => 'Sent back to IX Head by CEO',
            'head_sent_back' => 'Sent back to Processor',
            'ceo_approved' => 'Approved by CEO',
            'ceo_rejected' => 'Rejected by CEO',
            'port_assigned' => 'Port Assigned',
            'port_hold' => 'On Hold',
            'port_not_feasible' => 'Not Feasible',
            'customer_denied' => 'Customer Denied',
            'ip_assigned' => 'IP Assigned - Live',
            'invoice_pending' => 'Invoice Pending',
            'payment_verified' => 'Payment Verified',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            // Legacy statuses for backward compatibility
            'pending' => 'Pending (Processor)',
            'processor_approved' => 'Approved by Processor (Finance)',
            'finance_approved' => 'Approved by Finance (Technical)',
            'finance_review' => 'Sent back to Finance',
            'processor_review' => 'Sent back to Processor',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get current stage name.
     */
    public function getCurrentStageAttribute(): string
    {
        // IRINN simplified workflow stages
        if ($this->application_type === 'IRINN') {
            return match ($this->status) {
                'draft' => 'Draft',
                'pending' => 'Pending',
                'submitted' => 'Helpdesk',
                'helpdesk' => 'Helpdesk',
                'hostmaster' => 'Hostmaster',
                'billing' => 'Billing',
                'billing_approved' => 'Billing approved',
                'resubmission_requested' => 'Resubmission requested',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => ucfirst((string) ($this->status ?: 'Unknown')),
            };
        }

        $stageMap = [
            'draft' => 'Draft',
            'submitted' => 'IX Application Processor',
            'resubmitted' => 'IX Application Processor',
            'payment_pending' => 'Payment Pending',
            'processor_resubmission' => 'IX Application Processor',
            'processor_forwarded_legal' => 'IX Legal',
            'legal_forwarded_head' => 'IX Head',
            'legal_sent_back' => 'IX Application Processor',
            'head_forwarded_ceo' => 'CEO',
            'head_sent_back' => 'IX Application Processor',
            'ceo_sent_back_head' => 'IX Head',
            'ceo_approved' => 'Nodal Officer',
            'ceo_rejected' => 'Rejected',
            'port_assigned' => 'IX Tech Team',
            'port_hold' => 'Nodal Officer',
            'port_not_feasible' => 'Nodal Officer',
            'customer_denied' => 'Nodal Officer',
            'ip_assigned' => 'IX Account',
            'invoice_pending' => 'IX Account',
            'payment_verified' => 'Completed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            // Legacy statuses
            'pending' => 'Processor',
            'processor_approved' => 'Finance',
            'finance_approved' => 'Technical',
            'finance_review' => 'Finance',
            'processor_review' => 'Processor',
        ];

        return $stageMap[$this->status] ?? 'Unknown';
    }

    /**
     * Check if application is visible to Processor (legacy - for backward compatibility).
     */
    public function isVisibleToProcessor(): bool
    {
        return in_array($this->status, ['pending', 'processor_review']);
    }

    /**
     * Check if application is visible to Finance (legacy - for backward compatibility).
     */
    public function isVisibleToFinance(): bool
    {
        return in_array($this->status, ['processor_approved', 'finance_review']);
    }

    /**
     * Check if application is visible to Technical (legacy - for backward compatibility).
     */
    public function isVisibleToTechnical(): bool
    {
        return in_array($this->status, ['finance_approved']);
    }

    /**
     * Get admins who have read this application.
     */
    public function readByAdmins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_application_reads', 'application_id', 'admin_id')
            ->withPivot('read_at', 'role')
            ->withTimestamps();
    }
}
