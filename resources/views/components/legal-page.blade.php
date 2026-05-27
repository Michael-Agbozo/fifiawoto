@props([
    'title',
    'kicker' => 'Legal',
    'updated' => null,
    'intro' => null,
])

<x-layouts::site :title="$title">
    <section class="bg-cream-100 py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-700">{{ $kicker }}</p>
            <h1 class="mt-4 font-serif text-4xl text-brand-900 sm:text-5xl">{{ $title }}</h1>
            @if ($updated)
                <p class="mt-3 text-xs font-medium uppercase tracking-[0.18em] text-ink-500">
                    Last updated {{ \Illuminate\Support\Carbon::parse($updated)->format('F j, Y') }}
                </p>
            @endif
            @if ($intro)
                <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-ink-500">{{ $intro }}</p>
            @endif
        </div>
    </section>

    <section class="py-16 sm:py-20">
        <article class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-8 text-sm leading-relaxed text-ink-700 sm:text-base
                        [&_h2]:font-serif [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-brand-900 [&_h2]:mt-10 [&_h2]:mb-3
                        [&_h3]:font-serif [&_h3]:text-lg [&_h3]:font-bold [&_h3]:text-ink-900 [&_h3]:mt-6 [&_h3]:mb-2
                        [&_p]:text-ink-500
                        [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-2 [&_ul]:text-ink-500
                        [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-2 [&_ol]:text-ink-500
                        [&_a]:font-semibold [&_a]:text-gold-500 [&_a]:underline-offset-4 hover:[&_a]:underline">
                {{ $slot }}
            </div>

            <hr class="my-12 border-cream-300">

            <div class="rounded-3xl border border-cream-300 bg-cream-100 p-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Have questions about this policy?</p>
                <p class="mt-3 text-sm text-ink-700">
                    Email <a href="mailto:hello@fifiawoto.org" class="font-semibold text-gold-500 hover:text-brand-900">hello@fifiawoto.org</a>
                    or use our <a href="{{ route('contact') }}" wire:navigate class="font-semibold text-gold-500 hover:text-brand-900">contact form</a>.
                </p>
            </div>
        </article>
    </section>
</x-layouts::site>
