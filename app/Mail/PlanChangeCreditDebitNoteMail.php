<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanChangeCreditDebitNoteMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $userName,
        public string $applicationId,
        public string $noteNumber,
        public string $noteType, // 'credit' or 'debit'
        public float $amount,
        public ?string $notePdfPath = null,
        public ?string $authorizedPersonName = null,
        public ?string $ispName = null
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ispName = $this->ispName ?: $this->userName;
        $noteTypeLabel = $this->noteType === 'credit' ? 'Credit Note' : 'Debit Note';

        return new Envelope(
            subject: "NIXI Peering {$noteTypeLabel} - {$this->noteNumber}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-change-note',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if (! $this->notePdfPath) {
            return [];
        }

        $noteTypeLabel = $this->noteType === 'credit' ? 'Credit' : 'Debit';

        return [
            Attachment::fromStorageDisk('public', $this->notePdfPath)
                ->as("{$noteTypeLabel}_Note_{$this->applicationId}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
