@php
    $raised = $event->raisedCents();
    $goal = (int) ($event->goal_cents ?? 0);
    $percent = $event->progressPercent();
    $fallbacks = ['images/foundation/school-donation.jpg', 'images/foundation/outreach-1.jpg', 'images/foundation/outreach-4.webp'];
    $heroImage = $event->hero_image_path
        ? asset('storage/'.$event->hero_image_path)
        : asset($fallbacks[(int) abs(crc32($event->slug)) % count($fallbacks)]);
@endphp

<x-layouts::site :title="$event->title">
    {{-- HERO --}}
    <section class="relative isolate overflow-hidden bg-brand-900 text-cream-50">
        <img src="{{ $heroImage }}" alt="{{ $event->title }}" class="absolute inset-0 -z-20 h-full w-full object-cover" fetchpriority="high">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-brand-900/90 via-brand-900/80 to-brand-800/70"></div>
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-gold-400">
                {{ $event->starts_at?->format('F j, Y') }}
            </p>
            <h1 class="mt-3 max-w-4xl font-serif text-4xl leading-tight sm:text-5xl">
                {{ $event->title }}
            </h1>
            <p class="mt-4 flex items-center gap-1.5 text-xs text-cream-50/80">
                <svg class="size-3.5 text-cream-50/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4-3.5-7-7-7-11a7 7 0 0 1 14 0c0 4-3 7.5-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                {{ $event->location }}@if (filled($event->country)), {{ $event->country }}@endif
            </p>
            <div class="mt-8">
                <x-site.event-actions :event="$event" variant="dark" />
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.6fr_1fr] lg:px-8">
            {{-- LEFT: content --}}
            <div class="space-y-12">
                <div>
                    <h2 class="reveal font-serif text-2xl text-brand-900">Event Overview</h2>
                    <div class="mt-4 space-y-4 text-sm leading-relaxed text-ink-500">
                        @foreach (preg_split('/\R{2,}/', trim($event->description)) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </div>

                @if (filled($event->activities))
                    <div>
                        <h2 class="reveal font-serif text-2xl text-brand-900">Program Activities</h2>
                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach (preg_split('/\R/', trim($event->activities)) as $line)
                                @if (filled($line))
                                    <li class="flex items-start gap-3 rounded-xl border border-brand-100 bg-white px-4 py-3 text-sm text-brand-800">
                                        <span class="mt-1 size-1.5 shrink-0 rounded-full bg-gold-500"></span>
                                        <span>{{ $line }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (filled($event->expected_impact))
                    <div>
                        <h2 class="reveal font-serif text-2xl text-brand-900">Expected Impact</h2>
                        <p class="mt-4 text-sm leading-relaxed text-ink-500">{{ $event->expected_impact }}</p>
                    </div>
                @endif

                @if (filled($event->volunteer_opportunities))
                    <div>
                        <h2 class="reveal font-serif text-2xl text-brand-900">Volunteer Opportunities</h2>
                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach (preg_split('/\R/', trim($event->volunteer_opportunities)) as $line)
                                @if (filled($line))
                                    <li class="flex items-start gap-3 rounded-xl border border-brand-100 bg-white px-4 py-3 text-sm text-brand-800">
                                        <span class="mt-1 size-1.5 shrink-0 rounded-full bg-brand-500"></span>
                                        <span>{{ $line }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        <a href="{{ route('volunteer') }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-gold-500" wire:navigate>
                            Apply to volunteer for this event <span aria-hidden="true">→</span>
                        </a>
                    </div>
                @endif

                @if ($event->images->isNotEmpty())
                    <div>
                        <h2 class="reveal font-serif text-2xl text-brand-900">Photo Gallery</h2>
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($event->images as $image)
                                <figure class="aspect-square overflow-hidden rounded-xl bg-cream-200">
                                    <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->caption ?? $event->title }}" class="h-full w-full object-cover">
                                </figure>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT: donation sidebar --}}
            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold-500">Fundraising</p>
                    @if ($goal > 0)
                        <p class="mt-3 font-serif text-3xl text-brand-900">${{ number_format($raised / 100) }}</p>
                        <p class="mt-1 text-xs text-ink-500">raised of ${{ number_format($goal / 100) }} goal</p>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-brand-100">
                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-ink-500">{{ $percent }}% funded</p>
                    @else
                        <p class="mt-3 text-sm text-ink-500">
                            This event does not have a public fundraising goal.
                        </p>
                    @endif

                    <div class="mt-6">
                        <x-site.event-actions :event="$event" variant="light" :stack="true" />
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-100 bg-cream-100 p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold-500">Event Details</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">Date</dt>
                            <dd class="text-right font-medium text-brand-900">{{ $event->starts_at?->format('F j, Y') }}</dd>
                        </div>
                        @if ($event->ends_at && $event->ends_at->ne($event->starts_at))
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-500">Ends</dt>
                                <dd class="text-right font-medium text-brand-900">{{ $event->ends_at->format('F j, Y') }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">Location</dt>
                            <dd class="text-right font-medium text-brand-900">{{ $event->location }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">Country</dt>
                            <dd class="text-right font-medium text-brand-900">{{ $event->country }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </section>

    <livewire:site.newsletter-signup source="event:{{ $event->slug }}" />
</x-layouts::site>
