<?php

namespace App\Models;

use App\Enums\NewsletterCampaignStatus;
use Database\Factories\NewsletterCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsletterCampaign extends Model
{
    /** @use HasFactory<NewsletterCampaignFactory> */
    use HasFactory;

    protected $fillable = ['subject', 'body', 'status', 'created_by', 'sent_by', 'started_at', 'finished_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => NewsletterCampaignStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return HasMany<NewsletterCampaignRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(NewsletterCampaignRecipient::class);
    }

    /**
     * The body as safe HTML: staff write Markdown, and raw HTML or unsafe links are dropped.
     */
    public function bodyHtml(): string
    {
        return Str::markdown($this->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    /** Mark the campaign sent once no recipient is still waiting. */
    public function finishIfComplete(): void
    {
        if ($this->recipients()->where('status', NewsletterCampaignRecipient::STATUS_PENDING)->doesntExist()) {
            self::query()->whereKey($this->id)
                ->where('status', NewsletterCampaignStatus::Sending->value)
                ->update(['status' => NewsletterCampaignStatus::Sent->value, 'finished_at' => now()]);
        }
    }
}
