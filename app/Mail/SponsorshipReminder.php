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
class SponsorshipReminder extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Sponsorship $sponsorship) {}

    public function envelope(): Envelope
    {
        $childName = $this->sponsorship->beneficiary->public_display_name;

        return new Envelope(
            subject: $this->sponsorship->isOverdue()
                ? "A gentle reminder about your pledge for {$childName}"
                : "Your monthly pledge for {$childName} is coming up",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.sponsorship-reminder',
            with: [
                'childName' => $this->sponsorship->beneficiary->public_display_name,
                'isOverdue' => $this->sponsorship->isOverdue(),
                'stopRemindersUrl' => URL::signedRoute('sponsorship-reminders.stop', ['sponsorship' => $this->sponsorship]),
            ],
        );
    }
}
