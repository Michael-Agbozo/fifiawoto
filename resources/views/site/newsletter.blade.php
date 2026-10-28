@php
    $isUnsubscribe = $action === 'unsubscribe';
    $heading = match (true) {
        $isUnsubscribe && $done => 'You have been unsubscribed',
        $isUnsubscribe => 'Unsubscribe from our newsletter',
        $done => 'Subscription confirmed',
        default => 'Confirm your subscription',
    };
@endphp

<x-layouts::site :title="$heading" :noindex="true">
    <section class="py-20 lg:py-24" aria-labelledby="newsletter-heading">
        <div class="mx-auto max-w-xl px-4 text-center sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Newsletter</p>
            <h1 id="newsletter-heading" class="mt-3 font-serif text-3xl text-heading sm:text-4xl">{{ $heading }}</h1>

            @if ($done)
                <p role="status" class="mt-4 text-sm leading-relaxed text-ink-500">
                    @if ($isUnsubscribe)
                        {{ $subscriber->email }} will no longer receive newsletter emails from the foundation.
                    @else
                        Thank you — {{ $subscriber->email }} will receive our updates again. You can unsubscribe at any time.
                    @endif
                </p>
            @else
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    @if ($isUnsubscribe)
                        Stop sending newsletter emails to {{ $subscriber->email }}?
                    @else
                        Resume sending newsletter emails to {{ $subscriber->email }}?
                    @endif
                </p>
                <form method="POST" action="{{ $formUrl }}" class="mt-8">
                    @csrf
                    <button type="submit" class="rounded-full bg-gold-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-900">
                        {{ $isUnsubscribe ? 'Unsubscribe' : 'Yes, subscribe me again' }}
                    </button>
                </form>
            @endif

            <a href="{{ route('home') }}" wire:navigate class="mt-8 inline-block text-sm font-semibold text-gold-500 hover:text-heading">Back to the homepage</a>
        </div>
    </section>
</x-layouts::site>
