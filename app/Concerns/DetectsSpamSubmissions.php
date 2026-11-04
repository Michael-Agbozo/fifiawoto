<?php

namespace App\Concerns;

use Livewire\Attributes\Locked;

/**
 * Lightweight bot protection for public Livewire forms: a hidden honeypot
 * field that humans never fill, plus a minimum time between the form being
 * rendered and submitted. Callers should fake success when this trips so
 * bots get no signal that they were caught.
 */
trait DetectsSpamSubmissions
{
    /** Minimum seconds a human needs between page load and submit. */
    public const MINIMUM_FILL_SECONDS = 3;

    /** Honeypot — rendered off-screen and must stay empty. */
    public string $website = '';

    #[Locked]
    public int $formRenderedAt = 0;

    public function mountDetectsSpamSubmissions(): void
    {
        $this->formRenderedAt = now()->getTimestamp();
    }

    protected function isLikelySpam(): bool
    {
        if (filled($this->website)) {
            return true;
        }

        return $this->formRenderedAt === 0
            || now()->getTimestamp() - $this->formRenderedAt < self::MINIMUM_FILL_SECONDS;
    }
}
