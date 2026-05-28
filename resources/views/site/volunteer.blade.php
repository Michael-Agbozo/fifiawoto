<x-layouts::site title="Volunteer">
    <x-site.page-hero
        title="Lend your time. Change a story."
        breadcrumb="Volunteer"
        image="images/foundation/outreach-4.webp"
        alt="Volunteers preparing care packs at the foundation"
    >
        Volunteers are the engine behind every programme we run. Whether you can offer an afternoon, a weekend, or a recurring role, your time directly extends the foundation's reach.
    </x-site.page-hero>

    {{-- WHY VOLUNTEER + stats --}}
    <section class="py-20 lg:py-24" aria-labelledby="why-volunteer-heading">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:items-center lg:gap-16 lg:px-8">
            <div class="reveal-left">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Why volunteer with us</p>
                <h2 id="why-volunteer-heading" class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl lg:text-[44px]">A small commitment, a real-world impact.</h2>
                <p class="mt-5 text-sm leading-relaxed text-ink-500 sm:text-base">
                    The foundation runs lean by design. Most of our work is delivered by volunteers — community members, students, professionals, and diaspora supporters — coordinated by a small core team. That means every hour you give is felt directly in the lives of the families we walk alongside.
                </p>
                <ul class="mt-7 space-y-3 text-sm sm:text-base">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-gold-500/15 text-gold-500">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <span class="text-ink-700">Flexible roles — remote or on-the-ground, one-off or recurring.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-gold-500/15 text-gold-500">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <span class="text-ink-700">Real responsibility — you'll own a piece of a real programme, not just stuff envelopes.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-gold-500/15 text-gold-500">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <span class="text-ink-700">A welcoming team — orientation, mentoring, and a community of fellow volunteers.</span>
                    </li>
                </ul>
            </div>

            <div class="reveal-right grid grid-cols-2 gap-4 sm:gap-5" style="transition-delay: 160ms">
                @foreach ([
                    [120, '+', 'Active volunteers'],
                    [45,  '+', 'Programmes delivered'],
                    [18,  '+', 'Communities reached'],
                    [4,   '',  'Countries on the roster'],
                ] as $i => [$value, $suffix, $label])
                    <div class="rounded-3xl bg-cream-100 p-6 ring-1 ring-brand-100 sm:p-7">
                        <p
                            class="font-serif text-4xl font-bold text-brand-900 sm:text-5xl"
                            data-counter="{{ $value }}"
                            data-suffix="{{ $suffix }}"
                            data-duration="2800"
                        >0{{ $suffix }}</p>
                        <p class="mt-2 text-xs font-medium uppercase tracking-[0.16em] text-ink-500">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- OPPORTUNITIES --}}
    <section id="opportunities" class="bg-cream-50 py-20 lg:py-24" aria-labelledby="opportunities-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Where you fit in</p>
                <h2 id="opportunities-heading" class="reveal mt-3 font-serif text-3xl text-brand-900 sm:text-4xl" style="transition-delay: 120ms">Volunteer opportunities</h2>
                <p class="reveal mt-4 text-sm leading-relaxed text-ink-500 sm:text-base" style="transition-delay: 240ms">
                    Five tracks, each tied to a specific area of foundation work. You can express interest in more than one — we'll match you to what's needed when you apply.
                </p>
            </div>

            @php
                $opportunityIcons = [
                    'outreach' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 20a8 8 0 0 1 16 0M11 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm9-1 3-3-3-3m0 3h-7"/>',
                    'mentor'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 19V8.5L12 4l8 4.5V19m-16 0h16m-12 0v-6h8v6"/>',
                    'event'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M8 3v4m8-4v4M8 14h2m4 0h2m-8 4h2m4 0h2"/>',
                    'admin'    => '<rect x="4" y="3" width="16" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h5"/>',
                    'media'    => '<rect x="3" y="6" width="18" height="13" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m10 11 5 2.5L10 16v-5Z"/>',
                ];

                $opportunities = [
                    ['icon' => 'outreach', 'title' => 'Community outreach',       'copy' => 'Show up on-site and deliver support directly to families and individuals.',  'tag' => 'On-the-ground', 'highlight' => true],
                    ['icon' => 'mentor',   'title' => 'Education support',        'copy' => 'Mentor children, tutor students, or coordinate scholarship programmes.',    'tag' => 'Recurring',     'highlight' => false],
                    ['icon' => 'event',    'title' => 'Event coordination',       'copy' => 'Help plan, run, and document foundation programmes end-to-end.',           'tag' => 'Project-based', 'highlight' => false],
                    ['icon' => 'admin',    'title' => 'Administrative support',   'copy' => 'Operations, communications, donor stewardship, and data entry.',          'tag' => 'Remote-friendly','highlight' => false],
                    ['icon' => 'media',    'title' => 'Media and communications', 'copy' => 'Photography, video, social, writing, and storytelling.',                  'tag' => 'Creative',      'highlight' => false],
                ];
            @endphp

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                @foreach ($opportunities as $i => $opportunity)
                    @if ($opportunity['highlight'])
                        <article class="reveal group flex flex-col justify-between rounded-3xl bg-brand-900 p-7 text-white shadow-[0_18px_50px_-20px_rgba(0,4,78,0.45)] transition hover:-translate-y-1" style="transition-delay: {{ $i * 100 }}ms">
                            <div>
                                <div class="flex items-center justify-between">
                                    <div class="grid size-11 place-items-center rounded-xl bg-white/15 ring-1 ring-white/25 backdrop-blur">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $opportunityIcons[$opportunity['icon']] !!}</svg>
                                    </div>
                                    <span class="rounded-full bg-gold-500/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-gold-300">{{ $opportunity['tag'] }}</span>
                                </div>
                                <h3 class="mt-8 font-serif text-xl font-bold leading-tight">{{ $opportunity['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-white/75">{{ $opportunity['copy'] }}</p>
                            </div>
                        </article>
                    @else
                        <article class="reveal group flex flex-col justify-between rounded-3xl bg-white p-7 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100 transition hover:-translate-y-1 hover:shadow-[0_18px_50px_-20px_rgba(0,4,78,0.25)] hover:ring-brand-200" style="transition-delay: {{ $i * 100 }}ms">
                            <div>
                                <div class="flex items-center justify-between">
                                    <div class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $opportunityIcons[$opportunity['icon']] !!}</svg>
                                    </div>
                                    <span class="rounded-full bg-cream-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-ink-500">{{ $opportunity['tag'] }}</span>
                                </div>
                                <h3 class="mt-8 font-serif text-xl font-bold leading-tight text-ink-900">{{ $opportunity['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $opportunity['copy'] }}</p>
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS — 4-step process --}}
    <section class="py-20 lg:py-24" aria-labelledby="how-volunteer-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">How it works</p>
                <h2 id="how-volunteer-heading" class="reveal mt-3 font-serif text-3xl text-brand-900 sm:text-4xl" style="transition-delay: 120ms">From application to first shift</h2>
                <p class="reveal mt-4 text-sm leading-relaxed text-ink-500 sm:text-base" style="transition-delay: 240ms">
                    Four short steps — typically takes a week from when we receive your application.
                </p>
            </div>

            <ol class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Apply',       'Fill in the short form below. Tell us what you\'d like to do and how much time you can offer.'],
                    ['Brief call',  'A coordinator gets in touch within 48 hours to learn what you\'re hoping for and answer any questions.'],
                    ['Orientation', 'Short onboarding (in-person or video) covering programmes, safeguarding, and what to expect on-site.'],
                    ['First role',  'You\'re matched to an active programme. The team supports you through your first shift and every shift after.'],
                ] as $i => [$step, $copy])
                    <li class="reveal relative flex h-full flex-col rounded-3xl bg-white p-7 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100" style="transition-delay: {{ $i * 100 }}ms">
                        <span class="grid size-10 place-items-center rounded-2xl bg-gold-500 font-serif text-base font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-5 font-serif text-lg font-bold text-brand-900">{{ $step }}</h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-500">{{ $copy }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- APPLY (existing Livewire form) --}}
    <div id="apply" class="scroll-mt-24">
        <livewire:site.volunteer-application-form />
    </div>

    {{-- FOR ORGANISATIONS --}}
    <section class="bg-brand-900 py-16 text-white">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 text-center sm:px-6 lg:flex-row lg:text-left lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-300">Group volunteering?</p>
                <h2 class="mt-2 font-serif text-2xl sm:text-3xl">Bring your team, school, or church.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80">
                    We host group volunteer days for companies, student societies, and faith communities. Tell us your group size and what you'd like to do; we'll design something meaningful.
                </p>
            </div>
            <a href="{{ route('contact') }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-white hover:text-brand-900" wire:navigate>
                Plan a group day
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <livewire:site.newsletter-signup source="volunteer" />
</x-layouts::site>
