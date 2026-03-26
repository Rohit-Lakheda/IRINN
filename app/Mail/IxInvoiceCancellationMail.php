<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IxInvoiceCancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $applicationId,
        public string $invoiceNumber
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NIXI Invoice Cancelled - '.$this->invoiceNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ix-invoice-cancellation',
        );
    }
}
