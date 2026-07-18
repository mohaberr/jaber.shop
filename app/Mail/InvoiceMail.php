<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        private string $pdfBinary,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Facture ' . $this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdfBinary, 'facture-' . $this->invoice->invoice_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
