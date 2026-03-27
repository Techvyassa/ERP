<?php

namespace App\Mail;

use App\Models\Control\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredentialsReadyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $firstName,
        public string $email,
        public string $tempPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name') . ' login credentials for ' . $this->organization->org_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credentials-ready',
            with: [
                'organizationName' => $this->organization->org_name,
                'firstName' => $this->firstName,
                'email' => $this->email,
                'tempPassword' => $this->tempPassword,
                'loginUrl' => config('app.url') . '/login',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
