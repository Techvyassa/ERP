<?php

namespace App\Mail;

use App\Models\Control\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationQueuedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $firstName,
        public string $email
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We are setting up your workspace!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-queued',
            with: [
                'organizationName' => $this->organization->org_name,
                'firstName' => $this->firstName,
                'email' => $this->email,
                'organizationUrl' => url('/' . $this->organization->org_slug),
                'loginUrl' => config('app.url') . '/login',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
