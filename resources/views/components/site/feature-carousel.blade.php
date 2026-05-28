@props([
    'items' => [],
    'cta' => null,
    'autoplayMs' => 5000,
    'icons' => [],
    'noun' => 'item',
    'perPage' => 1,
])

@php
    $items = collect($items)->values();
    $perPage = max(1, min(4, (int) $perPage));

    $defaultIcons = [
        'women'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm0 11v8m-3-3h6m-9-4c0-2 3-3 6-3s6 1 6 3"/>',
        'education'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5 12 5l9 4.5L12 14 3 9.5Zm3 2.5v4.5c0 1.1 2.7 2 6 2s6-.9 6-2V12"/>',
        'support'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35Z"/>',
        'community'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-6a3 3 0 0 1 3-3h2m13 9v-6a3 3 0 0 0-3-3h-2m-3 9v-9m-3 9V8a3 3 0 1 1 6 0v13M9 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm10 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>',
        'global'     => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'compassion' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35Z"/>',
        'service'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-3 6c0-1.66 1.34-3 3-3s3 1.34 3 3v4H9v-4Zm-6-1c0-1.1.9-2 2-2s2 .9 2 2v5H3v-5Zm14 0c0-1.1.9-2 2-2s2 .9 2 2v5h-4v-5Z"/>',
        'empower'    => '<path stroke-linecap="round" stroke-linejoin="round" d="m12 2 3 6 6 .9-4.5 4.2 1 6.4L12 16l-5.5 3.5 1-6.4L3 8.9 9 8z"/>',
        'inclusive'  => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 20c0-3 2.7-5 6-5s6 2 6 5m1-4c2 0 5 1.2 5 4"/>',
        'sustain'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4-2-7-6-7-11 0 0 3 0 7 3 4-3 7-3 7-3 0 5-3 9-7 11Zm0-13v13"/>',
        'outreach'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 20a8 8 0 0 1 16 0M11 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm9-1 3-3-3-3m0 3h-7"/>',
        'mentor'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 19V8.5L12 4l8 4.5V19m-16 0h16m-12 0v-6h8v6"/>',
        'event'      => '<rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18M8 3v4m8-4v4M8 14h2m4 0h2m-8 4h2m4 0h2"/>',
        'admin'      => '<rect x="4" y="3" width="16" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h5"/>',
        'media'      => '<rect x="3" y="6" width="18" height="13" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m10 11 5 2.5L10 16v-5Z"/>',
        'default'    => '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/>',
    ];

    $iconMap = array_merge($defaultIcons, $icons);

    // For multi-card mode, slide the strip by one card at a time. Stop position = total - perPage.
    $totalSlides = $perPage === 1
        ? $items->count()
        : max(1, $items->count() - $perPage + 1);
@endphp

@if ($items->isEmpty())
    <div class="rounded-3xl border border-dashed border-brand-100 bg-white p-10 text-center text-sm text-ink-500">Nothing here yet.</div>
@elseif ($perPage === 1)
    {{-- Single big-card slideshow (used by Volunteer Opportunities, etc.) --}}
    <div
        x-data="{
            active: 0,
            count: {{ $items->count() }},
            autoplay: null,
            next() { this.active = (this.active + 1) % this.count; },
            go(i) { this.active = i; },
            start() { if (this.count > 1) this.autoplay = setInterval(() => this.next(), {{ (int) $autoplayMs }}); },
            stop() { clearInterval(this.autoplay); this.autoplay = null; }
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
        class="relative"
    >
        <div class="overflow-hidden rounded-3xl">
            <div class="relative min-h-[20rem] sm:min-h-[18rem]">
                @foreach ($items as $i => $item)
                    @php
                        $iconKey = $item['icon'] ?? 'default';
                        $iconSvg = $iconMap[$iconKey] ?? $iconMap['default'];
                    @endphp
                    <article
                        x-show="active === {{ $i }}"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0 translate-y-3"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200 absolute inset-0"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-3"
                        class="relative grid gap-8 overflow-hidden rounded-3xl bg-brand-900 p-10 text-white shadow-[0_18px_50px_-20px_rgba(0,4,78,0.45)] sm:grid-cols-[auto_1fr] sm:p-14"
                    >
                        <div class="pointer-events-none absolute -right-32 top-0 size-72 rounded-full bg-gold-500/20 blur-3xl"></div>
                        <div class="pointer-events-none absolute -left-24 bottom-0 size-64 rounded-full bg-brand-500/40 blur-3xl"></div>
                        <div class="relative">
                            <div class="grid size-20 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/25 backdrop-blur">
                                <svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $iconSvg !!}</svg>
                            </div>
                        </div>
                        <div class="relative flex flex-col justify-center">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-gold-300">{{ ucfirst($noun) }} {{ $i + 1 }} of {{ $items->count() }}</p>
                            <h3 class="mt-3 font-serif text-3xl font-bold leading-tight sm:text-4xl">{{ $item['title'] }}</h3>
                            <p class="mt-4 max-w-xl text-base leading-relaxed text-white/80">{{ $item['copy'] }}</p>
                            @if ($cta)
                                <a href="{{ $cta[1] }}" class="mt-6 inline-flex items-center gap-1.5 self-start rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-gold-500 hover:ring-gold-500" wire:navigate>
                                    {{ $cta[0] }}
                                    <span aria-hidden="true">→</span>
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        @if ($items->count() > 1)
            <div class="mt-6 flex justify-center gap-2" role="tablist">
                @foreach ($items as $i => $item)
                    <button
                        type="button"
                        @click="go({{ $i }})"
                        :class="active === {{ $i }} ? 'w-10 bg-brand-900' : 'w-3 bg-brand-100 hover:bg-brand-300'"
                        class="h-3 rounded-full transition-all"
                        aria-label="Show {{ $item['title'] }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
@else
    {{-- Horizontal-sliding strip showing $perPage cards at a time --}}
    @php
        $widthClass = match ($perPage) {
            2 => 'w-full sm:w-1/2',
            3 => 'w-full sm:w-1/2 lg:w-1/3',
            4 => 'w-full sm:w-1/2 lg:w-1/4',
            default => 'w-full',
        };
    @endphp
    <div
        x-data="{
            active: 0,
            stops: {{ $totalSlides }},
            perPage: {{ $perPage }},
            autoplay: null,
            next() { this.active = (this.active + 1) % this.stops; },
            go(i) { this.active = i; },
            start() { if (this.stops > 1) this.autoplay = setInterval(() => this.next(), {{ (int) $autoplayMs }}); },
            stop() { clearInterval(this.autoplay); this.autoplay = null; }
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
        class="relative"
    >
        {{-- overflow-x-clip lets the row pan horizontally but lets card shadows + hover lifts spill vertically. --}}
        <div class="overflow-x-clip py-4">
            {{-- Inner row uses negative margin + per-card padding so the visible card width equals (100%/perPage). --}}
            <div
                class="-mx-2.5 flex transition-transform duration-700 ease-out"
                :style="`transform: translateX(calc(-${active} * (100% / ${perPage})))`"
            >
                @foreach ($items as $i => $item)
                    @php
                        $iconKey = $item['icon'] ?? 'default';
                        $iconSvg = $iconMap[$iconKey] ?? $iconMap['default'];
                    @endphp
                    <div
                        class="{{ $widthClass }} shrink-0 px-2.5"
                        :class="(active <= {{ $i }} && {{ $i }} < active + perPage) ? 'opacity-100' : 'opacity-60'"
                    >
                        <article class="group relative h-full overflow-hidden rounded-3xl bg-white shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100 transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_50px_-20px_rgba(0,4,78,0.25)] hover:ring-brand-200">
                            <div class="relative flex h-full flex-col p-7">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-900 via-brand-500 to-gold-500"></div>

                                <div class="grid size-14 place-items-center rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 text-brand-700 ring-1 ring-brand-100 transition group-hover:from-brand-900 group-hover:to-brand-700 group-hover:text-white">
                                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $iconSvg !!}</svg>
                                </div>

                                <h3 class="mt-7 font-serif text-xl font-bold leading-snug text-ink-900">{{ $item['title'] }}</h3>
                                <p class="mt-3 flex-1 text-sm leading-relaxed text-ink-500">{{ $item['copy'] }}</p>

                                @if ($cta)
                                    <a href="{{ $cta[1] }}" class="mt-6 inline-flex items-center gap-1.5 self-start text-sm font-semibold text-brand-700 transition group-hover:text-gold-500" wire:navigate>
                                        {{ $cta[0] }}
                                        <span aria-hidden="true" class="transition group-hover:translate-x-0.5">→</span>
                                    </a>
                                @endif

                                <span class="absolute bottom-5 right-6 font-serif text-4xl font-bold text-brand-50 transition group-hover:text-brand-100">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($totalSlides > 1)
            <div class="mt-8 flex items-center justify-center gap-2" role="tablist" aria-label="Carousel pagination">
                @for ($i = 0; $i < $totalSlides; $i++)
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
@endif
