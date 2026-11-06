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
    <section class="section" aria-labelledby="newsletter-heading">
        <div class="mx-auto max-w-xl px-4 text-center sm:px-6 lg:px-8">
            <p class="eyebrow">{{ __('Newsletter') }}</p>
            <h1 id="newsletter-heading" class="mt-3 font-serif text-3xl text-heading sm:text-4xl">{{ __($heading) }}</h1>

            @if ($done)
                <p role="status" class="mt-4 text-sm leading-relaxed text-ink-500">
                    @if ($isUnsubscribe)
                        {{ __(':v1 will no longer receive newsletter emails from the foundation.', ['v1' => $subscriber->email]) }}
                    @else
                        {{ __('Thank you — :v1 will receive our updates again. You can unsubscribe at any time.', ['v1' => $subscriber->email]) }}
                    @endif
                </p>
            @else
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    @if ($isUnsubscribe)
                        {{ __('Stop sending newsletter emails to :v1?', ['v1' => $subscriber->email]) }}
                    @else
                        {{ __('Resume sending newsletter emails to :v1?', ['v1' => $subscriber->email]) }}
                    @endif
                </p>
                <form method="POST" action="{{ $formUrl }}" class="mt-8">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        {{ $isUnsubscribe ? __('Unsubscribe') : __('Yes, subscribe me again') }}
                    </button>
                </form>
            @endif

            <a href="{{ route('home') }}" wire:navigate class="mt-8 inline-block text-sm font-semibold text-gold-500 hover:text-heading">{{ __('Back to the homepage') }}</a>
        </div>
    </section>
</x-layouts::site>
