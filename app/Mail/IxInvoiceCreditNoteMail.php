<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IxInvoiceCreditNoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $applicationId,
        public string $invoiceNumber,
        public string $creditNoteNumber
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NIXI Credit Note Generated - '.$this->creditNoteNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ix-invoice-credit-note',
        );
    }
}
