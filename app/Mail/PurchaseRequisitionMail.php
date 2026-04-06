<?php

namespace App\Mail;

use App\Models\Tenant\PurchaseRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequisitionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseRequisition $purchaseRequisition,
        public string $vendorContactName,
        public string $orgName,
        public ?string $customMessage = null,
        public string $viewToken = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Requisition ' . $this->purchaseRequisition->pr_number . ' from ' . $this->orgName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-requisition',
            with: [
                'pr'            => $this->purchaseRequisition,
                'contactName'   => $this->vendorContactName,
                'orgName'       => $this->orgName,
                'customMessage' => $this->customMessage,
                'viewUrl'       => $this->viewToken ? url('/vendor/pr/' . $this->viewToken) : null,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
