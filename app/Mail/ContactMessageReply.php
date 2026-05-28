<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReply extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $recipientEmail,
        public string $subjectLine,
        public string $bodyText,
        public string $originalMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            to: [$this->recipientEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message-reply',
        );
    }
}
