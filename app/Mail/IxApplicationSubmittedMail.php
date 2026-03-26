<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class IxApplicationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Application $application
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'IX Application Submitted - '.$this->application->application_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ix.application-submitted',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        $applicationData = $this->application->application_data ?? [];
        
        // Attach application PDF if available
        if (isset($applicationData['pdfs']['application_pdf'])) {
            $attachments[] = Attachment::fromStorageDisk('public', $applicationData['pdfs']['application_pdf'])
                ->as('IX_Application_'.$this->application->application_id.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
