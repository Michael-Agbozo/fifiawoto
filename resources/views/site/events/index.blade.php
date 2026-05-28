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
    <section class="bg-cream-50 py-12 sm:py-16">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <form
                method="get"
                action="{{ route('events.index') }}"
                class="mx-auto flex max-w-xl items-center gap-2 rounded-full border border-brand-100 bg-white p-1.5 shadow-sm focus-within:border-brand-700 focus-within:ring-2 focus-within:ring-brand-700/15"
                role="search"
            >
                <span class="pl-3 text-ink-500">
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
                    placeholder="Search by event name, location, or country…"
                    autocomplete="off"
                    class="flex-1 border-0 bg-transparent px-2 py-2 text-sm text-brand-900 placeholder-ink-500 focus:outline-none focus:ring-0"
                >
                @if (filled($search ?? ''))
                    <a href="{{ route('events.index') }}" class="grid size-9 place-items-center rounded-full text-ink-500 transition hover:bg-cream-100 hover:text-brand-900" title="Clear search" aria-label="Clear search">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                <button type="submit" class="rounded-full bg-brand-900 px-5 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-gold-500">
                    Search
                </button>
            </form>

            @if (filled($search ?? ''))
                <p class="mt-4 text-sm text-ink-500">
                    Showing results for <strong class="font-semibold text-brand-900">"{{ $search }}"</strong>
                    · {{ $upcoming->count() }} upcoming, {{ $past->count() }} past
                </p>
            @endif
        </div>
    </section>

    {{-- UPCOMING --}}
    <section class="py-16" aria-labelledby="upcoming-events-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between">
                <h2 id="upcoming-events-heading" class="reveal font-serif text-2xl text-brand-900 sm:text-3xl">Upcoming events</h2>
                <p class="reveal text-xs uppercase tracking-[0.24em] text-gold-500" style="transition-delay: 120ms">{{ $upcoming->count() }} upcoming</p>
            </div>

            @if ($upcoming->isEmpty())
                <div class="mt-8 rounded-2xl border border-dashed border-brand-200 bg-white p-12 text-center">
                    <h3 class="font-serif text-xl text-brand-900">
                        @if (filled($search ?? ''))
                            No upcoming events match "{{ $search }}"
                        @else
                            No upcoming events yet
                        @endif
                    </h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-ink-500">
                        @if (filled($search ?? ''))
                            Try a different keyword, or
                            <a href="{{ route('events.index') }}" class="font-semibold text-brand-700 hover:text-gold-500">clear the search</a>
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
                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcoming as $i => $event)
                        @php
                            $raised = (int) ($event->raised_cents ?? 0);
                            $goal = (int) ($event->goal_cents ?? 0);
                            $percent = $goal > 0 ? min(100, (int) round($raised / $goal * 100)) : 0;
                            $fallbacks = ['images/foundation/school-donation.jpg', 'images/foundation/outreach-1.jpg', 'images/foundation/outreach-4.webp', 'images/foundation/community-1.jpg'];
                            $heroImage = $event->hero_image_path
                                ? asset('storage/'.$event->hero_image_path)
                                : asset($fallbacks[$loop->index % count($fallbacks)]);
                        @endphp
                        <article class="reveal group flex h-full flex-col overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg" style="transition-delay: {{ ($i % 3) * 100 }}ms">
                            <div class="relative aspect-[5/3] overflow-hidden">
                                <img
                                    src="{{ $heroImage }}"
                                    alt="{{ $event->title }}"
                                    class="img-fade absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                    loading="lazy"
                                >
                            </div>
                            <div class="flex flex-1 flex-col p-6">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-gold-500">
                                    {{ $event->starts_at?->format('M j, Y') }}
                                </p>
                                <h3 class="mt-2 font-serif text-xl text-brand-900">
                                    <a href="{{ route('events.show', $event) }}" class="hover:text-gold-600" wire:navigate>
                                        {{ $event->title }}
                                    </a>
                                </h3>
                                <p class="mt-2 flex items-center gap-1.5 text-xs text-ink-500">
                                    <svg class="size-3.5 text-ink-500/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4-3.5-7-7-7-11a7 7 0 0 1 14 0c0 4-3 7.5-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                    {{ $event->location }}
                                </p>
                                <p class="mt-3 text-sm text-ink-500 line-clamp-3">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($event->description), 160) }}
                                </p>

                                @if ($goal > 0)
                                    <div class="mt-5">
                                        <div class="flex items-center justify-between text-xs text-ink-500">
                                            <span>${{ number_format($raised / 100) }} raised</span>
                                            <span>Goal ${{ number_format($goal / 100) }}</span>
                                        </div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-brand-100">
                                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-6 flex flex-wrap items-center gap-2 pt-auto">
                                    <a href="{{ route('events.show', $event) }}" class="rounded-full border-2 border-brand-900 px-4 py-1.5 text-xs font-semibold text-brand-900 transition hover:bg-brand-900 hover:text-white" wire:navigate>Read More</a>
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
        <section class="bg-brand-50 py-16" aria-labelledby="past-events-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between">
                    <div class="reveal-left">
                        <h2 id="past-events-heading" class="font-serif text-2xl text-brand-900 sm:text-3xl">Past events</h2>
                        <p class="mt-2 text-sm text-ink-500">Programmes the foundation has already delivered.</p>
                    </div>
                    <p class="reveal-right text-xs uppercase tracking-[0.24em] text-gold-500">{{ $past->count() }} completed</p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    @foreach ($past as $i => $event)
                        @php
                            $raised = (int) ($event->raised_cents ?? 0);
                            $goal = (int) ($event->goal_cents ?? 0);
                            $percent = $goal > 0 ? min(100, (int) round($raised / $goal * 100)) : 0;
                            $fallbacks = ['images/foundation/school-donation.jpg', 'images/foundation/outreach-1.jpg', 'images/foundation/outreach-4.webp', 'images/foundation/community-1.jpg'];
                            $heroImage = $event->hero_image_path
                                ? asset('storage/'.$event->hero_image_path)
                                : asset($fallbacks[$loop->index % count($fallbacks)]);
                        @endphp
                        <article class="reveal group flex h-full flex-col overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg" style="transition-delay: {{ ($i % 2) * 120 }}ms">
                            <a href="{{ route('events.show', $event) }}" class="relative block aspect-[16/9] overflow-hidden" wire:navigate>
                                <img
                                    src="{{ $heroImage }}"
                                    alt="{{ $event->title }}"
                                    class="img-fade absolute inset-0 h-full w-full object-cover grayscale transition duration-500 group-hover:scale-105 group-hover:grayscale-0"
                                    loading="lazy"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-brand-900/55 via-transparent to-transparent"></div>
                                <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-brand-900 shadow-sm">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                    Completed
                                </span>
                            </a>
                            <div class="flex flex-1 flex-col p-6 sm:p-7">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-gold-500">
                                    {{ $event->starts_at?->format('M j, Y') }}
                                </p>
                                <h3 class="mt-2 font-serif text-xl text-brand-900 sm:text-2xl">
                                    <a href="{{ route('events.show', $event) }}" class="hover:text-gold-600" wire:navigate>
                                        {{ $event->title }}
                                    </a>
                                </h3>
                                <p class="mt-2 flex items-center gap-1.5 text-xs text-ink-500">
                                    <svg class="size-3.5 text-ink-500/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4-3.5-7-7-7-11a7 7 0 0 1 14 0c0 4-3 7.5-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                    {{ $event->location }}
                                </p>
                                <p class="mt-3 text-sm leading-relaxed text-ink-500 line-clamp-3">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($event->description), 200) }}
                                </p>

                                @if ($goal > 0)
                                    <div class="mt-5">
                                        <div class="flex items-center justify-between text-xs text-ink-500">
                                            <span><strong class="font-semibold text-ink-900">${{ number_format($raised / 100) }}</strong> raised</span>
                                            <span>{{ $percent }}% of ${{ number_format($goal / 100) }} goal</span>
                                        </div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-brand-100">
                                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-6 pt-auto">
                                    <a href="{{ route('events.show', $event) }}" class="inline-flex items-center gap-1.5 rounded-full border-2 border-brand-900 px-4 py-1.5 text-xs font-semibold text-brand-900 transition hover:bg-brand-900 hover:text-white" wire:navigate>
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
