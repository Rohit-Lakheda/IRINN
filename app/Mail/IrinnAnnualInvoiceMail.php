<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IrinnAnnualInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $applicationId,
        public string $invoiceNumber,
        public float $totalAmount,
        public string $financialYearLabel,
        public string $invoicePdfPath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'IRINN annual invoice '.$this->invoiceNumber.' — '.$this->applicationId,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.irinn-annual-invoice',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->invoicePdfPath === '') {
            return [];
        }

        $safeName = 'IRINN_Annual_'.preg_replace('/[^A-Za-z0-9._-]+/', '_', $this->invoiceNumber).'.pdf';

        return [
            Attachment::fromStorageDisk('public', $this->invoicePdfPath)
                ->as($safeName)
                ->withMime('application/pdf'),
        ];
    }
}
