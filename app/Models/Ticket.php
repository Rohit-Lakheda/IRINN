<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'type',
        'category',
        'sub_category',
        'subject',
        'description',
        'status',
        'priority',
        'escalation_level',
        'escalated_to',
        'escalated_at',
        'escalation_notes',
        'assigned_to',
        'assigned_role',
        'assigned_by',
        'assigned_at',
        'forwarded_by',
        'forwarded_at',
        'forwarding_notes',
        'resolved_at',
        'closed_at',
        'closed_by',
        'resolution_notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'escalated_at' => 'datetime',
        'forwarded_at' => 'datetime',
    ];

    /**
     * Generate unique ticket ID.
     */
    public static function generateTicketId(): string
    {
        $year = date('Y');
        $lastTicket = self::where('ticket_id', 'like', "TKT-{$year}-%")
            ->orderBy('ticket_id', 'desc')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_id, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('TKT-%s-%06d', $year, $newNumber);
    }

    /**
     * Get the user that owns the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'user_id');
    }

    /**
     * Get the admin assigned to the ticket.
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    /**
     * Get the superadmin who assigned the ticket.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'assigned_by');
    }

    /**
     * Get the admin who closed the ticket.
     */
    public function closedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'closed_by');
    }

    /**
     * Get the admin to whom the ticket is escalated.
     */
    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'escalated_to');
    }

    /**
     * Get the admin who forwarded the ticket.
     */
    public function forwardedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'forwarded_by');
    }

    /**
     * Get all messages for this ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get all attachments for this ticket.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Get status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Open',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'technical' => 'Technical',
            'billing' => 'Billing',
            'general_complaint' => 'General Complaint',
            'feedback' => 'Feedback',
            'suggestion' => 'Suggestion',
            'request' => 'Request',
            'enquiry' => 'Enquiry',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Get priority badge color.
     */
    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'secondary',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary',
        };
    }
}

