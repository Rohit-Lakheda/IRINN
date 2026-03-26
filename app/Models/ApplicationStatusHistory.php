<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';

    protected $fillable = [
        'application_id',
        'status_from',
        'status_to',
        'changed_by_type',
        'changed_by_id',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime:Asia/Kolkata',
        'updated_at' => 'datetime:Asia/Kolkata',
    ];

    /**
     * Get the application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the admin who made the change (polymorphic relationship).
     */
    public function changedBy()
    {
        if (! $this->changed_by_id || ! $this->changed_by_type) {
            return null;
        }

        if ($this->changed_by_type === 'admin') {
            return \App\Models\Admin::find($this->changed_by_id);
        } elseif ($this->changed_by_type === 'superadmin') {
            return \App\Models\SuperAdmin::find($this->changed_by_id);
        }

        return null;
    }

    /**
     * Human-readable labels for status slugs (matches Application status display).
     */
    public static function statusLabels(): array
    {
        return [
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
            'pending' => 'Pending (Processor)',
            'processor_approved' => 'Approved by Processor (Finance)',
            'finance_approved' => 'Approved by Finance (Technical)',
            'finance_review' => 'Sent back to Finance',
            'processor_review' => 'Sent back to Processor',
        ];
    }

    /**
     * Get human-readable label for a status slug.
     */
    public static function labelFor(string $slug): string
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return '';
        }
        $labels = self::statusLabels();
        $normalized = strtolower(str_replace(' ', '_', $slug));

        return $labels[$slug] ?? $labels[$normalized] ?? ucfirst(str_replace('_', ' ', $slug));
    }

    /**
     * Human-readable heading for this history entry (e.g. "Port Assigned → IP Assigned - Live").
     * When status is unchanged but notes describe an action (e.g. invoice generated), use that as the heading.
     * For IRINN applications, map legacy "pending" status to "Helpdesk" to align with new workflow.
     */
    public function getStatusDisplayAttribute(): string
    {
        $notes = trim((string) ($this->notes ?? ''));
        $from = trim((string) ($this->status_from ?? ''));
        $to = trim((string) ($this->status_to ?? ''));

        // Same status with action in notes: show action as heading instead of status label
        if ($from !== '' && $to !== '' && $from === $to && $notes !== '') {
            if (stripos($notes, 'Invoice generated') !== false) {
                return 'Invoice Generated';
            }
            if (stripos($notes, 'Payment verified') !== false || stripos($notes, 'Payment received') !== false) {
                return 'Payment Verified';
            }
            if (stripos($notes, 'Service activated') !== false) {
                return 'Service Activated';
            }
        }

        $applicationType = $this->application->application_type ?? null;

        $fromLabel = $from === '' ? 'New' : $this->labelForWithContext($from, $applicationType);
        $toLabel = $to === '' ? '—' : $this->labelForWithContext($to, $applicationType);

        if ($from === '' && $to !== '') {
            return $toLabel;
        }
        if ($from !== '' && $to !== '' && $from === $to) {
            return $toLabel;
        }

        return $fromLabel.' → '.$toLabel;
    }

    /**
     * Get status label with application-type specific overrides.
     */
    protected function labelForWithContext(string $slug, ?string $applicationType): string
    {
        $normalizedSlug = strtolower(trim($slug));

        // For IRINN applications, interpret legacy "pending" as the first workflow stage "Helpdesk"
        if ($applicationType === 'IRINN' && $normalizedSlug === 'pending') {
            return 'Helpdesk';
        }

        return self::labelFor($slug);
    }

    /**
     * Log a status change.
     */
    public static function log(int $applicationId, ?string $statusFrom, string $statusTo, string $changedByType, ?int $changedById = null, ?string $notes = null): self
    {
        return self::create([
            'application_id' => $applicationId,
            'status_from' => $statusFrom ?? '',
            'status_to' => $statusTo,
            'changed_by_type' => $changedByType,
            'changed_by_id' => $changedById ?? 0, // Use 0 for system changes when no user/admin ID
            'notes' => $notes,
        ]);
    }
}
