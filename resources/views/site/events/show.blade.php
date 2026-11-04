@php
    $raised = (int) ($event->raised_cents ?? $event->raisedCents());
    $goal = (int) ($event->goal_cents ?? 0);
    $percent = $goal > 0 ? (int) min(100, round($raised / $goal * 100)) : 0;
    $fallbacks = ['images/foundation/school-donation.jpg', 'images/foundation/outreach-1.jpg', 'images/foundation/outreach-4.webp'];
    $heroImage = \App\Support\MediaUrl::for($event->hero_image_path ?: $fallbacks[(int) abs(crc32($event->slug)) % count($fallbacks)]);
@endphp

<x-layouts::site :title="$event->title" :description="$event->description" :image="$heroImage">
    {{-- HERO --}}
    <section class="relative isolate overflow-hidden bg-brand-900 text-paper">
        <img src="{{ $heroImage }}" alt="{{ $event->title }}" class="absolute inset-0 -z-20 h-full w-full object-cover" fetchpriority="high">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-brand-900/90 via-brand-900/80 to-brand-800/70"></div>
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-gold-400">
                {{ $event->starts_at?->translatedFormat('F j, Y') }}
            </p>
            <h1 class="mt-3 max-w-4xl font-serif text-4xl leading-tight sm:text-5xl">
                {{ $event->title }}
            </h1>
            <p class="mt-4 flex items-center gap-1.5 text-xs text-paper/80">
                <svg class="size-3.5 text-paper/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4-3.5-7-7-7-11a7 7 0 0 1 14 0c0 4-3 7.5-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                {{ $event->location }}@if (filled($event->country)), {{ $event->country }}@endif
            </p>
            <div class="mt-8">
                <x-site.event-actions :event="$event" variant="dark" />
            </div>
        </div>
    </section>

    <section class="section">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1.6fr_1fr] lg:px-8">
            {{-- LEFT: content --}}
            <div class="space-y-12">
                <div>
                    <h2 class="reveal font-serif text-2xl text-heading">{{ __('Event Overview') }}</h2>
                    <div class="mt-4 space-y-4 text-sm leading-relaxed text-ink-500">
                        @foreach (preg_split('/\R{2,}/', trim($event->description)) as $paragraph)
                            <p>{{ __($paragraph) }}</p>
                        @endforeach
                    </div>
                </div>

                @if (filled($event->activities))
                    <div>
                        <h2 class="reveal font-serif text-2xl text-heading">{{ __('Program Activities') }}</h2>
                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach (preg_split('/\R/', trim($event->activities)) as $line)
                                @if (filled($line))
                                    <li class="flex items-start gap-3 rounded-xl border border-brand-100 bg-surface px-4 py-3 text-sm text-heading-soft">
                                        <span class="mt-1 size-1.5 shrink-0 rounded-full bg-gold-500"></span>
                                        <span>{{ __($line) }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (filled($event->expected_impact))
                    <div>
                        <h2 class="reveal font-serif text-2xl text-heading">{{ __('Expected Impact') }}</h2>
                        <p class="mt-4 text-sm leading-relaxed text-ink-500">{{ $event->expected_impact }}</p>
                    </div>
                @endif

                @if (filled($event->volunteer_opportunities))
                    <div>
                        <h2 class="reveal font-serif text-2xl text-heading">{{ __('Volunteer Opportunities') }}</h2>
                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach (preg_split('/\R/', trim($event->volunteer_opportunities)) as $line)
                                @if (filled($line))
                                    <li class="flex items-start gap-3 rounded-xl border border-brand-100 bg-surface px-4 py-3 text-sm text-heading-soft">
                                        <span class="mt-1 size-1.5 shrink-0 rounded-full bg-brand-500"></span>
                                        <span>{{ __($line) }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        <a href="{{ route('volunteer') }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-label hover:text-gold-500" wire:navigate>
                            {{ __('Apply to volunteer for this event') }} <span aria-hidden="true">→</span>
                        </a>
                    </div>
                @endif

                @if ($gallery)
                    @php $preview = $gallery['photos']->take(6); @endphp
                    <div>
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <h2 class="reveal font-serif text-2xl text-heading">{{ __('Photo Gallery') }}</h2>
                            <a href="{{ $gallery['gallery_url'] }}" class="text-sm font-semibold text-label hover:text-gold-500" wire:navigate>
                                {{ __('View full gallery (:v1)', ['v1' => $gallery['photos']->count()]) }} <span aria-hidden="true">→</span>
                            </a>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($preview as $item)
                                <a href="{{ $gallery['gallery_url'] }}" class="group relative block aspect-square overflow-hidden rounded-xl bg-cream-200" wire:navigate>
                                    <img src="{{ $item['thumb'] }}" alt="{{ $item['caption'] ?? $event->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                    @if ($item['type'] === 'video')
                                        <span class="absolute inset-0 grid place-items-center bg-black/25" aria-hidden="true"><span class="grid size-10 place-items-center rounded-full bg-white/90 text-heading"><svg class="ml-0.5 size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span></span>
                                    @endif
                                    @if ($loop->last && $gallery['photos']->count() > $preview->count())
                                        <span class="absolute inset-0 grid place-items-center bg-brand-900/70 font-semibold text-white">{{ __('+:v1 more', ['v1' => $gallery['photos']->count() - $preview->count()]) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT: donation sidebar --}}
            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl border border-brand-100 bg-surface p-6 shadow-sm">
                    <p class="eyebrow">{{ __('Fundraising') }}</p>
                    @if ($goal > 0)
                        <p class="mt-3 font-serif text-3xl text-heading">{{ \App\Support\Money::fromCents((int) $raised) }}</p>
                        <p class="mt-1 text-xs text-ink-500">{{ __('raised of :v1 goal', ['v1' => \App\Support\Money::fromCents((int) $goal)]) }}</p>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-brand-100">
                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-ink-500">{{ __(':v1% funded', ['v1' => $percent]) }}</p>
                    @else
                        <p class="mt-3 text-sm text-ink-500">
                            {{ __('This event does not have a public fundraising goal.') }}
                        </p>
                    @endif

                    <div class="mt-6">
                        <x-site.event-actions :event="$event" variant="light" :stack="true" />
                    </div>
                </div>

                @if ($event->isOpenForRegistration())
                    <livewire:site.event-registration :event="$event" />
                @endif

                <div class="rounded-2xl border border-brand-100 bg-cream-100 p-6">
                    <p class="eyebrow">{{ __('Event Details') }}</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">{{ __('Date') }}</dt>
                            <dd class="text-right font-medium text-heading">{{ $event->starts_at?->translatedFormat('F j, Y') }}</dd>
                        </div>
                        @if ($event->ends_at && $event->ends_at->ne($event->starts_at))
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-500">{{ __('Ends') }}</dt>
                                <dd class="text-right font-medium text-heading">{{ $event->ends_at->translatedFormat('F j, Y') }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">{{ __('Location') }}</dt>
                            <dd class="text-right font-medium text-heading">{{ $event->location }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-ink-500">{{ __('Country') }}</dt>
                            <dd class="text-right font-medium text-heading">{{ $event->country }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </section>

    <livewire:site.newsletter-signup source="event:{{ $event->slug }}" />
</x-layouts::site>
