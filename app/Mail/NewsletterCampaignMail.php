<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewsletterCampaignMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  ?NewsletterSubscriber  $subscriber  Null for previews and test sends, which have no unsubscribe link.
     */
    public function __construct(
        public NewsletterCampaign $campaign,
        public ?NewsletterSubscriber $subscriber = null,
        public bool $isTest = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->isTest ? '[Test] ' : '').$this->campaign->subject,
        );
    }

    public function headers(): Headers
    {
        if (! $this->subscriber) {
            return new Headers;
        }

        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletter-campaign',
            with: [
                'bodyHtml' => $this->campaign->bodyHtml(),
                'unsubscribeUrl' => $this->unsubscribeUrl(),
            ],
        );
    }

    private function unsubscribeUrl(): ?string
    {
        return $this->subscriber
            ? URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->subscriber])
            : null;
    }
}
