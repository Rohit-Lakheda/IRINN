<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'sender_type',
        'sender_id',
        'message',
        'is_internal',
    ];

    /**
     * Get the ticket this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get attachments for this message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Get sender name.
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->sender_type === 'user') {
            $user = Registration::find($this->sender_id);
            return $user ? $user->fullname : 'User';
        } elseif ($this->sender_type === 'admin') {
            if ($this->sender_id === null) {
                // System message (escalation, etc.)
                return 'System';
            }
            $admin = Admin::find($this->sender_id);
            return $admin ? $admin->name : 'Admin';
        } elseif ($this->sender_type === 'superadmin') {
            $superadmin = SuperAdmin::find($this->sender_id);
            return $superadmin ? $superadmin->name : 'Super Admin';
        }

        return 'Unknown';
    }
}

