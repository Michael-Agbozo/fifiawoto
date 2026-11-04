<x-layouts::site :title="null">
    {{-- HERO: blurred fluted-blue photo panel, headline left, layered 3D photo stack right --}}
    <section class="bg-fluted relative mx-3 mt-3 rounded-3xl text-white sm:mx-4 sm:mt-4 lg:mx-6 lg:mt-6" aria-labelledby="hero-heading">
        <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-16 pt-14 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:gap-10 lg:px-8 lg:pb-24 lg:pt-20">
            {{-- Copy --}}
            <div class="relative z-10">

                <h1 id="hero-heading" class="animate-fade-in font-serif text-[48px] leading-[0.95] tracking-[-0.03em] text-white [text-shadow:0_2px_30px_rgba(2,8,48,0.45)] sm:text-[64px] lg:text-[72px] xl:text-[84px]" style="animation-delay: 120ms">
                    <span class="block font-semibold">{{ __('Empowering') }}</span>
                    <span class="mt-1 block bg-gradient-to-r sm:whitespace-nowrap from-[#ffc9c9] via-[#ffe4e1] to-white bg-clip-text pb-2 pr-2 font-medium italic tracking-[-0.02em] text-transparent [text-shadow:none]">{{ __('New Beginnings') }}</span>
                </h1>

                <p class="animate-fade-in mt-6 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg" style="animation-delay: 220ms">
                    {{ __(\App\Support\SiteSettings::get('hero_text')) }}
                </p>

                <div class="animate-fade-in mt-9 flex flex-col gap-3 sm:flex-row sm:flex-wrap" style="animation-delay: 320ms">
                    <a href="{{ route('donate') }}" class="btn btn-primary btn-on-dark btn-lg" wire:navigate>
                        {{ __('Donate') }}
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('sponsor-a-child') }}" class="btn btn-outline-light btn-lg" wire:navigate>{{ __('Sponsor a Child') }}</a>
                    <a href="{{ route('contact') }}" class="btn btn-lg text-white/80 sm:px-3 underline-offset-4 hover:text-white hover:underline" wire:navigate>{{ __('Get In Touch') }}</a>
                </div>

                <dl class="animate-fade-in mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/15 pt-7" style="animation-delay: 420ms">
                    @foreach ([[$impact['children'], '+', 'Children supported'], [$impact['communities'], '+', 'Communities'], [$impact['countries'], '', 'Countries']] as [$value, $suffix, $label])
                        <div class="flex flex-col justify-between">
                            <dt class="text-xs font-medium text-white/60">{{ __($label) }}</dt>
                            <dd class="mt-1 font-serif text-3xl font-bold text-white sm:text-4xl" data-counter="{{ $value }}" data-suffix="{{ $suffix }}" data-duration="2200">{{ __($value) }}{{ __($suffix) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Layered photo stack --}}
            <div class="relative mx-auto hidden w-full max-w-[560px] [perspective:1400px] sm:block" aria-hidden="true">
                <div data-float class="relative aspect-[1/1.02]">
                    <img
                        src="{{ \App\Support\MediaUrl::for(\App\Support\SiteSettings::get('hero_image_main')) }}"
                        alt=""
                        class="absolute inset-x-[8%] top-[4%] h-[78%] w-[84%] rounded-[32px] object-cover shadow-[0_40px_80px_-30px_rgba(0,0,0,0.75)] ring-1 ring-white/20"
                        fetchpriority="high"
                    >
                    <img
                        src="{{ \App\Support\MediaUrl::for(\App\Support\SiteSettings::get('hero_image_left')) }}"
                        alt=""
                        class="absolute bottom-0 left-0 h-[42%] w-[38%] -rotate-6 rounded-3xl object-cover shadow-[0_30px_60px_-20px_rgba(0,0,0,0.8)] ring-4 ring-[#06104f]"
                    >
                    <img
                        src="{{ \App\Support\MediaUrl::for(\App\Support\SiteSettings::get('hero_image_right')) }}"
                        alt=""
                        class="absolute -right-2 bottom-[8%] h-[36%] w-[32%] rotate-6 rounded-3xl object-cover shadow-[0_30px_60px_-20px_rgba(0,0,0,0.8)] ring-4 ring-[#0b2a8a]"
                    >

                    {{-- Floating glass chips --}}
                    <div class="absolute -left-3 top-[10%] animate-[float-chip_6s_ease-in-out_infinite] rounded-2xl bg-white/10 px-4 py-3 text-left ring-1 ring-white/25 backdrop-blur-md sm:-left-8">
                        <p class="flex items-center gap-2 text-sm font-semibold text-white">
                            <span class="size-2 rounded-full bg-gold-300" aria-hidden="true"></span>
                            {{ __('Volta Region, Ghana') }}
                        </p>
                        <p class="mt-0.5 text-xs text-white/70">{{ __('School bags handed out this term') }}</p>
                    </div>
                    @if ($featuredEvent)
                        <div class="absolute -right-2 top-[44%] max-w-[230px] animate-[float-chip_6s_ease-in-out_-3s_infinite] rounded-2xl bg-white/95 px-4 py-3 text-left shadow-xl sm:-right-6">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gold-500">{{ __('Next outreach') }}</p>
                            <p class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-[#00044e]">{{ $featuredEvent->title }}</p>
                            <p class="mt-1 text-xs text-[#5a5a5a]">{{ $featuredEvent->starts_at?->translatedFormat('j M Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- LEGACY / FOUNDATION STORY, 2-column with photo collage --}}
    <section class="section relative overflow-hidden bg-surface" aria-labelledby="legacy-heading">
        {{-- Soft cream organic shape behind the collage --}}
        <div class="pointer-events-none absolute -right-24 top-12 -z-10 hidden h-[70%] w-[55%] rounded-l-[200px] bg-cream-100 lg:block"></div>

        <div class="mx-auto grid max-w-[1320px] gap-12 px-4 sm:px-6 lg:grid-cols-[5fr_7fr] lg:items-center lg:gap-16 lg:px-8">
            {{-- LEFT: copy + CTAs --}}
            <div>
                <h2 id="legacy-heading" class="section-title text-heading reveal-left">
                    {{ __('A Legacy of') }}<br>{{ __('Compassion,') }}<br>{{ __('A Future of Hope.') }}
                </h2>
                <p class="mt-8 text-sm leading-relaxed text-ink-500">
                    {{ __('The Dadaa Fifiawoto Nyamadi Foundation was established to preserve and extend the legacy of Madam Dadaa Fifiawoto Nyamadi-Adabla, a woman whose life was dedicated to compassion, faith, and service to others.') }}
                </p>
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    {{ __('Her generosity and humanitarian spirit inspired the creation of a foundation committed to uplifting women, supporting children, and providing hope for vulnerable communities.') }}
                </p>
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    {{ __('Today, the foundation operates across multiple countries, working to deliver sustainable programs that empower individuals and strengthen communities.') }}
                </p>
                <div class="mt-10 flex flex-wrap items-center gap-3">
                    <a href="{{ route('donate') }}" class="btn btn-primary" wire:navigate>
                        {{ __('Donate') }}
                    </a>
                    <a href="{{ route('volunteer') }}" class="btn btn-secondary" wire:navigate>
                        {{ __('Volunteer with Us') }}
                    </a>
                </div>
            </div>

            {{-- RIGHT: scattered photo collage --}}
            <div class="relative grid grid-cols-2 gap-4 lg:gap-6" data-float aria-hidden="true">
                <div class="space-y-4 lg:space-y-6 lg:pt-2">
                    <img
                        src="{{ asset('images/foundation/school-donation.jpg') }}"
                        alt=""
                        class="aspect-[5/4] w-full rounded-[28px] object-cover shadow-md"
                        loading="lazy"
                    >
                    <img
                        src="{{ asset('images/foundation/outreach-3.webp') }}"
                        alt=""
                        class="hidden aspect-[3/4] w-full rounded-[28px] object-cover shadow-md sm:block"
                        loading="lazy"
                    >
                </div>
                <div class="space-y-4 lg:space-y-6 lg:pt-20">
                    <img
                        src="{{ asset('images/foundation/outreach-2.webp') }}"
                        alt=""
                        class="aspect-[3/4] w-full rounded-[28px] object-cover shadow-md"
                        loading="lazy"
                    >
                    <img
                        src="{{ asset('images/foundation/outreach-1.jpg') }}"
                        alt=""
                        class="hidden aspect-[5/4] w-full rounded-[28px] object-cover shadow-md sm:block"
                        loading="lazy"
                    >
                </div>
            </div>
        </div>
    </section>

    {{-- IMPACT, stage-light blue band with 3 big stats --}}
    <section class="section bg-stage-light text-white" aria-labelledby="impact-heading">
        <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[6fr_5fr] lg:items-end lg:gap-16">
                <div class="reveal-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-white/75">{{ __('Our Impact in Numbers') }}</p>
                    <h2 id="impact-heading" class="section-title text-white mt-4">
                        {{ __('Real impact, measured') }}<br class="hidden lg:block">
                        {{ __('in lives changed.') }}
                    </h2>
                </div>
                <p class="reveal-right text-sm leading-relaxed text-white/90 sm:text-base" style="transition-delay: 120ms">
                    {{ __('The foundation measures its progress not just by numbers, but by the real impact seen in the lives it touches and the communities it supports through its programs. Each initiative is a step toward creating meaningful change, improving well-being, and building stronger, more connected communities over time.') }}
                </p>
            </div>

            @php
                $impactStats = array_filter([
                    [$impact['children'], '+', 'Children Supported'],
                    [$impact['communities'], '+', 'Communities Reached'],
                    [$impact['countries'], '', 'Countries Impacted'],
                    $impact['volunteer_hours'] > 0 ? [$impact['volunteer_hours'], '+', 'Volunteer Hours'] : null,
                ]);
            @endphp
            <div @class(['mt-12 grid gap-4 pt-4 sm:mt-16 sm:gap-10 sm:pt-12', 'grid-cols-3' => count($impactStats) === 3, 'grid-cols-2 sm:grid-cols-4' => count($impactStats) === 4])>
                @foreach (array_values($impactStats) as $index => [$value, $suffix, $label])
                    <div class="reveal" style="transition-delay: {{ $index * 160 }}ms">
                        <p
                            class="font-serif text-4xl font-bold text-white sm:text-6xl lg:text-7xl"
                            data-counter="{{ $value }}"
                            data-suffix="{{ $suffix }}"
                            data-duration="3200"
                        >0{{ $suffix }}</p>
                        <p class="mt-3 text-[11px] font-medium uppercase tracking-[0.12em] text-white/85 sm:text-sm sm:tracking-[0.18em]">{{ __($label) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- WELCOME, trusted non-profit intro with two ways to help --}}
    <section class="section relative overflow-hidden bg-surface" aria-labelledby="welcome-heading">
        <div class="mx-auto grid max-w-[1320px] gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8">
            <div>
                <p class="eyebrow reveal-down">{{ __('Welcome, let\'s make a difference!') }}</p>
                <h2 id="welcome-heading" class="section-title text-heading reveal mt-3" style="transition-delay: 100ms">
                    {{ __('A Trusted Non-Profit') }}<br class="hidden sm:block"> {{ __('Serving Four Countries') }}
                </h2>
                <p class="reveal mt-6 max-w-xl text-base leading-relaxed text-ink-500" style="transition-delay: 160ms">
                    {{ __('We work with churches, schools and community leaders in the United States, Ghana, Togo and Benin to support women, children and vulnerable people, so every family has the chance of a new beginning.') }}
                </p>

                <div class="reveal mt-8 grid gap-6 border-b border-cream-300 pb-8 sm:grid-cols-2" style="transition-delay: 220ms">
                    <a href="{{ route('donate') }}" class="group flex items-center gap-4" wire:navigate>
                        <span class="grid size-16 shrink-0 rotate-6 place-items-center rounded-[40%] bg-gold-500 text-white shadow-md transition group-hover:rotate-0">
                            <svg class="size-7 -rotate-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 14h2a2 2 0 0 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 16"/><path d="m7 20 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.8-2.8l-4.2 3.9"/><path d="m2 15 6 6"/><path d="M19.5 8.5c.7-.7 1.5-1.6 1.5-2.7A2.7 2.7 0 0 0 16 4a2.7 2.7 0 0 0-5 1.8c0 1.2.8 2 1.5 2.8L16 12Z"/></svg>
                        </span>
                        <span class="font-serif text-xl font-bold leading-snug text-heading group-hover:text-gold-500">{{ __('Be a Hero,') }}<br>{{ __('Give Today') }}</span>
                    </a>
                    <a href="{{ route('sponsor-a-child') }}" class="group flex items-center gap-4" wire:navigate>
                        <span class="grid size-16 shrink-0 -rotate-6 place-items-center rounded-[40%] bg-brand-900 text-white shadow-md transition group-hover:rotate-0">
                            <svg class="size-7 rotate-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="6" r="3"/><path d="M12 9v6"/><path d="M8 13l4-2 4 2"/><path d="M10 21l2-6 2 6"/></svg>
                        </span>
                        <span class="font-serif text-xl font-bold leading-snug text-heading group-hover:text-gold-500">{{ __('Help a Child,') }}<br>{{ __('Become a Sponsor') }}</span>
                    </a>
                </div>

                <ul class="reveal mt-8 space-y-3 text-base text-ink-700" style="transition-delay: 280ms">
                    @foreach ([
                        'School supplies, uniforms and fees for children in need.',
                        'Skills and small-business support for women and mothers.',
                        'Care for widows, orphanages and people living with disabilities.',
                    ] as $point)
                        <li class="flex items-start gap-3">
                            <svg class="mt-1 size-4 shrink-0 text-gold-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-8-5.1-8-11.1A4.6 4.6 0 0 1 12 6.6a4.6 4.6 0 0 1 8 3.3C20 15.9 12 21 12 21z"/></svg>
                            <span>{{ __($point) }}</span>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('donate') }}" class="btn btn-primary btn-lg reveal mt-10" style="transition-delay: 320ms" wire:navigate>
                    {{ __('Support Now') }}
                </a>
            </div>

            <div class="reveal-right relative">
                <div class="absolute -right-6 -top-10 bottom-16 left-24 rounded-tl-[64px] rounded-tr-[32px] bg-cream-100 sm:-right-10" aria-hidden="true"></div>
                <img
                    data-float
                    src="{{ asset('images/foundation/about/empowering-hope.jpg') }}"
                    alt="{{ __('Children at a foundation outreach day') }}"
                    class="relative aspect-[5/6] w-full rounded-[28px] object-cover shadow-xl"
                    loading="lazy"
                >
            </div>
        </div>
    </section>

    {{-- POPULAR CAUSES, fundraising events with live progress --}}
    @if ($popularCauses->isNotEmpty())
        <section class="section bg-surface" aria-labelledby="causes-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    x-data="{
                        count: {{ $popularCauses->count() }},
                        index: 0,
                        per: 3,
                        animate: true,
                        timer: null,
                        get looping() { return this.count > this.per },
                        get active() { return this.index % this.count },
                        measure() { this.per = window.innerWidth >= 1024 ? 3 : (window.innerWidth >= 768 ? 2 : 1) },
                        next() { if (! this.looping) return; this.animate = true; this.index++ },
                        prev() {
                            if (! this.looping) return;
                            if (this.index === 0) {
                                this.animate = false;
                                this.index = this.count;
                                requestAnimationFrame(() => requestAnimationFrame(() => { this.animate = true; this.index-- }));
                            } else { this.animate = true; this.index-- }
                        },
                        go(i) { this.animate = true; this.index = i },
                        settle() { if (this.index >= this.count) { this.animate = false; this.index -= this.count } },
                        start() {
                            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                            this.stop();
                            this.timer = setInterval(() => this.next(), 5000);
                        },
                        stop() { clearInterval(this.timer); this.timer = null },
                    }"
                    x-init="measure(); start()"
                    @resize.window.debounce.150ms="measure(); if (index >= count) { animate = false; index = 0 }"
                    @mouseenter="stop()"
                    @mouseleave="start()"
                    @focusin="stop()"
                    @focusout="start()"
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="{{ __('Popular causes') }}"
                >
                    <div class="flex flex-col items-center gap-6 text-center sm:flex-row sm:items-end sm:justify-between sm:text-left">
                        <div>
                            <p class="eyebrow reveal-down">{{ __('Our causes') }}</p>
                            <h2 id="causes-heading" class="section-title text-heading reveal mt-2" style="transition-delay: 100ms">{{ __('Find Popular Causes') }}</h2>
                        </div>
                        <div class="flex items-center gap-3" x-show="looping" x-cloak>
                            <button type="button" class="btn btn-ghost size-12 p-0" @click="prev()" aria-label="{{ __('Previous cause') }}">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <button type="button" class="btn btn-primary size-12 p-0" @click="next()" aria-label="{{ __('Next cause') }}">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Track: the list is drawn twice so sliding past the last cause flows straight into the first. --}}
                    <div class="reveal -mx-2 mt-12 overflow-hidden px-2 py-4" style="transition-delay: 160ms">
                        <div
                            class="flex gap-6"
                            :class="animate ? 'transition-transform duration-700 ease-[cubic-bezier(0.2,0.8,0.2,1)]' : ''"
                            :style="`transform: translateX(calc(-${index} * (100% + 1.5rem) / ${per}))`"
                            @transitionend.self="settle()"
                        >
                            @foreach ([false, true] as $isCopy)
                                @foreach ($popularCauses as $cause)
                                    <div
                                        class="shrink-0 basis-full md:basis-[calc((100%-1.5rem)/2)] lg:basis-[calc((100%-3rem)/3)]"
                                        @if ($isCopy) x-show="looping" aria-hidden="true" inert @else role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}" @endif
                                    >
                                        @include('site.partials.cause-card', ['cause' => $cause, 'index' => $loop->index])
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex justify-center gap-2" x-show="looping" x-cloak>
                        @foreach ($popularCauses as $cause)
                            <button
                                type="button"
                                class="h-2 rounded-full transition-all duration-300"
                                :class="active === {{ $loop->index }} ? 'w-8 bg-gold-500' : 'w-2 bg-cream-300 hover:bg-ink-500'"
                                @click="go({{ $loop->index }})"
                                aria-label="Show cause {{ $loop->iteration }}"
                            ></button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- PROGRAMS --}}
    <section class="section bg-cream-50" aria-labelledby="programs-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-end">
                <div>
                    <p class="eyebrow reveal-down">{{ __('What we do') }}</p>
                    <h2 id="programs-heading" class="section-title text-heading reveal mt-3" style="transition-delay: 120ms">{{ __('Our Programs and Initiatives') }}</h2>
                </div>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                    <a href="{{ route('about') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-label hover:text-gold-500" wire:navigate>
                        {{ __('Read more about our mission') }}
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('apply-for-support') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gold-500 hover:text-heading" wire:navigate>
                        {{ __('Need support? Apply') }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            {{-- Photo bento: the lead programme spans two rows, four tiles beside it --}}
            <div class="grid gap-4 sm:grid-cols-2 sm:gap-5 lg:auto-rows-[minmax(300px,auto)] lg:grid-cols-4">
                @foreach ([
                    ['Child Education Support',            'Books, uniforms, school fees and mentorship, so children can stay in class and keep learning.', 'images/foundation/outreach-1.jpg',               'lg:col-span-2 lg:row-span-2'],
                    ['Women Empowerment',                  'Training, mentorship and small-business support that help mothers provide for their families.', 'images/foundation/community-2.jpg',              ''],
                    ['Support for Vulnerable Populations', 'Care for widows, orphanages and people facing hardship, met with dignity.',                     'images/foundation/about/serving-with-heart.jpg', ''],
                    ['Community Development',              'Outreach and infrastructure chosen with communities, built to last.',                           'images/foundation/community-1.jpg',              ''],
                    ['Global Outreach',                    'Humanitarian support across the United States, Ghana, Togo and Benin.',                         'images/foundation/about/team-group.jpg',         ''],
                ] as $index => [$title, $copy, $image, $span])
                    <a
                        href="{{ route('about') }}"
                        class="reveal-3d tilt group relative isolate flex min-h-[220px] flex-col justify-end overflow-hidden rounded-3xl p-6 sm:min-h-[300px] text-white shadow-[0_24px_50px_-30px_rgba(0,4,78,0.55)] sm:p-7 {{ $span }} {{ $index === 0 ? 'sm:col-span-2 sm:min-h-[420px]' : '' }}"
                        style="transition-delay: {{ $index * 90 }}ms"
                        wire:navigate
                    >
                        <img src="{{ asset($image) }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover transition duration-700 group-hover:scale-110" loading="lazy">
                        <span class="absolute inset-0 -z-10 bg-gradient-to-t from-[#00044e]/95 via-[#00044e]/45 to-transparent transition-opacity duration-500 group-hover:from-[#00044e]" aria-hidden="true"></span>

                        <span class="font-mono text-xs font-semibold tracking-[0.2em] text-white/70">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }} / 05</span>
                        <h3 class="mt-2 font-serif font-bold leading-tight text-white {{ $index === 0 ? 'text-3xl sm:text-4xl lg:text-5xl' : 'text-xl xl:text-2xl' }}">{{ __($title) }}</h3>
                        <p class="mt-3 max-w-md text-sm leading-relaxed text-white/80 {{ $index === 0 ? 'sm:text-base' : 'line-clamp-2' }}">{{ __($copy) }}</p>
                        <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-white">
                            {{ __('Learn more') }}
                            <span class="grid size-8 place-items-center rounded-full bg-white/15 ring-1 ring-white/30 backdrop-blur transition group-hover:translate-x-1 group-hover:bg-gold-500 group-hover:ring-gold-500" aria-hidden="true">→</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- JOIN US CTA --}}
    <section class="bg-surface py-12" aria-label="{{ __('Get involved') }}">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-6 rounded-3xl bg-gold-500 px-8 py-10 text-white sm:flex-row sm:px-12 dark:bg-brand-900">
                <div class="text-center sm:text-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-white/80">{{ __('Get involved') }}</p>
                    <h2 class="section-title mt-2">{{ __('Join us, and be part of the impact.') }}</h2>
                </div>
                <a href="{{ route('volunteer') }}" class="btn btn-light btn-lg shrink-0" wire:navigate>
                    {{ __('Volunteer with us') }}
                </a>
            </div>
        </div>
    </section>

    {{-- TESTIMONIALS --}}
    <section class="section" aria-labelledby="testimonials-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @php
                $fallbackTestimonials = collect([
                    (object) ['author_name' => 'A volunteer', 'author_role' => 'Volunteer',         'quote' => 'Being part of the foundation has shown me how compassion and teamwork can truly transform communities.', 'photo_path' => null],
                    (object) ['author_name' => 'A neighbour', 'author_role' => 'Community Member', 'quote' => 'The outreach program brought hope to our village. It showed us that people care.', 'photo_path' => null],
                    (object) ['author_name' => 'A parent',    'author_role' => 'Beneficiary',      'quote' => 'The support provided by the foundation helped my children stay in school during difficult times.', 'photo_path' => null],
                ]);

                $testimonials = ($featuredTestimonials ?? collect())->isNotEmpty()
                    ? $featuredTestimonials
                    : $fallbackTestimonials;
            @endphp

            <x-site.testimonial-carousel :testimonials="$testimonials" :per-view="3" />

            <div class="mt-10 flex justify-center">
                <a href="{{ route('testimonials') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-label hover:text-gold-500" wire:navigate>
                    {{ __('Read more stories') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    {{-- FEATURED EVENT --}}
    @if ($featuredEvent)
        @php
            $raised = $featuredEvent->raisedCents();
            $goal = (int) ($featuredEvent->goal_cents ?? 0);
            $percent = $featuredEvent->progressPercent();
        @endphp
        <section class="section bg-brand-900 text-paper" aria-labelledby="featured-event-heading">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-400">{{ __('Upcoming Outreach') }}</p>
                    <h2 id="featured-event-heading" class="section-title mt-3">{{ $featuredEvent->title }}</h2>
                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-paper-dim/80">
                        {{ \Illuminate\Support\Str::limit(strip_tags($featuredEvent->description), 220) }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('events.show', $featuredEvent) }}" class="rounded-full border border-cream-200/40 px-5 py-2 text-sm font-medium text-paper transition hover:bg-cream-50 hover:text-heading" wire:navigate>{{ __('Learn More') }}</a>
                        <x-site.event-actions :event="$featuredEvent" variant="dark" />
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-700/40 bg-brand-800/40 p-6 backdrop-blur">
                    @if ($goal > 0)
                        <div class="flex items-end justify-between text-sm">
                            <span class="text-paper-dim/80">{{ __('Raised') }}</span>
                            <span class="font-semibold text-paper">{{ \App\Support\Money::fromCents((int) $raised) }}</span>
                        </div>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-brand-700/60">
                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-paper-dim/70">
                            <span>{{ __(':v1% funded', ['v1' => $percent]) }}</span>
                            <span>{{ __('Goal :v1', ['v1' => \App\Support\Money::fromCents((int) $goal)]) }}</span>
                        </div>
                    @endif
                    <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-[0.2em] text-paper-dim/60">{{ __('Date') }}</dt>
                            <dd class="mt-1 text-paper">{{ $featuredEvent->starts_at?->translatedFormat('M j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-[0.2em] text-paper-dim/60">{{ __('Location') }}</dt>
                            <dd class="mt-1 text-paper">{{ $featuredEvent->location }}, {{ $featuredEvent->country }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>
    @endif

    {{-- NEWSLETTER --}}
    <livewire:site.newsletter-signup source="home" />

    {{-- SPONSORSHIP CTA --}}
    <section class="bg-surface pb-12" aria-labelledby="sponsor-cta-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-start justify-between gap-6 rounded-3xl bg-brand-900 px-7 py-10 text-white sm:flex-row sm:items-center sm:px-12 sm:py-12">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gold-300">{{ __('Help hope grow') }}</p>
                    <h2 id="sponsor-cta-heading" class="section-title mt-3">{{ __('A connection. A brighter tomorrow.') }}</h2>
                    <p class="mt-4 max-w-xl text-white/80">{{ __('Explore child sponsorship and talk with our team about making a lasting difference.') }}</p>
                </div>
                <a href="{{ route('sponsor-a-child') }}" class="btn btn-primary shrink-0" wire:navigate>{{ __('Sponsor a Child') }} <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>
</x-layouts::site>
