<x-layouts::site title="About">
    <x-site.page-hero
        title="About Us"
        breadcrumb="About"
        image="images/foundation/about/team-group.jpg"
        alt="The Fifiawoto Foundation team and supporters in matching foundation t-shirts"
    />

    {{-- OUR LEGACY · Serving with Heart --}}
    <section class="py-20 lg:py-24" aria-labelledby="legacy-heading">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:items-center lg:gap-16 lg:px-8">
            <div class="reveal-left">
                <img
                    src="{{ asset('images/foundation/about/serving-with-heart.jpg') }}"
                    alt="Children holding up new school bags donated through the foundation"
                    class="img-fade aspect-[4/3] w-full rounded-3xl object-cover shadow-[0_24px_60px_-30px_rgba(0,4,78,0.35)]"
                    loading="lazy"
                >
            </div>
            <div class="reveal-right" style="transition-delay: 160ms">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Our Legacy</p>
                <h2 id="legacy-heading" class="mt-3 font-serif text-4xl font-bold leading-[1.1] text-gold-500 sm:text-5xl lg:text-[52px]">Serving with Heart</h2>
                <p class="mt-7 text-base leading-relaxed text-ink-500">
                    The Fifiawoto Foundation, established in honour of the esteemed Madam Dadaa Fifiawoto Nyamadi-Adabla, embodies the spirit of compassion and service that she exemplified throughout her life. Like the revered Mother Teresa, Madam Dadaa Fifiawoto was a dedicated humanitarian who devoted herself to the well-being of her community, her church (Apostles Revelation Society), and humanity at large. In her memory, her descendants founded the Fifiawoto Foundation to continue her legacy of selfless service.
                </p>
                <p class="mt-4 text-base leading-relaxed text-ink-500">
                    The foundation is committed to supporting women, children, and all individuals in need, including orphanages, widows, and those with physical and mental challenges. The foundation's activities currently span the United States and several African countries, including Ghana and Togo.
                </p>
            </div>
        </div>
    </section>

    {{-- LEGACY QUOTE BAND --}}
    <section class="bg-brand-200/80 py-16 sm:py-20" aria-label="Foundation tribute">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <div class="reveal-down mx-auto grid size-14 place-items-center overflow-hidden rounded-full bg-white shadow-[0_10px_30px_-15px_rgba(0,4,78,0.4)] ring-4 ring-white">
                <img
                    src="{{ asset('images/foundation/about/madam-dadaa-portrait.jpg') }}"
                    alt="Portrait of Madam Dadaa Fifiawoto Nyamadi-Adabla"
                    class="size-full object-cover"
                    loading="lazy"
                >
            </div>
            <blockquote class="reveal mt-6 font-serif text-2xl font-bold leading-snug text-ink-900 sm:text-3xl" style="transition-delay: 160ms">
                Compassion is a legacy that never fades. The Fifiawoto Foundation honours Madam Dadaa Fifiawoto by turning care into action.
            </blockquote>
        </div>
    </section>

    {{-- MISSION + VISION --}}
    <section class="bg-white py-20 lg:py-28" aria-labelledby="mission-vision-heading">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8">
            {{-- LEFT: heading + Mission/Vision copy --}}
            <div>
                <h2 id="mission-vision-heading" class="reveal-left font-serif text-4xl font-bold leading-[1.1] text-gold-500 sm:text-5xl lg:text-[52px]">
                    Empowering Hope,<br>Inspiring Change
                </h2>

                <div class="reveal mt-8 space-y-8" style="transition-delay: 160ms">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold-500">Mission</p>
                        <p class="mt-3 text-base leading-relaxed text-ink-500">
                            To honour the legacy of Madam Dadaa Fifiawoto Nyamadi-Adabla by empowering and uplifting underserved communities through comprehensive support and sustainable initiatives, with a focus on women, children, and vulnerable individuals.
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold-500">Vision</p>
                        <p class="mt-3 text-base leading-relaxed text-ink-500">
                            To create a world where every individual, regardless of their circumstances, has the opportunity for a new beginning and a brighter future, inspired by the enduring values of compassion, service, and community.
                        </p>
                    </div>
                </div>
            </div>

            {{-- RIGHT: photo with play-button overlay (links to the public IG reel) --}}
            <div class="reveal-right relative" style="transition-delay: 240ms">
                <img
                    src="{{ asset('images/foundation/about/empowering-hope.jpg') }}"
                    alt="Foundation volunteers distributing school bags to children"
                    class="img-fade aspect-[4/3] w-full rounded-3xl object-cover shadow-[0_24px_60px_-30px_rgba(0,4,78,0.35)]"
                    loading="lazy"
                >
                {{-- Play-button overlay — links to the most recent reel; admins can swap this URL --}}
                <a
                    href="{{ config('social.instagram_reel', config('social.instagram')) }}"
                    target="_blank"
                    rel="noopener"
                    aria-label="Watch our story on Instagram"
                    class="group absolute -bottom-6 -left-6 grid aspect-square w-32 place-items-center rounded-2xl bg-brand-200/85 backdrop-blur transition hover:bg-gold-500 sm:w-40"
                >
                    <span class="grid size-14 place-items-center rounded-full bg-white text-brand-900 shadow-lg transition group-hover:bg-white group-hover:text-gold-500 sm:size-16">
                        <svg class="size-6 translate-x-0.5 sm:size-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                </a>
            </div>
        </div>
    </section>

    {{-- VALUES --}}
    <section class="bg-cream-50 py-20" aria-labelledby="values-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">What we stand for</p>
                <h2 id="values-heading" class="reveal mt-3 font-serif text-3xl text-brand-900 sm:text-4xl" style="transition-delay: 120ms">Core Values</h2>
            </div>

            @php
                $valueIcons = [
                    'compassion' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35Z"/>',
                    'service'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-3 6c0-1.66 1.34-3 3-3s3 1.34 3 3v4H9v-4Zm-6-1c0-1.1.9-2 2-2s2 .9 2 2v5H3v-5Zm14 0c0-1.1.9-2 2-2s2 .9 2 2v5h-4v-5Z"/>',
                    'empower'    => '<path stroke-linecap="round" stroke-linejoin="round" d="m12 2 3 6 6 .9-4.5 4.2 1 6.4L12 16l-5.5 3.5 1-6.4L3 8.9 9 8z"/>',
                    'inclusive'  => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 20c0-3 2.7-5 6-5s6 2 6 5m1-4c2 0 5 1.2 5 4"/>',
                    'sustain'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4-2-7-6-7-11 0 0 3 0 7 3 4-3 7-3 7-3 0 5-3 9-7 11Zm0-13v13"/>',
                ];

                $values = [
                    ['icon' => 'compassion', 'title' => 'Compassion',     'copy' => 'Meeting every person we serve with empathy and dignity.',     'highlight' => true],
                    ['icon' => 'service',    'title' => 'Service',        'copy' => 'Acting in the interest of others before our own.',           'highlight' => false],
                    ['icon' => 'empower',    'title' => 'Empowerment',    'copy' => 'Equipping communities to build their own future.',           'highlight' => false],
                    ['icon' => 'inclusive',  'title' => 'Inclusivity',    'copy' => 'Welcoming every background, faith, and story.',              'highlight' => false],
                    ['icon' => 'sustain',    'title' => 'Sustainability', 'copy' => 'Designing programs that outlast a single season.',           'highlight' => false],
                ];
            @endphp

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                @foreach ($values as $value)
                    @if ($value['highlight'])
                        <article class="group flex flex-col justify-between rounded-3xl bg-brand-900 p-7 text-white shadow-[0_18px_50px_-20px_rgba(0,4,78,0.45)] transition hover:-translate-y-1">
                            <div>
                                <div class="grid size-11 place-items-center rounded-xl bg-white/15 ring-1 ring-white/25 backdrop-blur">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $valueIcons[$value['icon']] !!}</svg>
                                </div>
                                <h3 class="mt-8 font-serif text-xl font-bold leading-tight">{{ $value['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-white/75">{{ $value['copy'] }}</p>
                            </div>
                        </article>
                    @else
                        <article class="group flex flex-col justify-between rounded-3xl bg-white p-7 ring-1 ring-cream-300 transition hover:-translate-y-1 hover:ring-brand-200 hover:shadow-[0_18px_50px_-20px_rgba(0,4,78,0.25)]">
                            <div>
                                <div class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $valueIcons[$value['icon']] !!}</svg>
                                </div>
                                <h3 class="mt-8 font-serif text-xl font-bold leading-tight text-ink-900">{{ $value['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $value['copy'] }}</p>
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- GLOBAL PRESENCE --}}
    <section class="bg-brand-900 py-20 text-cream-50" aria-labelledby="global-presence-heading">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 md:grid-cols-2 md:items-center lg:px-8">
            <div>
                <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-400">Where we work</p>
                <h2 id="global-presence-heading" class="reveal mt-3 font-serif text-3xl sm:text-4xl" style="transition-delay: 120ms">A Global Presence</h2>
                <p class="reveal mt-6 text-sm leading-relaxed text-cream-200/80" style="transition-delay: 240ms">
                    The Fifiawoto Foundation operates across four countries, partnering with churches, schools, and community leaders to deliver programs where they're needed most.
                </p>
                <ul class="mt-8 grid grid-cols-2 gap-4 text-sm">
                    @foreach (['United States', 'Ghana', 'Togo', 'Benin'] as $country)
                        <li class="flex items-center gap-3 rounded-lg border border-brand-700/40 bg-brand-800/50 px-4 py-3">
                            <span class="size-2 rounded-full bg-gold-400"></span>
                            <span class="text-cream-50">{{ $country }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-6 text-xs text-cream-200/60">An interactive map will replace this list in a later release.</p>
            </div>

            <div class="relative">
                <div class="aspect-[4/3] overflow-hidden rounded-2xl border border-brand-700/40 bg-brand-800/40 p-6">
                    <svg viewBox="0 0 320 220" class="h-full w-full text-brand-700" aria-hidden="true">
                        <rect x="0" y="0" width="320" height="220" fill="none" />
                        <g fill="currentColor" opacity="0.45">
                            <circle cx="70"  cy="90"  r="46" />
                            <circle cx="170" cy="110" r="36" />
                            <circle cx="200" cy="140" r="20" />
                            <circle cx="220" cy="150" r="16" />
                        </g>
                        <g fill="var(--color-gold-400)">
                            <circle cx="70"  cy="90"  r="5" />
                            <circle cx="170" cy="110" r="5" />
                            <circle cx="195" cy="138" r="5" />
                            <circle cx="220" cy="148" r="5" />
                        </g>
                        <g fill="var(--color-cream-50)" font-family="ui-sans-serif" font-size="9">
                            <text x="80"  y="84">United States</text>
                            <text x="180" y="104">Ghana</text>
                            <text x="180" y="160">Togo</text>
                            <text x="230" y="148">Benin</text>
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    {{-- LEADERSHIP --}}
    <section class="bg-cream-50 py-24" aria-labelledby="leadership-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Our leadership</p>
                <h2 id="leadership-heading" class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl">
                    Meet the leadership team
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    A founder, a board, and a circle of advisors who guide the foundation's direction and accountability.
                </p>
            </div>

            @php
                $leaders = \App\Models\Leader::query()->published()->ordered()->get();
            @endphp

            @if ($leaders->isNotEmpty())
                <div class="mt-14 grid grid-cols-2 gap-x-5 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($leaders as $i => $leader)
                        @php
                            $initials = collect(preg_split('/\s+/', trim($leader->name)))
                                ->filter()
                                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                ->take(2)
                                ->implode('');
                            $photo = $leader->photo_path;
                            $photoSrc = filled($photo)
                                ? (str_starts_with($photo, 'http')
                                    ? $photo
                                    : (str_starts_with($photo, 'leaders/')
                                        ? asset('storage/'.$photo)
                                        : asset($photo)))
                                : null;
                        @endphp
                        <figure class="reveal group" style="transition-delay: {{ ($i % 4) * 80 }}ms">
                            <div class="relative aspect-square overflow-hidden rounded-2xl bg-cream-200">
                                @if ($photoSrc)
                                    <img
                                        src="{{ $photoSrc }}"
                                        alt="{{ $leader->name }}, {{ $leader->role }}"
                                        class="img-fade absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                        loading="lazy"
                                        onerror="this.style.display='none'; this.parentElement.querySelector('[data-fallback]').style.display='flex';"
                                    >
                                @endif
                                <div data-fallback class="absolute inset-0 {{ $photoSrc ? 'hidden' : 'flex' }} items-center justify-center bg-gradient-to-br from-brand-100 via-cream-200 to-brand-50">
                                    <span class="font-serif text-4xl font-bold text-brand-900/70">{{ $initials }}</span>
                                </div>
                            </div>
                            <figcaption class="mt-4">
                                <p class="font-serif text-base font-bold leading-tight text-brand-900">{{ $leader->name }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-ink-500">{{ $leader->role }}</p>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif

            <div class="mt-14 flex flex-col items-center justify-between gap-4 rounded-3xl border border-brand-100 bg-white/70 p-8 backdrop-blur sm:flex-row">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold-500">Get involved</p>
                    <p class="mt-2 max-w-xl text-sm text-ink-700">
                        Interested in serving on our advisory board, partnering on a program, or supporting a specific country office? We'd love to hear from you.
                    </p>
                </div>
                <a href="{{ route('contact') }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-brand-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-gold-500" wire:navigate>
                    Reach out to the foundation
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    {{-- NEWSLETTER (reused) --}}
    <livewire:site.newsletter-signup source="about" />
</x-layouts::site>
