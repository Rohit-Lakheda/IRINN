<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class IxApplicationIpAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Application $application,
        public string $assignedIp,
        public string $customerId,
        public string $membershipId,
        public ?string $serviceActivationDate = null
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'IX Application Live - IP Assigned - '.$this->application->application_id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ix.application-ip-assigned',
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
        
        // Attach membership invoice if available
        if (isset($applicationData['pdfs']['membership_invoice'])) {
            $attachments[] = Attachment::fromStorageDisk('public', $applicationData['pdfs']['membership_invoice'])
                ->as('Membership_Invoice_'.$this->application->application_id.'.pdf')
                ->withMime('application/pdf');
        }
        
        // Attach IX invoice if available
        if (isset($applicationData['pdfs']['ix_invoice'])) {
            $attachments[] = Attachment::fromStorageDisk('public', $applicationData['pdfs']['ix_invoice'])
                ->as('IX_Invoice_'.$this->application->application_id.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
