<x-layouts::site :title="null">
    {{-- HERO, inset as a rounded card inside the framed shell --}}
    <section class="relative isolate mx-3 mt-3 overflow-hidden rounded-3xl sm:mx-4 sm:mt-4 lg:mx-6 lg:mt-6">
        {{-- Real foundation photograph (school donation outreach in Ghana) --}}
        <img
            src="{{ asset('images/foundation/school-donation.jpg') }}"
            alt="Schoolchildren in Ghana holding exercise books donated by the Fifiawoto Foundation"
            class="absolute inset-0 -z-20 h-full w-full object-cover"
            fetchpriority="high"
        >

        {{-- Brand gradient overlay (matches the WordPress reference: transparent → deep navy, ~80% bottom opacity) --}}
        <div
            class="absolute inset-0 -z-10"
            style="background-image: linear-gradient(178deg, rgba(0, 4, 78, 0) 0%, rgba(0, 4, 78, 0.8) 98%);"
        ></div>

        {{-- Inner content: standard centered container (max-width) --}}
        <div class="mx-auto flex min-h-[480px] max-w-3xl flex-col items-center justify-center px-5 py-20 text-center text-white sm:min-h-[640px] sm:px-6 sm:py-32 lg:min-h-[720px] lg:py-40">
            <h1 class="reveal-down font-serif text-4xl font-bold leading-[1.05] tracking-tight text-white sm:text-6xl lg:text-[80px] lg:leading-none">
                Empowering<br>New Beginnings
            </h1>
            <p class="reveal mt-6 max-w-xl text-sm font-medium leading-relaxed text-white/95 sm:mt-8 sm:text-base lg:text-lg" style="transition-delay: 200ms">
                Transforming lives, one step at a time through compassion, service, and community empowerment.
            </p>
            <div class="reveal mt-8 flex w-full flex-wrap items-center justify-center gap-3 sm:mt-10 sm:w-auto sm:gap-4" style="transition-delay: 400ms">
                <a
                    href="{{ route('donate') }}"
                    class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-6 py-3.5 text-sm font-bold text-gold-600 shadow-lg transition hover:bg-gold-500 hover:text-white sm:w-auto sm:min-w-[160px] sm:px-8 sm:text-base"
                    wire:navigate
                >
                    Donate
                </a>
                <a
                    href="{{ route('contact') }}"
                    class="inline-flex w-full items-center justify-center rounded-2xl bg-gold-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg transition hover:bg-white hover:text-gold-600 sm:w-auto sm:min-w-[160px] sm:px-8 sm:text-base"
                    wire:navigate
                >
                    Get In Touch
                </a>
            </div>
        </div>
    </section>

    {{-- LEGACY / FOUNDATION STORY, 2-column with photo collage --}}
    <section class="relative overflow-hidden bg-white py-20 lg:py-28" aria-labelledby="legacy-heading">
        {{-- Soft cream organic shape behind the collage --}}
        <div class="pointer-events-none absolute -right-24 top-12 -z-10 hidden h-[70%] w-[55%] rounded-l-[200px] bg-cream-100 lg:block"></div>

        <div class="mx-auto grid max-w-[1320px] gap-12 px-4 sm:px-6 lg:grid-cols-[5fr_7fr] lg:items-center lg:gap-16 lg:px-8">
            {{-- LEFT: copy + CTAs --}}
            <div>
                <h2 id="legacy-heading" class="reveal-left font-serif text-4xl font-bold leading-[1.1] text-gold-500 sm:text-5xl lg:text-[52px]">
                    A Legacy of<br>Compassion,<br>A Future of Hope.
                </h2>
                <p class="mt-8 text-sm leading-relaxed text-ink-500">
                    The Dadaa Fifiawoto Nyamadi Foundation was established to preserve and extend the legacy of Madam Dadaa Fifiawoto Nyamadi-Adabla, a woman whose life was dedicated to compassion, faith, and service to others.
                </p>
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    Her generosity and humanitarian spirit inspired the creation of a foundation committed to uplifting women, supporting children, and providing hope for vulnerable communities.
                </p>
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    Today, the foundation operates across multiple countries, working to deliver sustainable programs that empower individuals and strengthen communities.
                </p>
                <div class="mt-10 flex flex-wrap items-center gap-3">
                    <a href="{{ route('donate') }}" class="inline-flex min-w-[140px] items-center justify-center rounded-2xl bg-gold-500 px-7 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900" wire:navigate>
                        Donate
                    </a>
                    <a href="{{ route('volunteer') }}" class="inline-flex min-w-[160px] items-center justify-center rounded-2xl border-2 border-ink-900 px-7 py-3 text-sm font-bold text-ink-900 transition hover:bg-ink-900 hover:text-white" wire:navigate>
                        Volunteer with Us
                    </a>
                </div>
            </div>

            {{-- RIGHT: scattered photo collage --}}
            <div class="relative grid grid-cols-2 gap-4 lg:gap-6" aria-hidden="true">
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
                        class="aspect-[3/4] w-full rounded-[28px] object-cover shadow-md"
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
                        class="aspect-[5/4] w-full rounded-[28px] object-cover shadow-md"
                        loading="lazy"
                    >
                </div>
            </div>
        </div>
    </section>

    {{-- IMPACT, red full-width band with 3 big stats --}}
    <section class="bg-gold-500 py-20 text-white lg:py-24" aria-labelledby="impact-heading">
        <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[6fr_5fr] lg:items-end lg:gap-16">
                <div class="reveal-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-white/75">Our Impact in Numbers</p>
                    <h2 id="impact-heading" class="mt-4 font-serif text-4xl font-bold leading-[1.1] text-white sm:text-5xl lg:text-[52px]">
                        Real impact, measured<br class="hidden lg:block">
                        in lives changed.
                    </h2>
                </div>
                <p class="reveal-right text-sm leading-relaxed text-white/90 sm:text-base" style="transition-delay: 120ms">
                    The foundation measures its progress not just by numbers, but by the real impact seen in the lives it touches and the communities it supports through its programs. Each initiative is a step toward creating meaningful change, improving well-being, and building stronger, more connected communities over time.
                </p>
            </div>

            <div class="mt-16 grid grid-cols-1 gap-10 pt-12 sm:grid-cols-3">
                @foreach ([
                    [520, '+', 'Children Supported'],
                    [18,  '+', 'Communities Reached'],
                    [4,   '+', 'Countries Impacted'],
                ] as $index => [$value, $suffix, $label])
                    <div class="reveal" style="transition-delay: {{ $index * 160 }}ms">
                        <p
                            class="font-serif text-6xl font-bold text-brand-900 sm:text-7xl"
                            data-counter="{{ $value }}"
                            data-suffix="{{ $suffix }}"
                            data-duration="3200"
                        >0{{ $suffix }}</p>
                        <p class="mt-3 text-sm font-medium uppercase tracking-[0.18em] text-white/85">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- PROGRAMS --}}
    <section class="bg-cream-50 py-24" aria-labelledby="programs-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-end">
                <div>
                    <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">What we do</p>
                    <h2 id="programs-heading" class="reveal mt-3 font-serif text-3xl text-ink-900 sm:text-4xl" style="transition-delay: 120ms">Our Programs and Initiatives</h2>
                </div>
                <a href="{{ route('about') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-gold-500" wire:navigate>
                    Read more about our mission
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <x-site.feature-carousel
                noun="program"
                :per-page="4"
                :cta="['Learn more', route('about')]"
                :items="[
                    ['icon' => 'women',     'title' => 'Women Empowerment',                  'copy' => 'Training, mentorship, and financial empowerment for women.'],
                    ['icon' => 'education', 'title' => 'Child Education Support',            'copy' => 'Educational materials, scholarships, and mentorship for children.'],
                    ['icon' => 'support',   'title' => 'Support for Vulnerable Populations', 'copy' => 'Helping widows, orphanages, and individuals facing hardship.'],
                    ['icon' => 'community', 'title' => 'Community Development',              'copy' => 'Outreach programs and infrastructure for stronger communities.'],
                    ['icon' => 'global',    'title' => 'Global Outreach',                    'copy' => 'Humanitarian support across the United States, Ghana, Togo, and Benin.'],
                ]"
            />
        </div>
    </section>

    {{-- JOIN US CTA --}}
    <section class="bg-white py-12" aria-label="Get involved">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-6 rounded-3xl bg-gold-500 px-8 py-10 text-white sm:flex-row sm:px-12">
                <div class="text-center sm:text-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-white/80">Get involved</p>
                    <h2 class="mt-2 font-serif text-3xl sm:text-4xl">Join us, and be part of the impact.</h2>
                </div>
                <a href="{{ route('volunteer') }}" class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-white px-7 py-3.5 text-sm font-bold text-gold-600 shadow-sm transition hover:bg-brand-900 hover:text-white" wire:navigate>
                    Volunteer with us
                </a>
            </div>
        </div>
    </section>

    {{-- MEDIA GALLERY PREVIEW --}}
    <section class="py-20" aria-labelledby="gallery-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Media Gallery</p>
                    <h2 id="gallery-heading" class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl">Our Work in Action</h2>
                    <p class="mt-3 max-w-2xl text-sm text-ink-500">
                        Moments from outreach programs, volunteer initiatives, and community activities organized by the foundation.
                    </p>
                </div>
                <a href="{{ route('media') }}" class="text-sm font-semibold text-brand-700 hover:text-gold-500" wire:navigate>View all →</a>
            </div>

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Community Outreach',  'images/foundation/school-donation.jpg'],
                    ['Education Support',   'images/foundation/outreach-1.jpg'],
                    ['Volunteer Activities','images/foundation/outreach-4.webp'],
                    ['Events',              'images/foundation/outreach-3.webp'],
                ] as $index => [$category, $image])
                    <figure class="reveal group relative aspect-[4/5] overflow-hidden rounded-3xl shadow-sm" style="transition-delay: {{ $index * 100 }}ms">
                        <img
                            src="{{ asset($image) }}"
                            alt="{{ $category }}, Fifiawoto Foundation outreach moment"
                            class="img-fade absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            loading="lazy"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-900/85 via-brand-900/20 to-transparent"></div>
                        <figcaption class="absolute inset-x-0 bottom-0 p-5">
                            <span class="inline-flex rounded-full bg-white/95 px-3 py-1.5 text-xs font-bold text-brand-900 shadow">{{ $category }}</span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- INSTAGRAM SLIDER --}}
    @php
        $instagramUrl = config('social.instagram');
        $highlights = $instagramHighlights ?? collect();

        // Always end up with at least 6 tiles. Use DB posts when present, otherwise
        // foundation imagery linking to the live profile so the slider never looks empty.
        if ($highlights->isNotEmpty()) {
            $slides = $highlights->map(fn ($post) => [
                'permalink' => $post->permalink,
                'thumb' => $post->thumbnail_url ?: null,
                'caption' => $post->caption ?: 'View on Instagram',
                'is_video' => ($post->media_type ?? null) === 'VIDEO',
            ])->values()->all();
        } else {
            $slides = [
                ['permalink' => $instagramUrl, 'thumb' => asset('images/foundation/community-1.jpg'),     'caption' => 'Community gathering in the Volta region',  'is_video' => false],
                ['permalink' => $instagramUrl, 'thumb' => asset('images/foundation/school-donation.jpg'), 'caption' => 'Back-to-school distribution day',          'is_video' => false],
                ['permalink' => $instagramUrl, 'thumb' => asset('images/foundation/outreach-1.jpg'),      'caption' => 'Outreach kickoff with local partners',     'is_video' => false],
                ['permalink' => $instagramUrl, 'thumb' => asset('images/foundation/outreach-2.webp'),     'caption' => 'Mobile health clinic in action',           'is_video' => true],
                ['permalink' => $instagramUrl, 'thumb' => asset('images/foundation/outreach-3.webp'),     'caption' => 'Widows empowerment workshop',              'is_video' => false],
                ['permalink' => $instagramUrl, 'thumb' => asset('images/foundation/community-2.jpg'),     'caption' => 'Greater Accra town-hall',                  'is_video' => false],
                ['permalink' => $instagramUrl, 'thumb' => asset('images/foundation/outreach-4.webp'),     'caption' => 'Volunteers prepping care packs',           'is_video' => false],
                ['permalink' => $instagramUrl, 'thumb' => asset('images/foundation/community-3.jpg'),     'caption' => 'Annual gathering with partners',           'is_video' => false],
            ];
        }
        $slideCount = count($slides);
    @endphp
    <section class="bg-cream-100 py-20" aria-labelledby="instagram-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Instagram</p>
                    <h2 id="instagram-heading" class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl">Follow Our Journey</h2>
                    <p class="mt-2 text-sm text-ink-500">@<a href="{{ $instagramUrl }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 hover:text-gold-500">the_fifiawotofoundation</a> · highlights from the feed</p>
                </div>
                <a href="{{ $instagramUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-brand-900 px-5 py-2.5 text-sm font-semibold text-cream-50 transition hover:bg-gold-500">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2.2c3.2 0 3.6 0 4.8.1 1.2.1 1.8.2 2.2.4.5.2 1 .5 1.4.9.4.4.7.9.9 1.4.2.4.4 1 .4 2.2.1 1.2.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 1.2-.2 1.8-.4 2.2-.2.5-.5 1-.9 1.4-.4.4-.9.7-1.4.9-.4.2-1 .4-2.2.4-1.2.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2-.1-1.8-.2-2.2-.4-.5-.2-1-.5-1.4-.9-.4-.4-.7-.9-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8c.1-1.2.2-1.8.4-2.2.2-.5.5-1 .9-1.4.4-.4.9-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.5-1.3.9-.4.4-.7.8-.9 1.3-.2.4-.3 1-.4 2.1-.1 1.2-.1 1.6-.1 4.7s0 3.5.1 4.7c.1 1.1.2 1.7.4 2.1.2.5.5.9.9 1.3.4.4.8.7 1.3.9.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.5 1.3-.9.4-.4.7-.8.9-1.3.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.5-.9-.9-1.3-.4-.4-.8-.7-1.3-.9-.4-.2-1-.3-2.1-.4-1.2-.1-1.6-.1-4.7-.1Zm0 3.1a5 5 0 1 1 0 9.9 5 5 0 0 1 0-9.9Zm0 1.8a3.2 3.2 0 1 0 0 6.3 3.2 3.2 0 0 0 0-6.3Zm5.1-2a1.2 1.2 0 1 1 0 2.3 1.2 1.2 0 0 1 0-2.3Z"/>
                    </svg>
                    Follow Us on Instagram
                </a>
            </div>

            {{-- Sliding strip --}}
            <div
                x-data="{
                    active: 0,
                    count: {{ $slideCount }},
                    perPage: 4,
                    perPageFor() {
                        if (window.matchMedia('(min-width: 1024px)').matches) return 4;
                        if (window.matchMedia('(min-width: 640px)').matches) return 3;
                        return 2;
                    },
                    stops() { return Math.max(1, this.count - this.perPage + 1); },
                    next() { this.active = (this.active + 1) % this.stops(); },
                    go(i) { this.active = i; },
                    autoplay: null,
                    start() {
                        this.perPage = this.perPageFor();
                        if (this.count > this.perPage) {
                            this.autoplay = setInterval(() => this.next(), 4000);
                        }
                    },
                    stop() { clearInterval(this.autoplay); this.autoplay = null; },
                    handleResize() {
                        const next = this.perPageFor();
                        if (next !== this.perPage) {
                            this.perPage = next;
                            this.active = Math.min(this.active, this.stops() - 1);
                            this.stop();
                            this.start();
                        }
                    }
                }"
                x-init="start(); $watch('active', () => {}); window.addEventListener('resize', () => handleResize())"
                @mouseenter="stop()"
                @mouseleave="start()"
                class="relative mt-10"
                aria-roledescription="carousel"
                aria-label="Instagram highlights"
            >
                <div class="overflow-x-clip py-2">
                    <div
                        class="-mx-2 flex transition-transform duration-700 ease-out"
                        :style="`transform: translateX(calc(-${active} * (100% / ${perPage})))`"
                    >
                        @foreach ($slides as $i => $slide)
                            <div class="w-1/2 shrink-0 px-2 sm:w-1/3 lg:w-1/4">
                                <a
                                    href="{{ $slide['permalink'] }}"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="{{ \Illuminate\Support\Str::limit($slide['caption'], 80) }}"
                                    class="group relative block aspect-square overflow-hidden rounded-2xl bg-cream-200 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                                >
                                    @if ($slide['thumb'])
                                        <img
                                            src="{{ $slide['thumb'] }}"
                                            alt="{{ $slide['caption'] }}"
                                            loading="lazy"
                                            class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.05]"
                                        >
                                    @endif
                                    <span class="absolute inset-0 bg-gradient-to-t from-brand-900/55 via-transparent to-transparent opacity-0 transition group-hover:opacity-100"></span>
                                    @if ($slide['is_video'])
                                        <span class="absolute inset-0 grid place-items-center">
                                            <span class="grid size-12 place-items-center rounded-full bg-white/90 text-brand-900 shadow-lg backdrop-blur transition group-hover:bg-gold-500 group-hover:text-white">
                                                <svg class="size-5 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                            </span>
                                        </span>
                                    @endif
                                    <span class="absolute right-2 top-2 grid size-7 place-items-center rounded-full bg-white/90 text-brand-900 shadow backdrop-blur transition group-hover:bg-gold-500 group-hover:text-white">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 2.2c3.2 0 3.6 0 4.8.1 1.2.1 1.8.2 2.2.4.5.2 1 .5 1.4.9.4.4.7.9.9 1.4.2.4.4 1 .4 2.2.1 1.2.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 1.2-.2 1.8-.4 2.2-.2.5-.5 1-.9 1.4-.4.4-.9.7-1.4.9-.4.2-1 .4-2.2.4-1.2.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2-.1-1.8-.2-2.2-.4-.5-.2-1-.5-1.4-.9-.4-.4-.7-.9-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8c.1-1.2.2-1.8.4-2.2.2-.5.5-1 .9-1.4.4-.4.9-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 5.1a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4Z"/>
                                        </svg>
                                    </span>
                                    {{-- Hover overlay with caption + clear "open on IG" affordance --}}
                                    <span class="pointer-events-none absolute inset-x-2 bottom-2 flex flex-col gap-1.5 opacity-0 transition group-hover:opacity-100">
                                        @if ($slide['caption'])
                                            <span class="line-clamp-2 rounded-md bg-black/65 px-2 py-1 text-[10px] font-medium text-white backdrop-blur">{{ $slide['caption'] }}</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1.5 self-start rounded-md bg-gold-500 px-2 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-white">
                                            View on Instagram
                                            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7v7M21 3 10 14M19 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6"/></svg>
                                        </span>
                                    </span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($slideCount > 4)
                    <div class="mt-6 flex justify-center gap-2" role="tablist">
                        @for ($i = 0; $i < $slideCount - 3; $i++)
                            <button
                                type="button"
                                @click="go({{ $i }})"
                                :class="active === {{ $i }} ? 'w-10 bg-brand-900' : 'w-3 bg-brand-100 hover:bg-brand-300'"
                                class="h-3 rounded-full transition-all"
                                aria-label="Go to slide {{ $i + 1 }}"
                            ></button>
                        @endfor
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- TESTIMONIALS --}}
    <section class="py-20" aria-labelledby="testimonials-heading">
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

            <x-site.testimonial-carousel :testimonials="$testimonials" :per-view="2" />

            <div class="mt-10 flex justify-center">
                <a href="{{ route('testimonials') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-gold-500" wire:navigate>
                    Read more stories
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
        <section class="bg-brand-900 py-20 text-cream-50" aria-labelledby="featured-event-heading">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-400">Upcoming Outreach</p>
                    <h2 id="featured-event-heading" class="mt-3 font-serif text-3xl sm:text-4xl">{{ $featuredEvent->title }}</h2>
                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-cream-200/80">
                        {{ \Illuminate\Support\Str::limit(strip_tags($featuredEvent->description), 220) }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('events.show', $featuredEvent) }}" class="rounded-full border border-cream-200/40 px-5 py-2 text-sm font-medium text-cream-50 transition hover:bg-cream-50 hover:text-brand-900" wire:navigate>Learn More</a>
                        <x-site.event-actions :event="$featuredEvent" variant="dark" />
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-700/40 bg-brand-800/40 p-6 backdrop-blur">
                    @if ($goal > 0)
                        <div class="flex items-end justify-between text-sm">
                            <span class="text-cream-200/80">Raised</span>
                            <span class="font-semibold text-cream-50">${{ number_format($raised / 100) }}</span>
                        </div>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-brand-700/60">
                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-cream-200/70">
                            <span>{{ $percent }}% funded</span>
                            <span>Goal ${{ number_format($goal / 100) }}</span>
                        </div>
                    @endif
                    <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-[0.2em] text-cream-200/60">Date</dt>
                            <dd class="mt-1 text-cream-50">{{ $featuredEvent->starts_at?->format('M j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-[0.2em] text-cream-200/60">Location</dt>
                            <dd class="mt-1 text-cream-50">{{ $featuredEvent->location }}, {{ $featuredEvent->country }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>
    @endif

    {{-- NEWSLETTER --}}
    <livewire:site.newsletter-signup source="home" />
</x-layouts::site>
