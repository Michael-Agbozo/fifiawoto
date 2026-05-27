<x-layouts::site title="Media Gallery">
    @php
        // Fallback foundation imagery so the page is never empty even if the admin hasn't
        // uploaded media items yet. Real DB rows take precedence when present.
        $fallbacks = collect([
            (object) ['id' => 'fb-1',  'type' => \App\Enums\MediaType::Image, 'category' => \App\Enums\MediaCategory::CommunityOutreach, 'url' => asset('images/foundation/community-1.jpg'),     'caption' => 'Community gathering in the Volta region'],
            (object) ['id' => 'fb-2',  'type' => \App\Enums\MediaType::Image, 'category' => \App\Enums\MediaCategory::EducationPrograms, 'url' => asset('images/foundation/school-donation.jpg'),  'caption' => 'School supplies distribution day'],
            (object) ['id' => 'fb-3',  'type' => \App\Enums\MediaType::Image, 'category' => \App\Enums\MediaCategory::Volunteers,         'url' => asset('images/foundation/outreach-1.jpg'),       'caption' => 'Volunteers at the outreach kick-off'],
            (object) ['id' => 'fb-4',  'type' => \App\Enums\MediaType::Image, 'category' => \App\Enums\MediaCategory::CommunityOutreach, 'url' => asset('images/foundation/outreach-2.webp'),      'caption' => 'Family check-in at the mobile clinic'],
            (object) ['id' => 'fb-5',  'type' => \App\Enums\MediaType::Image, 'category' => \App\Enums\MediaCategory::Events,             'url' => asset('images/foundation/outreach-3.webp'),      'caption' => 'Widows empowerment workshop'],
            (object) ['id' => 'fb-6',  'type' => \App\Enums\MediaType::Image, 'category' => \App\Enums\MediaCategory::CommunityOutreach, 'url' => asset('images/foundation/community-2.jpg'),     'caption' => 'Town-hall in Greater Accra'],
            (object) ['id' => 'fb-7',  'type' => \App\Enums\MediaType::Image, 'category' => \App\Enums\MediaCategory::Volunteers,         'url' => asset('images/foundation/outreach-4.webp'),      'caption' => 'Volunteers preparing care packs'],
            (object) ['id' => 'fb-8',  'type' => \App\Enums\MediaType::Image, 'category' => \App\Enums\MediaCategory::Events,             'url' => asset('images/foundation/community-3.jpg'),     'caption' => 'Annual gathering with partners'],
        ]);

        // Normalize DB rows + fallbacks into a single shape: { id, type, category, url, caption, event_url, event_title }
        $tiles = $items->isNotEmpty()
            ? $items->map(function ($m) {
                $event = $m->event;
                $linkable = $event
                    && $event->status === \App\Enums\EventStatus::Published
                    && ($event->published_at === null || $event->published_at->lte(now()));

                return (object) [
                    'id' => $m->id,
                    'type' => $m->type,
                    'category' => $m->category,
                    'url' => str_starts_with((string) $m->path, 'http') ? $m->path : asset('storage/'.ltrim((string) $m->path, '/')),
                    'poster' => $m->poster_path ? (str_starts_with($m->poster_path, 'http') ? $m->poster_path : asset('storage/'.ltrim($m->poster_path, '/'))) : null,
                    'caption' => $m->caption ?: $m->category?->label() ?? '',
                    'event_url' => $linkable ? route('events.show', $event) : null,
                    'event_title' => $linkable ? $event->title : null,
                ];
            })
            : $fallbacks->map(fn ($f) => (object) array_merge((array) $f, ['event_url' => null, 'event_title' => null]));

        $categoryOptions = \App\Enums\MediaCategory::options();
    @endphp

    <x-site.page-hero
        title="Moments from our work in action."
        breadcrumb="Media Gallery"
        image="images/foundation/community-2.jpg"
        alt="Foundation outreach gathering"
    >
        Browse images and clips from outreach programmes, education drives, volunteer days, and community events.
    </x-site.page-hero>

    {{-- GALLERY --}}
    <section class="py-16" aria-labelledby="gallery-heading">
        <div
            x-data="{
                category: 'all',
                videoOnly: false,
                isVisible(card) {
                    const cat = card.dataset.category;
                    const type = card.dataset.type;
                    if (this.videoOnly && type !== 'video') return false;
                    if (this.category !== 'all' && cat !== this.category) return false;
                    return true;
                },
                applyFilter() {
                    this.$nextTick(() => {
                        const cards = this.$refs.grid.querySelectorAll('[data-card]');
                        cards.forEach(card => {
                            card.style.display = this.isVisible(card) ? '' : 'none';
                        });
                        const empty = this.$refs.empty;
                        const anyVisible = Array.from(cards).some(c => c.style.display !== 'none');
                        empty.style.display = anyVisible ? 'none' : '';
                    });
                }
            }"
            x-init="applyFilter()"
            class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
        >
            <h2 id="gallery-heading" class="sr-only">Gallery</h2>

            {{-- Filter bar --}}
            <div class="mb-10 flex flex-col gap-4 border-b border-cream-300 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="-mx-1 flex items-center gap-1 overflow-x-auto px-1 sm:flex-wrap sm:overflow-visible">
                    <button
                        type="button"
                        @click="category = 'all'; applyFilter()"
                        :class="category === 'all' ? 'border-brand-900 text-brand-900' : 'border-transparent text-ink-500 hover:text-brand-900'"
                        class="shrink-0 border-b-2 px-3 pb-3 pt-1 text-sm font-semibold transition"
                    >
                        All
                    </button>
                    @foreach ($categoryOptions as $value => $label)
                        <button
                            type="button"
                            @click="category = '{{ $value }}'; applyFilter()"
                            :class="category === '{{ $value }}' ? 'border-brand-900 text-brand-900' : 'border-transparent text-ink-500 hover:text-brand-900'"
                            class="shrink-0 border-b-2 px-3 pb-3 pt-1 text-sm font-semibold transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <label class="inline-flex shrink-0 cursor-pointer select-none items-center gap-3 text-sm font-medium text-ink-700">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        Videos only
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center">
                        <input type="checkbox" x-model="videoOnly" @change="applyFilter()" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-cream-300 transition peer-checked:bg-brand-900"></span>
                        <span class="absolute left-0.5 top-0.5 size-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                    </span>
                </label>
            </div>

            {{-- Masonry gallery --}}
            <div
                x-ref="grid"
                class="columns-1 gap-4 sm:columns-2 lg:columns-3"
            >
                @foreach ($tiles as $tile)
                    @php
                        $isVideo = ($tile->type ?? null) instanceof \App\Enums\MediaType
                            ? $tile->type === \App\Enums\MediaType::Video
                            : (string) $tile->type === 'video';
                        $thumb = $tile->poster ?? $tile->url;
                        // Videos always open the source URL in a new tab. Images
                        // navigate to the related event detail page if there is one;
                        // otherwise the tile is a static image (no link).
                        $tileHref = $isVideo
                            ? $tile->url
                            : ($tile->event_url ?? null);
                        $tileTarget = $isVideo ? '_blank' : '_self';
                        $tileRel = $isVideo ? 'noopener' : null;
                    @endphp
                    <figure
                        data-card
                        data-category="{{ $tile->category?->value }}"
                        data-type="{{ $isVideo ? 'video' : 'image' }}"
                        class="group relative mb-4 block break-inside-avoid overflow-hidden rounded-2xl bg-cream-200 shadow-sm ring-1 ring-cream-300 transition hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        @if ($tileHref)
                            <a href="{{ $tileHref }}" @if ($tileTarget !== '_self') target="{{ $tileTarget }}" @endif @if ($tileRel) rel="{{ $tileRel }}" @endif class="block" @if (! $isVideo) wire:navigate @endif>
                                @if ($thumb)
                                    <img src="{{ $thumb }}" alt="{{ $tile->caption }}" class="block w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                @endif
                                @if ($isVideo)
                                    <div class="absolute inset-0 bg-gradient-to-t from-brand-900/65 via-transparent to-transparent"></div>
                                    <span class="absolute inset-0 grid place-items-center">
                                        <span class="grid size-14 place-items-center rounded-full bg-white/90 text-brand-900 shadow-lg backdrop-blur transition group-hover:bg-gold-500 group-hover:text-white">
                                            <svg class="size-6 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </span>
                                    </span>
                                    <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/60 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-white backdrop-blur">
                                        <svg class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        Video
                                    </span>
                                @endif
                            </a>
                        @else
                            <img src="{{ $tile->url }}" alt="{{ $tile->caption }}" class="block w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                        @endif

                        @if ($tile->category || $tile->event_title)
                            <figcaption class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-brand-900/85 via-brand-900/30 to-transparent p-4 opacity-0 transition group-hover:opacity-100">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($tile->category)
                                        <span class="inline-flex rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-brand-900">{{ $tile->category->label() }}</span>
                                    @endif
                                    @if ($tile->event_title)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gold-500 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-white">
                                            <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75"/></svg>
                                            View event
                                        </span>
                                    @endif
                                </div>
                                @if ($tile->caption)
                                    <p class="mt-2 line-clamp-2 text-sm font-medium text-white">{{ $tile->caption }}</p>
                                @endif
                            </figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>

            <div x-ref="empty" style="display: none;" class="mt-12 rounded-3xl border border-dashed border-cream-300 bg-white p-12 text-center">
                <p class="font-serif text-2xl text-brand-900">No media matches that filter.</p>
                <p class="mt-2 text-sm text-ink-500">Try a different category or turn off the videos-only toggle.</p>
            </div>
        </div>
    </section>

    {{-- CTA STRIP --}}
    <section class="bg-brand-900 py-16 text-white">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 sm:px-6 lg:flex-row lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-gold-300">More to come</p>
                <h2 class="mt-2 font-serif text-3xl sm:text-4xl">See the journey unfold live.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80">
                    Follow @the_fifiawotofoundation on Instagram for fresh photos and reels as our outreach programmes happen.
                </p>
            </div>
            <a href="{{ config('social.instagram') }}" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-gold-500 px-7 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-white hover:text-brand-900">
                Follow on Instagram →
            </a>
        </div>
    </section>

    <livewire:site.newsletter-signup source="media" />
</x-layouts::site>
