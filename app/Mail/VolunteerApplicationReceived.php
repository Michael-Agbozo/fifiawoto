<?php

namespace App\Mail;

use App\Models\VolunteerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VolunteerApplicationReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public VolunteerApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New volunteer application: '.$this->application->full_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.volunteer-application-received',
        );
    }
}
