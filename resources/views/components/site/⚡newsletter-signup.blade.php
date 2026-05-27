<?php

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('nullable|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public string $source = 'home';

    public bool $submitted = false;

    public function mount(string $source = 'home'): void
    {
        $this->source = $source;
    }

    public function subscribe(): void
    {
        $key = 'public-form:newsletter:'.(request()->ip() ?? 'anon');

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('rate', "Too many attempts. Please wait {$seconds} seconds.");

            return;
        }

        $data = $this->validate();

        RateLimiter::hit($key, 60);

        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'] ?: null,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
                'source' => $this->source,
            ],
        );

        $this->reset(['name', 'email']);
        $this->submitted = true;
    }
}; ?>

<section class="bg-cream-100 py-20">
    <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Newsletter</p>
        <h2 class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl">Stay Connected</h2>
        <p class="mt-4 text-sm leading-relaxed text-ink-500">
            Subscribe to receive updates about our programs, events, and community initiatives.
        </p>

        @if ($submitted)
            <div
                role="status"
                class="mx-auto mt-8 max-w-xl rounded-2xl border border-brand-200 bg-white p-6 text-sm text-brand-800"
            >
                <p class="font-semibold text-brand-900">Thank you for subscribing.</p>
                <p class="mt-1 text-ink-500">
                    We'll keep you posted on the foundation's work, you can unsubscribe at any time.
                </p>
            </div>
        @else
            <form
                wire:submit="subscribe"
                class="mx-auto mt-8 grid max-w-xl gap-3 text-left sm:grid-cols-[1fr_1.4fr_auto]"
            >
                <label class="sr-only" for="newsletter-name">Name</label>
                <input
                    id="newsletter-name"
                    type="text"
                    wire:model="name"
                    placeholder="Name"
                    class="rounded-md border border-brand-200 bg-white px-4 py-2.5 text-sm text-brand-900 placeholder-brand-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                />

                <label class="sr-only" for="newsletter-email">Email address</label>
                <input
                    id="newsletter-email"
                    type="email"
                    wire:model="email"
                    placeholder="Email address"
                    required
                    class="rounded-md border border-brand-200 bg-white px-4 py-2.5 text-sm text-brand-900 placeholder-brand-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
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
                    <p class="sm:col-span-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('email')
                    <p class="sm:col-span-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('name')
                    <p class="sm:col-span-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </form>
        @endif
    </div>
</section>
