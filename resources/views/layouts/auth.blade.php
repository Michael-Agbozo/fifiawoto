<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-cream-50 font-sans text-ink-700 antialiased">
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
            {{-- Brand panel: the fluted blue hero background, framed like the site's bands --}}
            <aside class="bg-fluted relative m-3 flex flex-col justify-between overflow-hidden rounded-3xl p-6 text-white sm:m-4 sm:p-8 lg:sticky lg:top-0 lg:m-4 lg:h-[calc(100vh-2rem)] lg:p-12">
                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('home') }}" class="inline-flex" aria-label="Fifiawoto Foundation home" wire:navigate>
                        <img src="{{ asset('images/logos/dff-sticky.svg') }}" alt="Dadaa Fifiawoto Nyamadi Foundation" class="h-11 w-auto sm:h-12">
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-semibold text-white/85 ring-1 ring-white/20 backdrop-blur-md transition hover:bg-white/20 hover:text-white" wire:navigate>
                        <span aria-hidden="true">←</span> Back to website
                    </a>
                </div>

                <div class="hidden max-w-lg lg:block">
                    <p class="eyebrow eyebrow-light">Foundation workspace</p>
                    <h2 class="mt-4 font-serif text-5xl font-bold leading-[1.05] text-white xl:text-6xl">
                        Every record is a life <em class="font-serif italic font-medium text-gold-300">we serve.</em>
                    </h2>
                    <p class="mt-6 max-w-md text-base leading-relaxed text-white/75">
                        Beneficiaries, events, donations and volunteers across Ghana, Togo, Benin and the United States, managed in one place.
                    </p>
                </div>

                <figure class="hidden max-w-md rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur-md lg:block">
                    <blockquote class="font-serif text-lg italic leading-snug text-white">“Compassion is a legacy that never fades.”</blockquote>
                    <figcaption class="mt-3 text-xs font-semibold uppercase tracking-[0.22em] text-white/60">Dadaa Fifiawoto Nyamadi Foundation</figcaption>
                </figure>
            </aside>

            {{-- Form --}}
            <main class="flex flex-col justify-center px-5 pb-10 pt-4 sm:px-10 lg:px-16 lg:py-12">
                <div class="glass-in is-visible mx-auto w-full max-w-md">
                    {{ $slot }}
                </div>

                <p class="mt-10 text-center text-xs text-ink-500">
                    &copy; {{ now()->year }}, The Fifiawoto Foundation
                </p>
            </main>
        </div>

        @livewireScripts
    </body>
</html>
