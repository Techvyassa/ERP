<?php

namespace App\Mail;

use App\Models\Control\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $firstName,
        public string $email,
        public string $tempPassword,
        public string $departmentUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . $this->organization->org_name . ' - Your Account is Ready!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-welcome',
            with: [
                'organizationName' => $this->organization->org_name,
                'firstName'        => $this->firstName,
                'email'            => $this->email,
                'tempPassword'     => $this->tempPassword,
                'loginUrl'         => config('app.url') . '/login',
                'departmentUrl'    => $this->departmentUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
