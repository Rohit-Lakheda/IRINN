<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id',
        'ticket_message_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    /**
     * Get the ticket this attachment belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the message this attachment belongs to.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    /**
     * Get file size in human readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if file is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->file_type ?? '', 'image/');
    }

    /**
     * Check if file is a PDF.
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'application/pdf';
    }
}

