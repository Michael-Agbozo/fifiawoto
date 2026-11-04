<?php

namespace App\Jobs;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaignRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNewsletterCampaignEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(public int $recipientId) {}

    public function handle(): void
    {
        $recipient = NewsletterCampaignRecipient::query()->with(['campaign', 'subscriber'])->find($this->recipientId);

        if (! $recipient || $recipient->status !== NewsletterCampaignRecipient::STATUS_PENDING) {
            return;
        }

        // Honour an unsubscribe that arrived after the send started.
        if (! $recipient->subscriber || $recipient->subscriber->unsubscribed_at !== null) {
            $recipient->update(['status' => NewsletterCampaignRecipient::STATUS_SKIPPED]);
        } else {
            Mail::to($recipient->email)->send(new NewsletterCampaignMail($recipient->campaign, $recipient->subscriber));
            $recipient->update(['status' => NewsletterCampaignRecipient::STATUS_SENT, 'sent_at' => now()]);
        }

        $recipient->campaign->finishIfComplete();
    }

    public function failed(?Throwable $exception): void
    {
        $recipient = NewsletterCampaignRecipient::query()->with('campaign')->find($this->recipientId);

        $recipient?->update(['status' => NewsletterCampaignRecipient::STATUS_FAILED]);
        $recipient?->campaign->finishIfComplete();
    }
}
