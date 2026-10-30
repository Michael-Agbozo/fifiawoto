@php
    $socials = [
        'Instagram' => config('social.instagram') ?: 'https://instagram.com',
        'Facebook' => config('social.facebook') ?: 'https://facebook.com',
        'YouTube' => config('social.youtube') ?: 'https://youtube.com',
    ];

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
            ['Sponsor a Child', route('sponsor-a-child')],
            ['Volunteer', route('volunteer')],
            ['Apply for Support', route('apply-for-support')],
            ['Contact Us', route('contact')],
        ],
    ];

    $countries = ['Ghana', 'Togo', 'Benin', 'United States'];

    $legal = [
        ['Privacy Policy', route('legal.privacy')],
        ['Terms of Service', route('legal.terms')],
        ['Cookie Policy', route('legal.cookies')],
        ['Disclaimer', route('legal.disclaimer')],
    ];
@endphp

{{-- Navy footer: a feature surface, so it reads the same in both themes. --}}
<footer class="relative isolate mx-3 mb-3 overflow-hidden rounded-3xl bg-brand-900 text-white sm:mx-4 sm:mb-4 lg:mx-6 lg:mb-6">
    {{-- Soft stage glow behind the newsletter --}}
    <div class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-[420px] w-[900px] -translate-x-1/2 rounded-full bg-[radial-gradient(closest-side,rgba(86,156,255,0.35),transparent)]" aria-hidden="true"></div>

    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        {{-- Main columns --}}
        <div class="grid gap-12 py-14 md:grid-cols-2 lg:grid-cols-12 lg:gap-10 lg:py-16">
            {{-- Legacy card --}}
            <div class="lg:col-span-4">
                <a href="{{ route('home') }}" class="inline-block" wire:navigate>
                    <img src="{{ asset('images/logos/dff-sticky.svg') }}" alt="Dadaa Fifiawoto Nyamadi Foundation" class="h-14 w-auto">
                </a>
                <p class="mt-6 max-w-sm text-sm leading-relaxed text-white/70">
                    The Fifiawoto Foundation works to empower communities through compassion, service, and sustainable development initiatives across the United States, Ghana, Togo, and Benin.
                </p>
                <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-4">
                    <a href="mailto:hello@fifiawoto.org" class="group inline-flex items-center gap-2 text-sm font-semibold text-white">
                        <span class="grid size-10 place-items-center rounded-full bg-white/10 ring-1 ring-white/15 transition group-hover:bg-gold-500 group-hover:ring-gold-500" aria-hidden="true">
                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        </span>
                        hello@fifiawoto.org
                    </a>
                    <div class="flex items-center gap-2">
                        @foreach ($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full bg-white/10 text-white ring-1 ring-white/15 transition hover:-translate-y-0.5 hover:bg-gold-500 hover:ring-gold-500" aria-label="{{ $network }}">
                                <svg class="size-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $socialIcons[$network] !!}</svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Link columns --}}
            @foreach ($columns as $heading => $links)
                <nav class="lg:col-span-2" aria-label="{{ $heading }}">
                    <h3 class="eyebrow eyebrow-light">{{ $heading }}</h3>
                    <ul class="mt-5 space-y-3">
                        @foreach ($links as [$label, $url])
                            <li>
                                <a href="{{ $url }}" class="group inline-flex items-center gap-2 text-[15px] text-white/75 transition hover:text-white" wire:navigate>
                                    <span class="h-px w-0 bg-gold-300 transition-all duration-300 group-hover:w-3" aria-hidden="true"></span>
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach

            {{-- Where we work + give --}}
            <div class="md:col-span-2 lg:col-span-4">
                <h3 class="eyebrow eyebrow-light">Where we work</h3>
                <ul class="mt-5 grid grid-cols-2 gap-3">
                    @foreach ($countries as $country)
                        <li class="flex items-center gap-2 rounded-2xl bg-white/5 px-4 py-3 font-serif text-lg font-bold text-white ring-1 ring-white/10">
                            <span class="size-2 rounded-full bg-gold-300 shadow-[0_0_10px_rgba(255,176,176,0.8)]" aria-hidden="true"></span>
                            {{ $country }}
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('donate') }}" class="btn btn-primary btn-on-dark mt-6 w-full" wire:navigate>
                    Donate to our work
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Signature wordmark --}}
    <div class="pointer-events-none select-none overflow-hidden px-5 sm:px-6 lg:px-8" aria-hidden="true">
        <p class="mx-auto max-w-7xl translate-y-[18%] text-center font-serif text-[clamp(64px,17vw,250px)] font-bold leading-none tracking-tight text-transparent [-webkit-text-stroke:1px_rgba(255,255,255,0.14)]">Fifiawoto</p>
    </div>

    {{-- Bottom bar --}}
    <div class="relative border-t border-white/10 bg-brand-900/80 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-6 text-xs text-white/60 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <p>&copy; {{ now()->year }} Dadaa Fifiawoto Nyamadi Foundation. All rights reserved.</p>
            <nav class="flex flex-wrap gap-x-5 gap-y-2" aria-label="Legal">
                @foreach ($legal as [$label, $url])
                    <a href="{{ $url }}" class="hover:text-white" wire:navigate>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </div>
</footer>
