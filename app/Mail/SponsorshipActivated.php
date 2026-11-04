<?php

namespace App\Mail;

use App\Models\Sponsorship;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Only ever names the child by their guardian-approved public display name.
 */
class SponsorshipActivated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Sponsorship $sponsorship) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your sponsorship of '.$this->sponsorship->beneficiary->public_display_name.' has begun',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.sponsorship-activated',
            with: [
                'childName' => $this->sponsorship->beneficiary->public_display_name,
                'stopRemindersUrl' => URL::signedRoute('sponsorship-reminders.stop', ['sponsorship' => $this->sponsorship]),
            ],
        );
    }
}
