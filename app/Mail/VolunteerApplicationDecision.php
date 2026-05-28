<?php

namespace App\Mail;

use App\Models\VolunteerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VolunteerApplicationDecision extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public VolunteerApplication $application,
        public bool $approved,
        public ?string $roleLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->approved
            ? 'Welcome to the Dadaa Fifiawoto Nyamadi Foundation volunteer team'
            : 'Update on your volunteer application';

        return new Envelope(
            subject: $subject,
            to: [$this->application->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.volunteer-application-decision',
        );
    }
}
