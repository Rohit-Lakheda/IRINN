<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReactivationInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $applicationId,
        public string $invoiceNumber,
        public float $totalAmount,
        public ?string $invoicePdfPath = null,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NIXI Reactivation Charges Invoice',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reactivation-invoice',
        );
    }

    public function attachments(): array
    {
        if (! $this->invoicePdfPath) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('public', $this->invoicePdfPath)
                ->as('Reactivation_Invoice_'.$this->applicationId.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
