<x-layouts::site title="Media Gallery">
    @php
        $usedCategories = $albums->pluck('category')->filter()->unique();
        $categoryOptions = collect(\App\Enums\MediaCategory::options())->only($usedCategories->all());
        $years = $albums->pluck('year')->filter()->unique()->sortDesc()->values();
        $countries = $albums->pluck('country')->filter()->unique()->sort()->values();
        $albumMeta = $albums->map(fn ($album) => [
            'key' => $album['key'],
            'title' => mb_strtolower($album['title']),
            'category' => $album['category'],
            'year' => $album['year'],
            'country' => $album['country'],
            'date' => $album['date']?->timestamp ?? 0,
            'count' => $album['photos']->count(),
        ])->values();
        $totalPhotos = $albums->sum(fn ($album) => $album['photos']->count());
        $fallbackPhotos = collect([
            ['about/team-group.jpg', 'The foundation team and partners'],
            ['school-donation.jpg', 'School supplies distribution day'],
            ['outreach-1.jpg', 'Volunteers at the outreach kick-off'],
            ['outreach-2.webp', 'Family check-in at the mobile clinic'],
            ['outreach-3.webp', 'Widows empowerment workshop'],
            ['outreach-4.webp', 'Volunteers preparing care packs'],
        ]);
    @endphp

    <x-site.page-hero
        title="Moments from our work in action."
        breadcrumb="Media Gallery"
        image="images/foundation/school-donation.jpg"
        alt="Children holding donated school books"
    >
        Every outreach, donation drive and programme has its own album. Pick one to see all its photos.
    </x-site.page-hero>

    {{-- ALBUM INDEX --}}
    <section
        class="section"
        aria-labelledby="gallery-heading"
        x-data="{
            albums: @js($albumMeta),
            q: '',
            year: '',
            country: '',
            category: 'all',
            sort: 'newest',
            get shown() {
                const q = this.q.trim().toLowerCase();
                const list = this.albums.filter(a =>
                    (! q || a.title.includes(q))
                    && (! this.year || String(a.year) === this.year)
                    && (! this.country || a.country === this.country)
                    && (this.category === 'all' || a.category === this.category));
                const by = {
                    newest: (a, b) => b.date - a.date,
                    oldest: (a, b) => a.date - b.date,
                    photos: (a, b) => b.count - a.count || b.date - a.date,
                    az: (a, b) => a.title.localeCompare(b.title),
                }[this.sort];
                return list.sort(by).map(a => a.key);
            },
            isShown(key) { return this.shown.includes(key) },
            orderOf(key) { return this.shown.indexOf(key) },
            get filtered() { return this.q || this.year || this.country || this.category !== 'all' },
            reset() { this.q = ''; this.year = ''; this.country = ''; this.category = 'all' },
        }"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">Gallery</p>
                    <h2 id="gallery-heading" class="section-title mt-3 text-heading">Browse our albums</h2>
                </div>
                @if ($albums->isNotEmpty())
                    <p class="text-sm text-ink-500" aria-live="polite">
                        Showing <strong class="font-semibold text-heading" x-text="shown.length">{{ $albums->count() }}</strong> of {{ $albums->count() }} {{ Str::plural('album', $albums->count()) }} · {{ $totalPhotos }} {{ Str::plural('item', $totalPhotos) }}
                    </p>
                @endif
            </div>

            @if ($albums->isNotEmpty())
                {{-- Filter bar --}}
                <div class="mt-8 rounded-3xl border border-cream-300 bg-surface p-4 shadow-[0_20px_50px_-35px_rgba(0,4,78,0.4)] sm:p-5">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.6fr)_repeat(3,minmax(0,1fr))]">
                        <label class="relative block">
                            <span class="sr-only">Search albums</span>
                            <svg class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                            <input type="search" x-model="q" placeholder="Search albums…" class="w-full rounded-full border border-cream-300 bg-cream-50 py-3 pl-11 pr-4 text-sm text-heading placeholder-ink-500 focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                        </label>
                        <label class="block">
                            <span class="sr-only">Year</span>
                            <select x-model="year" class="w-full rounded-full border border-cream-300 bg-cream-50 px-4 py-3 text-sm text-heading focus:border-heading focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                                <option value="">All years</option>
                                @foreach ($years as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="sr-only">Country</span>
                            <select x-model="country" class="w-full rounded-full border border-cream-300 bg-cream-50 px-4 py-3 text-sm text-heading focus:border-heading focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                                <option value="">All countries</option>
                                @foreach ($countries as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="sr-only">Sort</span>
                            <select x-model="sort" class="w-full rounded-full border border-cream-300 bg-cream-50 px-4 py-3 text-sm text-heading focus:border-heading focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                                <option value="newest">Newest first</option>
                                <option value="oldest">Oldest first</option>
                                <option value="photos">Most photos</option>
                                <option value="az">A to Z</option>
                            </select>
                        </label>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2" role="group" aria-label="Programme">
                        <button type="button" @click="category = 'all'" :aria-pressed="category === 'all'" :class="category === 'all' ? 'border-heading bg-heading text-surface' : 'border-cream-300 bg-surface text-ink-700 hover:border-heading'" class="rounded-full border px-4 py-2 text-sm font-medium transition">All programmes</button>
                        @foreach ($categoryOptions as $value => $label)
                            <button type="button" @click="category = '{{ $value }}'" :aria-pressed="category === '{{ $value }}'" :class="category === '{{ $value }}' ? 'border-heading bg-heading text-surface' : 'border-cream-300 bg-surface text-ink-700 hover:border-heading'" class="rounded-full border px-4 py-2 text-sm font-medium transition">{{ $label }}</button>
                        @endforeach
                        <button type="button" x-show="filtered" x-cloak @click="reset()" class="ml-auto text-sm font-semibold text-label underline-offset-4 hover:underline">Clear filters</button>
                    </div>
                </div>

                {{-- One card per album: its cover photo opens the album's own gallery --}}
                <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($albums as $album)
                        @php $cover = $album['photos']->first(); @endphp
                        <li
                            class="glass-in"
                            style="--i: {{ $loop->index }}"
                            x-show="isShown('{{ $album['key'] }}')"
                            x-transition:enter="transition duration-500 ease-out"
                            x-transition:enter-start="opacity-0 scale-95 blur-md"
                            x-transition:enter-end="opacity-100 scale-100 blur-0"
                            x-transition:leave="transition duration-300 ease-in"
                            x-transition:leave-start="opacity-100 scale-100 blur-0"
                            x-transition:leave-end="opacity-0 scale-95 blur-md"
                            :style="`order: ${orderOf('{{ $album['key'] }}')}; --i: {{ $loop->index }}`"
                        >
                            <a href="{{ $album['gallery_url'] }}" class="tilt group relative block overflow-hidden rounded-3xl bg-cream-200 shadow-[0_24px_50px_-30px_rgba(0,4,78,0.55)]" wire:navigate>
                                <div class="relative aspect-[4/5]">
                                    <img src="{{ $cover['thumb'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                    <span class="absolute inset-0 bg-gradient-to-t from-[#00044e]/95 via-[#00044e]/35 to-transparent" aria-hidden="true"></span>

                                    <span class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-black/55 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="m21 17-5-5-9 9"/></svg>
                                        {{ $album['photos']->count() }}
                                    </span>
                                    @if ($album['date'])
                                        <span class="absolute left-4 top-4 rounded-2xl bg-white/95 px-3 py-2 text-center leading-none text-heading shadow">
                                            <span class="block font-serif text-2xl font-bold">{{ $album['date']->format('d') }}</span>
                                            <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-500">{{ $album['date']->format('M Y') }}</span>
                                        </span>
                                    @endif

                                    <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold-300">{{ \App\Enums\MediaCategory::from($album['category'])->label() }}</p>
                                        <h3 class="mt-2 font-serif text-2xl font-bold leading-tight">{{ $album['title'] }}</h3>
                                        @if ($album['place'])
                                            <p class="mt-2 flex items-center gap-1.5 text-sm text-white/80">
                                                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                                {{ $album['place'] }}
                                            </p>
                                        @endif
                                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold">
                                            View gallery
                                            <span class="grid size-8 place-items-center rounded-full bg-white/15 ring-1 ring-white/30 transition group-hover:translate-x-1 group-hover:bg-gold-500 group-hover:ring-gold-500" aria-hidden="true">→</span>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div x-show="shown.length === 0" x-cloak class="mt-10 rounded-3xl border border-dashed border-cream-300 p-12 text-center">
                    <p class="font-serif text-2xl font-bold text-heading">No albums match</p>
                    <p class="mt-2 text-sm text-ink-500">Try another year, country or programme.</p>
                    <button type="button" class="btn btn-secondary mt-5" @click="reset()">Clear filters</button>
                </div>
            @else
                {{-- No albums yet: show foundation photographs so the page is never empty --}}
                <ul class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
                    @foreach ($fallbackPhotos as [$file, $caption])
                        <li class="relative overflow-hidden rounded-2xl bg-cream-200">
                            <img src="{{ asset('images/foundation/'.$file) }}" alt="{{ $caption }}" loading="lazy" class="aspect-[4/3] h-full w-full object-cover">
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 pb-2.5 pt-8 text-xs text-white">{{ $caption }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- CTA STRIP --}}
    <section class="section bg-brand-900 text-white">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 sm:px-6 lg:flex-row lg:px-8">
            <div>
                <p class="eyebrow eyebrow-light">More to come</p>
                <h2 class="section-title mt-2">See the journey unfold live.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80">
                    Follow @the_fifiawotofoundation on Instagram for fresh photos and reels as our outreach programmes happen.
                </p>
            </div>
            <a href="{{ config('social.instagram') }}" target="_blank" rel="noopener" class="btn btn-primary btn-on-dark btn-lg shrink-0">
                Follow on Instagram →
            </a>
        </div>
    </section>

    <livewire:site.newsletter-signup source="media" />
</x-layouts::site>
