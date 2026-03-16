<?php

namespace App\Mail;

use App\Models\Tenant\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public string $vendorContactName,
        public string $orgName,
        public string $viewToken = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Order ' . $this->purchaseOrder->po_number . ' from ' . $this->orgName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order',
            with: [
                'po'          => $this->purchaseOrder,
                'contactName' => $this->vendorContactName,
                'orgName'     => $this->orgName,
                'viewUrl'     => $this->viewToken ? url('/vendor/po/' . $this->viewToken) : null,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
