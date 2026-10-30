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
                    class="btn btn-light btn-lg w-full sm:w-auto"
                    wire:navigate
                >
                    Donate
                </a>
                <a
                    href="{{ route('contact') }}"
                    class="btn btn-primary btn-on-dark btn-lg w-full sm:w-auto"
                    wire:navigate
                >
                    Get In Touch
                </a>
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
                    <a href="{{ route('donate') }}" class="btn btn-primary" wire:navigate>
                        Donate
                    </a>
                    <a href="{{ route('volunteer') }}" class="btn btn-secondary" wire:navigate>
                        Volunteer with Us
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

    {{-- IMPACT, stage-light blue band with 3 big stats --}}
    <section class="section bg-stage-light text-white" aria-labelledby="impact-heading">
        <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[6fr_5fr] lg:items-end lg:gap-16">
                <div class="reveal-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-white/75">Our Impact in Numbers</p>
                    <h2 id="impact-heading" class="section-title text-white mt-4">
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
                            class="font-serif text-6xl font-bold text-white sm:text-7xl"
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

    {{-- WELCOME, trusted non-profit intro with two ways to help --}}
    <section class="section relative overflow-hidden bg-surface" aria-labelledby="welcome-heading">
        <div class="mx-auto grid max-w-[1320px] gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8">
            <div>
                <p class="eyebrow reveal-down">Welcome, let's make a difference!</p>
                <h2 id="welcome-heading" class="section-title text-heading reveal mt-3" style="transition-delay: 100ms">
                    A Trusted Non-Profit<br class="hidden sm:block"> Serving Four Countries
                </h2>
                <p class="reveal mt-6 max-w-xl text-base leading-relaxed text-ink-500" style="transition-delay: 160ms">
                    We work with churches, schools and community leaders in the United States, Ghana, Togo and Benin to support women, children and vulnerable people, so every family has the chance of a new beginning.
                </p>

                <div class="reveal mt-8 grid gap-6 border-b border-cream-300 pb-8 sm:grid-cols-2" style="transition-delay: 220ms">
                    <a href="{{ route('donate') }}" class="group flex items-center gap-4" wire:navigate>
                        <span class="grid size-16 shrink-0 rotate-6 place-items-center rounded-[40%] bg-gold-500 text-white shadow-md transition group-hover:rotate-0">
                            <svg class="size-7 -rotate-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 14h2a2 2 0 0 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 16"/><path d="m7 20 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.8-2.8l-4.2 3.9"/><path d="m2 15 6 6"/><path d="M19.5 8.5c.7-.7 1.5-1.6 1.5-2.7A2.7 2.7 0 0 0 16 4a2.7 2.7 0 0 0-5 1.8c0 1.2.8 2 1.5 2.8L16 12Z"/></svg>
                        </span>
                        <span class="font-serif text-xl font-bold leading-snug text-heading group-hover:text-gold-500">Be a Hero,<br>Give Today</span>
                    </a>
                    <a href="{{ route('sponsor-a-child') }}" class="group flex items-center gap-4" wire:navigate>
                        <span class="grid size-16 shrink-0 -rotate-6 place-items-center rounded-[40%] bg-brand-900 text-white shadow-md transition group-hover:rotate-0">
                            <svg class="size-7 rotate-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="6" r="3"/><path d="M12 9v6"/><path d="M8 13l4-2 4 2"/><path d="M10 21l2-6 2 6"/></svg>
                        </span>
                        <span class="font-serif text-xl font-bold leading-snug text-heading group-hover:text-gold-500">Help a Child,<br>Become a Sponsor</span>
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
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('donate') }}" class="btn btn-primary btn-lg reveal mt-10" style="transition-delay: 320ms" wire:navigate>
                    Support Now
                </a>
            </div>

            <div class="reveal-right relative">
                <div class="absolute -right-6 -top-10 bottom-16 left-24 rounded-tl-[64px] rounded-tr-[32px] bg-cream-100 sm:-right-10" aria-hidden="true"></div>
                <img
                    data-float
                    src="{{ asset('images/foundation/about/empowering-hope.jpg') }}"
                    alt="Children at a foundation outreach day"
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
                <div class="text-center">
                    <p class="eyebrow reveal-down">Our causes</p>
                    <h2 id="causes-heading" class="section-title text-heading reveal mt-2" style="transition-delay: 100ms">Find Popular Causes</h2>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($popularCauses as $cause)
                        @php
                            $raised = (int) ($cause->raised_cents ?? 0);
                            $goal = (int) $cause->goal_cents;
                            $percent = min(100, (int) round($raised / $goal * 100));
                            $fallbacks = ['images/foundation/school-donation.jpg', 'images/foundation/outreach-1.jpg', 'images/foundation/outreach-4.webp'];
                            $causeImage = \App\Support\MediaUrl::for($cause->hero_image_path ?: $fallbacks[$loop->index % count($fallbacks)]);
                        @endphp
                        <article class="reveal-3d tilt group flex flex-col overflow-hidden rounded-2xl border border-cream-300 bg-surface shadow-sm hover:shadow-lg" style="transition-delay: {{ $loop->index * 100 }}ms">
                            <div class="relative">
                                <div class="aspect-[4/3] overflow-hidden">
                                    <img src="{{ $causeImage }}" alt="{{ $cause->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                </div>
                                @if ($cause->country)
                                    <span class="absolute -bottom-3 left-6 rounded-md bg-brand-900 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">{{ $cause->country }}</span>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col p-6 pt-7">
                                <h3 class="font-serif text-2xl font-bold leading-snug text-heading">
                                    <a href="{{ route('events.show', $cause) }}" class="hover:text-gold-500" wire:navigate>{{ $cause->title }}</a>
                                </h3>
                                <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-ink-500">{{ \Illuminate\Support\Str::limit(strip_tags((string) $cause->description), 120) }}</p>

                                <div class="mt-auto pt-6">
                                    <div class="relative h-1.5 rounded-full bg-cream-300" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $percent }}% of goal raised">
                                        <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <div class="mt-3 flex justify-between gap-3 text-sm text-ink-500">
                                        <span>Raised: <b class="font-semibold text-heading">{{ \App\Support\Money::fromCents($raised) }}</b></span>
                                        <span>{{ $percent }}% of <b class="font-semibold text-heading">{{ \App\Support\Money::fromCents($goal) }}</b></span>
                                    </div>
                                    <a href="{{ route('events.show', $cause) }}" class="btn btn-primary mt-5 w-full" wire:navigate>View Cause</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- PROGRAMS --}}
    <section class="section bg-cream-50" aria-labelledby="programs-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-end">
                <div>
                    <p class="eyebrow reveal-down">What we do</p>
                    <h2 id="programs-heading" class="section-title text-heading reveal mt-3" style="transition-delay: 120ms">Our Programs and Initiatives</h2>
                </div>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                    <a href="{{ route('about') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-label hover:text-gold-500" wire:navigate>
                        Read more about our mission
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('apply-for-support') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gold-500 hover:text-heading" wire:navigate>
                        Need support? Apply
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            {{-- Photo bento: the lead programme spans two rows, four tiles beside it --}}
            <div class="grid gap-5 sm:grid-cols-2 lg:auto-rows-[300px] lg:grid-cols-4">
                @foreach ([
                    ['Child Education Support',            'Books, uniforms, school fees and mentorship, so children can stay in class and keep learning.', 'images/foundation/outreach-1.jpg',               'lg:col-span-2 lg:row-span-2'],
                    ['Women Empowerment',                  'Training, mentorship and small-business support that help mothers provide for their families.', 'images/foundation/community-2.jpg',              ''],
                    ['Support for Vulnerable Populations', 'Care for widows, orphanages and people facing hardship, met with dignity.',                     'images/foundation/about/serving-with-heart.jpg', ''],
                    ['Community Development',              'Outreach and infrastructure chosen with communities, built to last.',                           'images/foundation/community-1.jpg',              ''],
                    ['Global Outreach',                    'Humanitarian support across the United States, Ghana, Togo and Benin.',                         'images/foundation/about/team-group.jpg',         ''],
                ] as $index => [$title, $copy, $image, $span])
                    <a
                        href="{{ route('about') }}"
                        class="reveal-3d tilt group relative isolate flex min-h-[300px] flex-col justify-end overflow-hidden rounded-3xl p-6 text-white shadow-[0_24px_50px_-30px_rgba(0,4,78,0.55)] sm:p-7 {{ $span }} {{ $index === 0 ? 'sm:col-span-2 sm:min-h-[420px]' : '' }}"
                        style="transition-delay: {{ $index * 90 }}ms"
                        wire:navigate
                    >
                        <img src="{{ asset($image) }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover transition duration-700 group-hover:scale-110" loading="lazy">
                        <span class="absolute inset-0 -z-10 bg-gradient-to-t from-[#00044e]/95 via-[#00044e]/45 to-transparent transition-opacity duration-500 group-hover:from-[#00044e]" aria-hidden="true"></span>

                        <span class="font-mono text-xs font-semibold tracking-[0.2em] text-white/70">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }} / 05</span>
                        <h3 class="mt-2 font-serif font-bold leading-tight text-white {{ $index === 0 ? 'text-3xl sm:text-4xl lg:text-5xl' : 'text-2xl' }}">{{ $title }}</h3>
                        <p class="mt-3 max-w-md text-sm leading-relaxed text-white/80 {{ $index === 0 ? 'sm:text-base' : 'line-clamp-2' }}">{{ $copy }}</p>
                        <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-white">
                            Learn more
                            <span class="grid size-8 place-items-center rounded-full bg-white/15 ring-1 ring-white/30 backdrop-blur transition group-hover:translate-x-1 group-hover:bg-gold-500 group-hover:ring-gold-500" aria-hidden="true">→</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- JOIN US CTA --}}
    <section class="bg-surface py-12" aria-label="Get involved">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-6 rounded-3xl bg-gold-500 px-8 py-10 text-white sm:flex-row sm:px-12 dark:bg-brand-900">
                <div class="text-center sm:text-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-white/80">Get involved</p>
                    <h2 class="section-title mt-2">Join us, and be part of the impact.</h2>
                </div>
                <a href="{{ route('volunteer') }}" class="btn btn-light btn-lg shrink-0" wire:navigate>
                    Volunteer with us
                </a>
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
                'thumb' => $post->previewImageUrl(),
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
    <section class="section bg-cream-100" aria-labelledby="instagram-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">Instagram</p>
                    <h2 id="instagram-heading" class="section-title text-heading mt-3">Follow Our Journey</h2>
                    <p class="mt-2 text-sm text-ink-500">@<a href="{{ $instagramUrl }}" target="_blank" rel="noopener" class="font-semibold text-label hover:text-gold-500">the_fifiawotofoundation</a> · highlights from the feed</p>
                </div>
                <a href="{{ $instagramUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-brand-900 px-5 py-2.5 text-sm font-semibold text-paper transition hover:bg-gold-500">
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
        <section class="section bg-brand-900 text-paper" aria-labelledby="featured-event-heading">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-400">Upcoming Outreach</p>
                    <h2 id="featured-event-heading" class="section-title mt-3">{{ $featuredEvent->title }}</h2>
                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-paper-dim/80">
                        {{ \Illuminate\Support\Str::limit(strip_tags($featuredEvent->description), 220) }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('events.show', $featuredEvent) }}" class="rounded-full border border-cream-200/40 px-5 py-2 text-sm font-medium text-paper transition hover:bg-cream-50 hover:text-heading" wire:navigate>Learn More</a>
                        <x-site.event-actions :event="$featuredEvent" variant="dark" />
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-700/40 bg-brand-800/40 p-6 backdrop-blur">
                    @if ($goal > 0)
                        <div class="flex items-end justify-between text-sm">
                            <span class="text-paper-dim/80">Raised</span>
                            <span class="font-semibold text-paper">{{ \App\Support\Money::fromCents((int) $raised) }}</span>
                        </div>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-brand-700/60">
                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-paper-dim/70">
                            <span>{{ $percent }}% funded</span>
                            <span>Goal {{ \App\Support\Money::fromCents((int) $goal) }}</span>
                        </div>
                    @endif
                    <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-[0.2em] text-paper-dim/60">Date</dt>
                            <dd class="mt-1 text-paper">{{ $featuredEvent->starts_at?->format('M j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-[0.2em] text-paper-dim/60">Location</dt>
                            <dd class="mt-1 text-paper">{{ $featuredEvent->location }}, {{ $featuredEvent->country }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>
    @endif

    {{-- NEWSLETTER --}}
    <livewire:site.newsletter-signup source="home" />
<section class="mx-6 mb-12 rounded-3xl bg-brand-900 px-7 py-12 text-white sm:px-12"><div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-8"><div><p class="text-xs font-semibold uppercase tracking-widest text-gold-300">Help hope grow</p><h2 class="section-title mt-3">A connection. A brighter tomorrow.</h2><p class="mt-4 max-w-xl text-white/80">Explore child sponsorship and talk with our team about making a lasting difference.</p></div><a href="{{ route('sponsor-a-child') }}" class="btn btn-primary">Sponsor a Child →</a></div></section>
</x-layouts::site>
