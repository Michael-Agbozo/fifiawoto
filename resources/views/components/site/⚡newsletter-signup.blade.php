<?php

use App\Concerns\DetectsSpamSubmissions;
use App\Mail\NewsletterResubscribeConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    use DetectsSpamSubmissions;

    #[Validate('nullable|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Locked]
    public string $source = 'home';

    public bool $submitted = false;

    /** Form only, without the section wrapper and heading — used inside the site popup. */
    #[Locked]
    public bool $compact = false;

    public function mount(string $source = 'home', bool $compact = false): void
    {
        $this->source = $source;
        $this->compact = $compact;
    }

    public function subscribe(): void
    {
        $key = 'public-form:newsletter:'.(request()->ip() ?? 'anon');

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('rate', "Too many attempts. Please wait {$seconds} seconds.");

            return;
        }

        if ($this->isLikelySpam()) {
            RateLimiter::hit($key, 60);
            $this->reset(['name', 'email', 'website']);
            $this->submitted = true;

            return;
        }

        $this->email = Str::lower(trim($this->email));
        $this->name = trim($this->name);

        $data = $this->validate();

        RateLimiter::hit($key, 60);

        $subscriber = NewsletterSubscriber::query()->where('email', $data['email'])->first();

        if ($subscriber === null) {
            NewsletterSubscriber::query()->create([
                'email' => $data['email'],
                'name' => $data['name'] ?: null,
                'subscribed_at' => now(),
                'source' => $this->source,
            ]);
        } elseif ($subscriber->unsubscribed_at !== null) {
            // Never silently re-subscribe an address that opted out: the owner must confirm by email.
            Mail::to($subscriber->email)->queue(new NewsletterResubscribeConfirmation($subscriber));
        }

        // Existing active subscribers are left untouched (no name/source overwrite). The same
        // message is shown in every case so the form doesn't reveal who is subscribed.
        $this->reset(['name', 'email']);
        $this->submitted = true;
    }
}; ?>

<section @class(['bg-cream-100 py-20' => ! $compact])>
    <div @class(['mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8' => ! $compact])>
        @unless ($compact)
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Newsletter</p>
            <h2 class="mt-3 font-serif text-3xl text-heading sm:text-4xl">Stay Connected</h2>
            <p class="mt-4 text-sm leading-relaxed text-ink-500">
                Subscribe to receive updates about our programs, events, and community initiatives.
            </p>
        @endunless

        @if ($submitted)
            <div
                role="status"
                @class(['rounded-2xl border border-brand-200 bg-surface p-6 text-sm text-heading-soft', 'mx-auto mt-8 max-w-xl' => ! $compact])
            >
                <p class="font-semibold text-heading">Thank you for subscribing.</p>
                <p class="mt-1 text-ink-500">
                    We'll keep you posted on the foundation's work, you can unsubscribe at any time.
                    If this address previously unsubscribed, check your inbox for a link to confirm.
                </p>
            </div>
        @else
            <form
                wire:submit="subscribe"
                @class(['grid gap-3 text-left', 'mx-auto mt-8 max-w-xl sm:grid-cols-[1fr_1.4fr_auto]' => ! $compact])
            >
                @include('components.site.partials.honeypot')

                <label class="sr-only" for="newsletter-name-{{ $this->getId() }}">Name</label>
                <input
                    id="newsletter-name-{{ $this->getId() }}"
                    type="text"
                    wire:model="name"
                    placeholder="Name"
                    class="rounded-md border border-brand-200 bg-surface px-4 py-2.5 text-sm text-heading placeholder-brand-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                />

                <label class="sr-only" for="newsletter-email-{{ $this->getId() }}">Email address</label>
                <input
                    id="newsletter-email-{{ $this->getId() }}"
                    type="email"
                    wire:model="email"
                    placeholder="Email address"
                    required
                    class="rounded-md border border-brand-200 bg-surface px-4 py-2.5 text-sm text-heading placeholder-brand-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                />

                <button
                    type="submit"
                    class="rounded-md bg-gold-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900 disabled:opacity-60"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Subscribe</span>
                    <span wire:loading>Subscribing…</span>
                </button>

                @error('rate')
                    <p @class(['text-sm text-red-600', 'sm:col-span-3' => ! $compact])>{{ $message }}</p>
                @enderror
                @error('email')
                    <p @class(['text-sm text-red-600', 'sm:col-span-3' => ! $compact])>{{ $message }}</p>
                @enderror
                @error('name')
                    <p @class(['text-sm text-red-600', 'sm:col-span-3' => ! $compact])>{{ $message }}</p>
                @enderror
            </form>
        @endif
    </div>
</section>
