@props([
    'testimonials' => [],
    'heading' => 'Stories of Impact',
    'kicker' => 'Testimonials',
    'subtitle' => 'Voices from the families, volunteers, and partners who walk this journey with us.',
    'perView' => 1,
])

@php
    $items = collect($testimonials)->values();
    $perView = max(1, (int) $perView);

    // Sliding-window pages: one page per starting offset, wrapping at the end so the
    // first card sits next to the last card instead of leaving a blank slot.
    $count = $items->count();
    if ($count === 0) {
        $pages = collect();
    } elseif ($count <= $perView) {
        $pages = collect([$items]);
    } else {
        $pages = collect(range(0, $count - 1))->map(fn ($start) => collect(range(0, $perView - 1))->map(
            fn ($offset) => $items[($start + $offset) % $count]
        ));
    }
@endphp

@if ($items->isEmpty())
    <div class="rounded-3xl border border-dashed border-brand-100 bg-white p-10 text-center text-sm text-ink-500">No testimonials yet.</div>
@else
    <div
        x-data="{
            active: 0,
            count: {{ $pages->count() }},
            autoplay: null,
            next() { this.active = (this.active + 1) % this.count; },
            prev() { this.active = (this.active - 1 + this.count) % this.count; },
            go(i) { this.active = i; },
            start() {
                if (this.count <= 1) return;
                this.autoplay = setInterval(() => this.next(), 7000);
            },
            stop() { clearInterval(this.autoplay); this.autoplay = null; }
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
        @keydown.arrow-left.window="prev()"
        @keydown.arrow-right.window="next()"
        class="relative"
    >
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">{{ $kicker }}</p>
            <h2 id="testimonials-heading" class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl">{{ $heading }}</h2>
            @if ($subtitle)
                <p class="mt-4 text-sm leading-relaxed text-ink-500">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="mt-12 grid items-center gap-4 sm:grid-cols-[auto_1fr_auto]">
            <button
                type="button"
                @click="prev()"
                aria-label="Previous testimonials"
                class="grid size-11 place-items-center justify-self-start rounded-full border border-brand-100 bg-white text-brand-700 shadow-sm transition hover:border-brand-700 hover:bg-brand-700 hover:text-white sm:size-12"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>

            <div class="overflow-hidden">
                <div class="relative" style="min-height: 22rem;">
                    @foreach ($pages as $pageIndex => $pageItems)
                        <div
                            x-show="active === {{ $pageIndex }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 translate-x-6"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            x-transition:leave="transition ease-in duration-200 absolute inset-0"
                            x-transition:leave-start="opacity-100 translate-x-0"
                            x-transition:leave-end="opacity-0 -translate-x-6"
                            @class([
                                'mx-auto grid gap-5',
                                'max-w-3xl' => $perView === 1,
                                'max-w-6xl md:grid-cols-2' => $perView === 2,
                                'max-w-6xl md:grid-cols-3' => $perView === 3,
                            ])
                        >
                            @foreach ($pageItems as $testimonial)
                                @php
                                    $photo = $testimonial->photo_path ?? null;
                                    $initials = collect(preg_split('/\s+/', trim((string) $testimonial->author_name)))
                                        ->filter()
                                        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                @endphp
                                <figure
                                    @class([
                                        'flex h-full flex-col justify-between rounded-3xl border border-brand-100 bg-white shadow-[0_18px_60px_-25px_rgba(0,4,78,0.25)]',
                                        'items-center px-6 py-10 text-center sm:px-12 sm:py-12' => $perView === 1,
                                        'px-6 py-8 text-left sm:px-8 sm:py-10' => $perView > 1,
                                    ])
                                >
                                    <div>
                                        <svg @class([
                                            'text-gold-400',
                                            'size-9' => $perView === 1,
                                            'size-7' => $perView > 1,
                                        ]) viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M9 7H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2H5V9h4V7zm10 0h-4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2h-4V9h4V7z"/>
                                        </svg>
                                        <blockquote @class([
                                            'font-serif leading-relaxed text-brand-900',
                                            'mt-5 text-xl sm:text-2xl' => $perView === 1,
                                            'mt-4 text-lg' => $perView > 1,
                                        ])>
                                            &ldquo;{{ $testimonial->quote }}&rdquo;
                                        </blockquote>
                                    </div>
                                    <figcaption @class([
                                        'flex items-center gap-3',
                                        'mt-8' => $perView === 1,
                                        'mt-6 border-t border-cream-300 pt-5' => $perView > 1,
                                    ])>
                                        @if (filled($photo))
                                            <img
                                                src="{{ str_starts_with($photo, 'http') ? $photo : asset('storage/'.ltrim($photo, '/')) }}"
                                                alt="{{ $testimonial->author_name }}"
                                                class="aspect-square size-12 rounded-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <span class="grid aspect-square size-12 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">
                                                {{ $initials ?: mb_substr((string) $testimonial->author_role, 0, 1) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0 flex-1 text-left">
                                            <p class="font-serif text-base font-bold text-ink-900">{{ $testimonial->author_name }}</p>
                                            <p class="text-sm text-ink-500">{{ $testimonial->author_role }}</p>
                                        </div>
                                        @if (($testimonial->featured ?? false))
                                            <span class="shrink-0 text-[10px] font-bold uppercase tracking-[0.24em] text-ink-500">Featured</span>
                                        @endif
                                    </figcaption>
                                </figure>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <button
                type="button"
                @click="next()"
                aria-label="Next testimonials"
                class="grid size-11 place-items-center justify-self-end rounded-full border border-brand-100 bg-white text-brand-700 shadow-sm transition hover:border-brand-700 hover:bg-brand-700 hover:text-white sm:size-12"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>

        @if ($pages->count() > 1)
            <div class="mt-6 flex justify-center gap-2">
                @foreach ($pages as $pageIndex => $pageItems)
                    <button
                        type="button"
                        @click="go({{ $pageIndex }})"
                        :class="active === {{ $pageIndex }} ? 'w-8 bg-brand-700' : 'w-2.5 bg-brand-100 hover:bg-brand-300'"
                        class="h-2.5 rounded-full transition-all"
                        aria-label="Show testimonials page {{ $pageIndex + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
