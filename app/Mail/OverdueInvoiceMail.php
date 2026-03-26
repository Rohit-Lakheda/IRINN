<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class OverdueInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public Application $application,
        public string $recipientEmail,
        public string $recipientName
    ) {}

    public function envelope(): Envelope
    {
        $daysOverdue = now('Asia/Kolkata')->diffInDays($this->invoice->due_date, false);
        $daysOverdue = abs($daysOverdue);

        return new Envelope(
            subject: 'Overdue Invoice Reminder - '.$this->invoice->invoice_number.' ('.$daysOverdue.' day(s) overdue)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.overdue-invoice',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        // Attach invoice PDF if it exists
        if ($this->invoice->pdf_path && Storage::disk('public')->exists($this->invoice->pdf_path)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->invoice->pdf_path)
                ->as($this->invoice->invoice_number.'_invoice.pdf');
        }

        return $attachments;
    }
}
