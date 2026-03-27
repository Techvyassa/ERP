<?php

namespace App\Mail;

use App\Models\Control\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProvisioningFailedUserEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $firstName,
        public string $errorMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your ' . config('app.name') . ' workspace setup',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provisioning-failed',
            with: [
                'organizationName' => $this->organization->org_name,
                'firstName' => $this->firstName,
                'organizationUrl' => url('/' . $this->organization->org_slug),
                'supportEmail' => config('mail.from.address', config('app.admin_email')),
                'errorMessage' => $this->errorMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
