<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IrinnResubmissionRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public string $resubmissionReason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'IRINN Application Resubmission Required — '.$this->application->application_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.irinn.resubmission-requested',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
