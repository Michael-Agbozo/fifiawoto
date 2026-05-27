<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Volunteer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventVolunteerInvitation extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Event $event,
        public Volunteer $volunteer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New event opportunity: '.$this->event->title,
            to: [$this->volunteer->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event-volunteer-invitation',
        );
    }
}
