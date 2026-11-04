<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Sent when someone submits the signup form with an address that previously
 * unsubscribed — they must click through before we resume sending to them.
 */
class NewsletterResubscribeConfirmation extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** Hours the confirmation link stays valid. */
    public const LINK_TTL_HOURS = 72;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your newsletter subscription',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletter-resubscribe-confirmation',
            with: [
                'confirmUrl' => URL::temporarySignedRoute(
                    'newsletter.resubscribe',
                    now()->addHours(self::LINK_TTL_HOURS),
                    ['subscriber' => $this->subscriber],
                ),
                'unsubscribeUrl' => URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->subscriber]),
            ],
        );
    }
}
