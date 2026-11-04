<?php

namespace App\Jobs;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Snapshots the active subscriber list, then queues one email per recipient,
 * spaced out so a large list doesn't trip the mail provider's rate limit.
 */
class SendNewsletterCampaign implements ShouldQueue
{
    use Queueable;

    public const EMAILS_PER_MINUTE = 60;

    public function __construct(public NewsletterCampaign $campaign) {}

    public function handle(): void
    {
        NewsletterSubscriber::query()->whereNull('unsubscribed_at')->select(['id', 'email'])
            ->chunkById(500, function ($subscribers): void {
                NewsletterCampaignRecipient::query()->insertOrIgnore($subscribers->map(fn (NewsletterSubscriber $subscriber) => [
                    'newsletter_campaign_id' => $this->campaign->id,
                    'newsletter_subscriber_id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'status' => NewsletterCampaignRecipient::STATUS_PENDING,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            });

        $position = 0;

        $this->campaign->recipients()
            ->where('status', NewsletterCampaignRecipient::STATUS_PENDING)
            ->select('id')
            ->eachById(function (NewsletterCampaignRecipient $recipient) use (&$position): void {
                SendNewsletterCampaignEmail::dispatch($recipient->id)
                    ->delay(now()->addMinutes(intdiv($position, self::EMAILS_PER_MINUTE)));
                $position++;
            });

        $this->campaign->finishIfComplete();
    }
}
