<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class IxApplicationInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $userName,
        public string $applicationId,
        public string $invoiceNumber,
        public float $totalAmount,
        public string $status,
        public ?string $invoicePdfPath = null,
        public ?string $payuPaymentUrl = null,
        public ?array $payuPaymentData = null,
        public ?string $authorizedPersonName = null,
        public ?string $ispName = null,
        public ?string $billingStartDate = null,
        public ?string $billingEndDate = null
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ispName = $this->ispName ?: $this->userName;
        return new Envelope(
            subject: 'NIXI Peering charges Invoice of '.$ispName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ix-application-invoice',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->invoicePdfPath)
                ->as('Invoice_'.$this->applicationId.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}

