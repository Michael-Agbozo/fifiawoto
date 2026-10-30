@php
    $socials = array_filter([
        'Instagram' => config('social.instagram'),
        'Facebook' => config('social.facebook'),
        'YouTube' => config('social.youtube'),
    ]);

    $socialIcons = [
        'Instagram' => '<path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zm0 1.92c-3.15 0-3.51.01-4.74.07-1.04.05-1.61.22-1.98.36-.5.2-.85.43-1.23.8-.37.38-.6.73-.8 1.23-.14.37-.31.94-.36 1.98-.06 1.23-.07 1.59-.07 4.74s.01 3.51.07 4.74c.05 1.04.22 1.61.36 1.98.2.5.43.85.8 1.23.38.37.73.6 1.23.8.37.14.94.31 1.98.36 1.23.06 1.59.07 4.74.07s3.51-.01 4.74-.07c1.04-.05 1.61-.22 1.98-.36.5-.2.85-.43 1.23-.8.37-.38.6-.73.8-1.23.14-.37.31-.94.36-1.98.06-1.23.07-1.59.07-4.74s-.01-3.51-.07-4.74c-.05-1.04-.22-1.61-.36-1.98-.2-.5-.43-.85-.8-1.23-.38-.37-.73-.6-1.23-.8-.37-.14-.94-.31-1.98-.36-1.23-.06-1.59-.07-4.74-.07zm0 3.27a4.65 4.65 0 1 1 0 9.3 4.65 4.65 0 0 1 0-9.3zm0 1.92a2.73 2.73 0 1 0 0 5.46 2.73 2.73 0 0 0 0-5.46zm5.9-2.13a1.09 1.09 0 1 1-2.18 0 1.09 1.09 0 0 1 2.18 0z"/>',
        'Facebook' => '<path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.84c0-2.52 1.49-3.92 3.78-3.92 1.1 0 2.24.2 2.24.2v2.47h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.45 2.91h-2.33V22c4.78-.76 8.43-4.92 8.43-9.94z"/>',
        'YouTube' => '<path d="M23.5 6.2a3 3 0 0 0-2.12-2.12C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.38.58A3 3 0 0 0 .5 6.2C0 8.08 0 12 0 12s0 3.92.5 5.8a3 3 0 0 0 2.12 2.12C4.5 20.5 12 20.5 12 20.5s7.5 0 9.38-.58a3 3 0 0 0 2.12-2.12C24 15.92 24 12 24 12s0-3.92-.5-5.8zM9.75 15.5v-7l6.5 3.5-6.5 3.5z"/>',
    ];

    $columns = [
        'Explore' => [
            ['Home', route('home')],
            ['About Us', route('about')],
            ['Events', route('events.index')],
            ['Stories of Impact', route('testimonials')],
            ['Media Gallery', route('media')],
        ],
        'Get Involved' => [
            ['Donate', route('donate')],
            ['Sponsor a Child', route('sponsor-a-child')],
            ['Volunteer', route('volunteer')],
            ['Apply for Support', route('apply-for-support')],
            ['Contact', route('contact')],
        ],
    ];

    $legal = [
        ['Privacy Policy', route('legal.privacy')],
        ['Terms of Service', route('legal.terms')],
        ['Cookie Policy', route('legal.cookies')],
        ['Disclaimer', route('legal.disclaimer')],
    ];
@endphp

{{-- Navy footer: a feature surface, so it reads the same in both themes. --}}
<footer class="mx-3 mb-3 overflow-hidden rounded-3xl bg-brand-900 text-white sm:mx-4 sm:mb-4 lg:mx-6 lg:mb-6">
    {{-- Call to action --}}
    <div class="bg-stage-light">
        <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-5 py-12 sm:px-6 md:flex-row md:items-center lg:px-8">
            <div>
                <p class="eyebrow eyebrow-light">Make a difference today</p>
                <h2 class="section-title mt-3 text-white">Every gift begins a new chapter.</h2>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('donate') }}" class="btn btn-primary btn-on-dark btn-lg" wire:navigate>Donate</a>
                <a href="{{ route('sponsor-a-child') }}" class="btn btn-outline-light btn-lg" wire:navigate>Sponsor a Child</a>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-5 pb-10 pt-14 sm:px-6 lg:px-8">
        <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.5fr]">
            {{-- Brand --}}
            <div>
                <a href="{{ route('home') }}" class="inline-block" wire:navigate>
                    <img src="{{ asset('images/logos/dff-sticky.svg') }}" alt="Dadaa Fifiawoto Nyamadi Foundation" class="h-14 w-auto">
                </a>
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-white/70">
                    Carrying on the legacy of Madam Dadaa Fifiawoto Nyamadi-Adabla by supporting women, children and vulnerable people across the United States, Ghana, Togo and Benin.
                </p>
                <p class="mt-5 text-sm text-white/70">
                    <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-white/50">Email us</span>
                    <a href="mailto:hello@fifiawoto.org" class="mt-1 inline-block font-semibold text-white hover:text-gold-300">hello@fifiawoto.org</a>
                </p>
                @if ($socials)
                    <div class="mt-6 flex items-center gap-3">
                        @foreach ($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full bg-white/10 text-white ring-1 ring-white/15 transition hover:-translate-y-0.5 hover:bg-gold-500 hover:ring-gold-500" aria-label="{{ $network }}">
                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $socialIcons[$network] !!}</svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Link columns --}}
            @foreach ($columns as $heading => $links)
                <nav aria-label="{{ $heading }}">
                    <h3 class="eyebrow eyebrow-light">{{ $heading }}</h3>
                    <ul class="mt-5 space-y-3 text-sm">
                        @foreach ($links as [$label, $url])
                            <li>
                                <a href="{{ $url }}" class="group inline-flex items-center gap-2 text-white/75 transition hover:text-white" wire:navigate>
                                    <span class="h-px w-0 bg-gold-300 transition-all duration-300 group-hover:w-3" aria-hidden="true"></span>
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach

            {{-- Newsletter --}}
            <div>
                <h3 class="eyebrow eyebrow-light">Stay connected</h3>
                <p class="mt-5 text-sm leading-relaxed text-white/70">News from the field, upcoming events and ways to help, a few times a year.</p>
                <div class="mt-4 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                    <livewire:site.newsletter-signup source="footer" :compact="true" />
                </div>
            </div>
        </div>

        <div class="mt-14 flex flex-col gap-4 border-t border-white/10 pt-6 text-xs text-white/60 lg:flex-row lg:items-center lg:justify-between">
            <p>&copy; {{ now()->year }} Dadaa Fifiawoto Nyamadi Foundation. All rights reserved.</p>
            <nav class="flex flex-wrap gap-x-5 gap-y-2" aria-label="Legal">
                @foreach ($legal as [$label, $url])
                    <a href="{{ $url }}" class="hover:text-white" wire:navigate>{{ $label }}</a>
                @endforeach
            </nav>
            <p>United States · Ghana · Togo · Benin</p>
        </div>
    </div>
</footer>
