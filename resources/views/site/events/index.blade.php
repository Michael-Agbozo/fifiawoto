<x-layouts::site title="Events">
    <x-site.page-hero
        title="Outreach in Action"
        breadcrumb="Events"
        image="images/foundation/outreach-1.jpg"
        alt="Foundation volunteers and community members at an outreach event"
    >
        Events and outreach programs allow the foundation to deliver direct support to communities. Each event below has its own story, goals, volunteer roles, and donation page.
    </x-site.page-hero>

    {{-- SEARCH --}}
    <section class="bg-cream-50 py-14 sm:py-20">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <p class="eyebrow">Find an event</p>
            <h2 class="section-title mt-3 text-heading">Find an outreach near you</h2>

            <form
                method="get"
                action="{{ route('events.index') }}"
                class="mx-auto mt-8 flex max-w-3xl items-center gap-2 rounded-full border border-cream-300 bg-surface p-2 shadow-[0_20px_50px_-30px_rgba(0,4,78,0.45)] transition focus-within:border-heading focus-within:shadow-[0_24px_60px_-28px_rgba(0,4,78,0.55)] focus-within:ring-4 focus-within:ring-brand-500/15"
                role="search"
            >
                <span class="ml-1 hidden size-11 shrink-0 place-items-center rounded-full bg-cream-100 text-heading sm:grid" aria-hidden="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/>
                    </svg>
                </span>
                <label for="event-search" class="sr-only">Search events</label>
                <input
                    id="event-search"
                    type="search"
                    name="q"
                    value="{{ $search ?? '' }}"
                    placeholder="Event, town or country…"
                    autocomplete="off"
                    class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base text-heading placeholder-ink-500 focus:outline-none focus:ring-0 sm:text-lg"
                >
                @if (filled($search ?? ''))
                    <a href="{{ route('events.index') }}" class="grid size-10 shrink-0 place-items-center rounded-full text-ink-500 transition hover:bg-cream-100 hover:text-heading" title="Clear search" aria-label="Clear search">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                <button type="submit" class="btn btn-primary btn-lg shrink-0 px-5 sm:px-7">
                    <span class="hidden sm:inline">Search</span>
                    <svg class="size-5 sm:size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    <span class="sr-only sm:hidden">Search</span>
                </button>
            </form>

            <div class="mt-5 flex flex-wrap items-center justify-center gap-2 text-sm">
                <span class="text-ink-500">Popular:</span>
                @foreach (['Ghana', 'Togo', 'Benin', 'United States'] as $country)
                    @php $isActive = strcasecmp(trim($search ?? ''), $country) === 0; @endphp
                    <a
                        href="{{ route('events.index', ['q' => $country]) }}"
                        @class([
                            'rounded-full border px-4 py-1.5 font-medium transition',
                            'border-heading bg-heading text-surface' => $isActive,
                            'border-cream-300 bg-surface text-ink-700 hover:border-heading hover:text-heading' => ! $isActive,
                        ])
                        @if ($isActive) aria-current="true" @endif
                    >{{ $country }}</a>
                @endforeach
            </div>

            @if (filled($search ?? ''))
                <p class="mt-4 text-sm text-ink-500">
                    Showing results for <strong class="font-semibold text-heading">"{{ $search }}"</strong>
                    · {{ $upcoming->count() }} upcoming, {{ $past->count() }} past
                </p>
            @endif
        </div>
    </section>

    {{-- UPCOMING --}}
    <section class="section" aria-labelledby="upcoming-events-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between">
                <h2 id="upcoming-events-heading" class="reveal font-serif text-2xl text-heading sm:text-3xl">Upcoming events</h2>
                <p class="eyebrow reveal" style="transition-delay: 120ms">{{ $upcoming->count() }} upcoming</p>
            </div>

            @if ($upcoming->isEmpty())
                <div class="mt-8 rounded-2xl border border-dashed border-brand-200 bg-surface p-12 text-center">
                    <h3 class="font-serif text-xl text-heading">
                        @if (filled($search ?? ''))
                            No upcoming events match "{{ $search }}"
                        @else
                            No upcoming events yet
                        @endif
                    </h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-ink-500">
                        @if (filled($search ?? ''))
                            Try a different keyword, or
                            <a href="{{ route('events.index') }}" class="font-semibold text-label hover:text-gold-500">clear the search</a>
                            to see everything.
                        @else
                            Check back soon, or get involved through one of the other ways below.
                        @endif
                    </p>
                    @if (! filled($search ?? ''))
                        <div class="mt-6 flex justify-center">
                            <x-site.event-actions variant="light" />
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-8 grid gap-4 sm:mt-10 sm:gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcoming as $i => $event)
                        @php
                            $raised = (int) ($event->raised_cents ?? 0);
                            $goal = (int) ($event->goal_cents ?? 0);
                            $percent = $goal > 0 ? min(100, (int) round($raised / $goal * 100)) : 0;
                            $fallbacks = ['images/foundation/school-donation.jpg', 'images/foundation/outreach-1.jpg', 'images/foundation/outreach-4.webp', 'images/foundation/community-1.jpg'];
                            $heroImage = \App\Support\MediaUrl::for($event->hero_image_path ?: $fallbacks[$loop->index % count($fallbacks)]);
                        @endphp
                        <article class="reveal-3d tilt group flex h-full flex-col overflow-hidden rounded-3xl border border-cream-300 bg-surface shadow-sm hover:shadow-lg" style="transition-delay: {{ ($i % 3) * 100 }}ms">
                            <div class="relative aspect-[2/1] overflow-hidden sm:aspect-[5/3]">
                                <img
                                    src="{{ $heroImage }}"
                                    alt="{{ $event->title }}"
                                    class="img-fade absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                    loading="lazy"
                                >
                            </div>
                            <div class="flex flex-1 flex-col p-5 sm:p-6">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-gold-500">
                                    {{ $event->starts_at?->format('M j, Y') }}
                                </p>
                                <h3 class="mt-2 font-serif text-xl text-heading">
                                    <a href="{{ route('events.show', $event) }}" class="hover:text-gold-600" wire:navigate>
                                        {{ $event->title }}
                                    </a>
                                </h3>
                                <p class="mt-2 flex items-center gap-1.5 text-xs text-ink-500">
                                    <svg class="size-3.5 text-ink-500/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4-3.5-7-7-7-11a7 7 0 0 1 14 0c0 4-3 7.5-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                    {{ $event->location }}
                                </p>
                                <p class="mt-3 hidden text-sm text-ink-500 sm:line-clamp-3">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($event->description), 160) }}
                                </p>

                                @if ($goal > 0)
                                    <div class="mt-5">
                                        <div class="flex items-center justify-between text-xs text-ink-500">
                                            <span>{{ \App\Support\Money::fromCents((int) $raised) }} raised</span>
                                            <span>Goal {{ \App\Support\Money::fromCents((int) $goal) }}</span>
                                        </div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-brand-100">
                                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-auto flex flex-wrap items-center gap-2 pt-5 sm:pt-6">
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-secondary btn-sm" wire:navigate>Read More</a>
                                    <x-site.event-actions :event="$event" variant="light" size="sm" />
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- PAST --}}
    @if ($past->isNotEmpty())
        <section class="section bg-brand-50" aria-labelledby="past-events-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between">
                    <div class="reveal-left">
                        <h2 id="past-events-heading" class="font-serif text-2xl text-heading sm:text-3xl">Past events</h2>
                        <p class="mt-2 text-sm text-ink-500">Programmes the foundation has already delivered.</p>
                    </div>
                    <p class="eyebrow reveal-right">{{ $past->count() }} completed</p>
                </div>

                <div class="mt-8 grid grid-cols-2 gap-3 sm:mt-10 sm:gap-6">
                    @foreach ($past as $i => $event)
                        @php
                            $raised = (int) ($event->raised_cents ?? 0);
                            $goal = (int) ($event->goal_cents ?? 0);
                            $percent = $goal > 0 ? min(100, (int) round($raised / $goal * 100)) : 0;
                            $fallbacks = ['images/foundation/school-donation.jpg', 'images/foundation/outreach-1.jpg', 'images/foundation/outreach-4.webp', 'images/foundation/community-1.jpg'];
                            $heroImage = \App\Support\MediaUrl::for($event->hero_image_path ?: $fallbacks[$loop->index % count($fallbacks)]);
                        @endphp
                        <article class="reveal-3d tilt group flex h-full flex-col overflow-hidden rounded-3xl border border-cream-300 bg-surface shadow-sm hover:shadow-lg" style="transition-delay: {{ ($i % 2) * 120 }}ms">
                            <a href="{{ route('events.show', $event) }}" class="relative block aspect-[16/9] overflow-hidden" wire:navigate>
                                <img
                                    src="{{ $heroImage }}"
                                    alt="{{ $event->title }}"
                                    class="img-fade absolute inset-0 h-full w-full object-cover grayscale transition duration-500 group-hover:scale-105 group-hover:grayscale-0"
                                    loading="lazy"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-brand-900/55 via-transparent to-transparent"></div>
                                <span class="absolute left-2 top-2 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2 py-1 text-[9px] sm:left-4 sm:top-4 sm:px-3 sm:text-[10px] font-bold uppercase tracking-[0.18em] text-brand-900 shadow-sm">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                    Completed
                                </span>
                            </a>
                            <div class="flex flex-1 flex-col p-3 sm:p-7">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-gold-500">
                                    {{ $event->starts_at?->format('M j, Y') }}
                                </p>
                                <h3 class="mt-1 font-serif text-base leading-snug text-heading sm:mt-2 sm:text-2xl">
                                    <a href="{{ route('events.show', $event) }}" class="hover:text-gold-600" wire:navigate>
                                        {{ $event->title }}
                                    </a>
                                </h3>
                                <p class="mt-2 flex items-center gap-1.5 text-xs text-ink-500">
                                    <svg class="size-3.5 text-ink-500/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4-3.5-7-7-7-11a7 7 0 0 1 14 0c0 4-3 7.5-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                    {{ $event->location }}
                                </p>
                                <p class="mt-3 hidden text-sm leading-relaxed text-ink-500 sm:line-clamp-3">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($event->description), 200) }}
                                </p>

                                @if ($goal > 0)
                                    <div class="mt-3 sm:mt-5">
                                        <div class="flex flex-wrap items-center justify-between gap-x-2 text-[11px] text-ink-500 sm:text-xs">
                                            <span><strong class="font-semibold text-ink-900">{{ \App\Support\Money::fromCents((int) $raised) }}</strong> raised</span>
                                            <span>{{ $percent }}%<span class="hidden sm:inline"> of {{ \App\Support\Money::fromCents((int) $goal) }} goal</span></span>
                                        </div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-brand-100">
                                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-auto hidden pt-6 sm:block">
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-secondary btn-sm" wire:navigate>
                                        See impact
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <livewire:site.newsletter-signup source="events" />
</x-layouts::site>
