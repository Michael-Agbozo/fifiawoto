<x-layouts::site :title="__('Media Gallery')">
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
        :title="__('Moments from our work in action.')"
        :breadcrumb="__('Media Gallery')"
        image="images/foundation/school-donation.jpg"
        :alt="__('Children holding donated school books')"
    >
        {{ __('Every outreach, donation drive and programme has its own album. Pick one to see all its photos.') }}
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
            page: 1,
            perPage: 6,
            init() {
                ['q', 'year', 'country', 'category', 'sort'].forEach(field => this.$watch(field, () => this.page = 1));
            },
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
            get pages() { return Math.max(1, Math.ceil(this.shown.length / this.perPage)) },
            get pageKeys() { return this.shown.slice((this.page - 1) * this.perPage, this.page * this.perPage) },
            get rangeLabel() {
                if (! this.shown.length) return '0';
                const from = (this.page - 1) * this.perPage + 1;
                return `${from}–${Math.min(this.page * this.perPage, this.shown.length)}`;
            },
            goTo(n) {
                this.page = Math.min(Math.max(1, n), this.pages);
                this.$nextTick(() => this.$refs.grid.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            },
            isShown(key) { return this.pageKeys.includes(key) },
            orderOf(key) { return this.pageKeys.indexOf(key) },
            get filtered() { return this.q || this.year || this.country || this.category !== 'all' },
            reset() { this.q = ''; this.year = ''; this.country = ''; this.category = 'all' },
        }"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">{{ __('Gallery') }}</p>
                    <h2 id="gallery-heading" class="section-title mt-3 text-heading">{{ __('Browse our albums') }}</h2>
                </div>
                @if ($albums->isNotEmpty())
                    <p class="text-sm text-ink-500" aria-live="polite">
                        {{ __('Showing') }} <strong class="font-semibold text-heading" x-text="rangeLabel">1–{{ min(6, $albums->count()) }}</strong> {{ __('of') }} <strong class="font-semibold text-heading" x-text="shown.length">{{ $albums->count() }}</strong> {{ Str::plural('album', $albums->count()) }} · {{ $totalPhotos }} {{ Str::plural('item', $totalPhotos) }}
                    </p>
                @endif
            </div>

            @if ($albums->isNotEmpty())
                {{-- Filter bar --}}
                <div class="mt-8 rounded-3xl border border-cream-300 bg-surface p-4 shadow-[0_20px_50px_-35px_rgba(0,4,78,0.4)] sm:p-5">
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-[minmax(0,1.6fr)_repeat(3,minmax(0,1fr))]">
                        <label class="relative col-span-3 block sm:col-span-1">
                            <span class="sr-only">{{ __('Search albums') }}</span>
                            <svg class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                            <input type="search" x-model="q" placeholder="{{ __('Search albums…') }}" class="w-full rounded-full border border-cream-300 bg-cream-50 py-3 pl-11 pr-4 text-sm text-heading placeholder-ink-500 focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                        </label>
                        <label class="block">
                            <span class="sr-only">{{ __('Year') }}</span>
                            <select x-model="year" class="w-full rounded-full border border-cream-300 bg-cream-50 px-4 py-3 text-sm text-heading focus:border-heading focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                                <option value="">{{ __('All years') }}</option>
                                @foreach ($years as $y)
                                    <option value="{{ $y }}">{{ __($y) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="sr-only">{{ __('Country') }}</span>
                            <select x-model="country" class="w-full rounded-full border border-cream-300 bg-cream-50 px-4 py-3 text-sm text-heading focus:border-heading focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                                <option value="">{{ __('All countries') }}</option>
                                @foreach ($countries as $c)
                                    <option value="{{ $c }}">{{ __($c) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="sr-only">{{ __('Sort') }}</span>
                            <select x-model="sort" class="w-full rounded-full border border-cream-300 bg-cream-50 px-4 py-3 text-sm text-heading focus:border-heading focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                                <option value="newest">{{ __('Newest first') }}</option>
                                <option value="oldest">{{ __('Oldest first') }}</option>
                                <option value="photos">{{ __('Most photos') }}</option>
                                <option value="az">{{ __('A to Z') }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('Programme') }}">
                        <button type="button" @click="category = 'all'" :aria-pressed="category === 'all'" :class="category === 'all' ? 'border-heading bg-heading text-surface' : 'border-cream-300 bg-surface text-ink-700 hover:border-heading'" class="rounded-full border px-4 py-2 text-sm font-medium transition">{{ __('All programmes') }}</button>
                        @foreach ($categoryOptions as $value => $label)
                            <button type="button" @click="category = '{{ $value }}'" :aria-pressed="category === '{{ $value }}'" :class="category === '{{ $value }}' ? 'border-heading bg-heading text-surface' : 'border-cream-300 bg-surface text-ink-700 hover:border-heading'" class="rounded-full border px-4 py-2 text-sm font-medium transition">{{ __($label) }}</button>
                        @endforeach
                        <button type="button" x-show="filtered" x-cloak @click="reset()" class="ml-auto text-sm font-semibold text-label underline-offset-4 hover:underline">{{ __('Clear filters') }}</button>
                    </div>
                </div>

                {{-- One card per album: its cover photo opens the album's own gallery --}}
                <ul x-ref="grid" class="mt-8 grid scroll-mt-32 grid-cols-2 gap-3 sm:mt-10 sm:gap-6 lg:grid-cols-3">
                    @foreach ($albums as $album)
                        @php $cover = $album['photos']->first(); @endphp
                        <li
                            class="glass-in"
                            style="--i: {{ $loop->index % 3 }}; order: {{ $loop->index }}"
                            @if ($loop->index >= 6) hidden @endif
                            x-bind:hidden="! isShown('{{ $album['key'] }}')"
                            :style="`order: ${orderOf('{{ $album['key'] }}')}; --i: ${orderOf('{{ $album['key'] }}') % 3}`"
                        >
                            <article class="tilt group relative flex h-full flex-col overflow-hidden rounded-3xl border border-cream-300 bg-surface shadow-sm transition-shadow hover:shadow-[0_24px_50px_-28px_rgba(0,4,78,0.45)]">
                                <div class="relative aspect-[4/3] overflow-hidden bg-cream-200">
                                    <img src="{{ $cover['thumb'] }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                    @if ($album['date'])
                                        <span class="absolute left-2 top-2 rounded-xl bg-white/95 px-2 py-1.5 text-center leading-none text-[#00044e] shadow sm:left-4 sm:top-4 sm:rounded-2xl sm:px-3 sm:py-2">
                                            <span class="block font-serif text-lg font-bold sm:text-2xl">{{ $album['date']->format('d') }}</span>
                                            <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-[0.18em] text-[#5a5a5a]">{{ $album['date']->translatedFormat('M Y') }}</span>
                                        </span>
                                    @endif
                                    <span class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-black/55 px-2 py-1 text-[10px] font-semibold text-white backdrop-blur sm:right-4 sm:top-4 sm:gap-1.5 sm:px-3 sm:py-1.5 sm:text-xs">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="m21 17-5-5-9 9"/></svg>
                                        {{ $album['photos']->count() }}<span class="hidden sm:inline">{{ Str::plural(' item', $album['photos']->count()) }}</span>
                                    </span>
                                    <span class="absolute -bottom-3 left-3 rounded-md bg-brand-900 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white sm:left-6 sm:px-3 sm:text-xs">{{ __($album['kind']) }}</span>
                                </div>
                                <div class="flex flex-1 flex-col p-3 pt-6 sm:p-6 sm:pt-7">
                                    <p class="eyebrow hidden sm:block">{{ __(\App\Enums\MediaCategory::from($album['category'])->label()) }}</p>
                                    <h3 class="font-serif text-base font-bold leading-snug text-heading sm:mt-2 sm:text-2xl">
                                        <a href="{{ $album['gallery_url'] }}" class="after:absolute after:inset-0 hover:text-gold-500" wire:navigate>{{ __($album['title']) }}</a>
                                    </h3>
                                    @if ($album['place'])
                                        <p class="mt-1 flex items-center gap-1.5 text-xs text-ink-500 sm:mt-2 sm:text-sm">
                                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                            {{ __($album['place']) }}
                                        </p>
                                    @endif
                                    <span class="mt-auto hidden items-center justify-between gap-3 border-t border-cream-300 pt-4 text-sm font-semibold text-label transition-colors group-hover:text-gold-500 sm:flex">
                                        {{ __('View gallery') }}
                                        <span class="grid size-9 place-items-center rounded-full bg-cream-100 transition-colors group-hover:bg-gold-500 group-hover:text-white" aria-hidden="true">→</span>
                                    </span>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>

                {{-- Pagination --}}
                <nav x-show="pages > 1" x-cloak class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="{{ __('Album pages') }}">
                    <button type="button" class="btn btn-ghost px-4 py-2.5 text-sm" @click="goTo(page - 1)" :disabled="page === 1" aria-label="{{ __('Previous page') }}">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        {{ __('Previous') }}
                    </button>
                    <template x-for="n in pages" :key="n">
                        <button type="button" @click="goTo(n)" :aria-current="n === page ? 'page' : null" :aria-label="`Page ${n}`"
                            :class="n === page ? 'border-heading bg-heading text-surface' : 'border-cream-300 bg-surface text-ink-700 hover:border-heading hover:text-heading'"
                            class="grid size-11 place-items-center rounded-full border text-sm font-semibold transition" x-text="n"></button>
                    </template>
                    <button type="button" class="btn btn-ghost px-4 py-2.5 text-sm" @click="goTo(page + 1)" :disabled="page === pages" aria-label="{{ __('Next page') }}">
                        {{ __('Next') }}
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </nav>

                <div x-show="shown.length === 0" x-cloak class="mt-10 rounded-3xl border border-dashed border-cream-300 p-12 text-center">
                    <p class="font-serif text-2xl font-bold text-heading">{{ __('No albums match') }}</p>
                    <p class="mt-2 text-sm text-ink-500">{{ __('Try another year, country or programme.') }}</p>
                    <button type="button" class="btn btn-secondary mt-5" @click="reset()">{{ __('Clear filters') }}</button>
                </div>
            @else
                {{-- No albums yet: show foundation photographs so the page is never empty --}}
                <ul class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
                    @foreach ($fallbackPhotos as [$file, $caption])
                        <li class="relative overflow-hidden rounded-2xl bg-cream-200">
                            <img src="{{ asset('images/foundation/'.$file) }}" alt="{{ $caption }}" loading="lazy" class="aspect-[4/3] h-full w-full object-cover">
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 pb-2.5 pt-8 text-xs text-white">{{ __($caption) }}</span>
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
                <p class="eyebrow eyebrow-light">{{ __('More to come') }}</p>
                <h2 class="section-title mt-2">{{ __('See the journey unfold live.') }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80">
                    {{ __('Follow') }} @the{{ __('_fifiawotofoundation on Instagram for fresh photos and reels as our outreach programmes happen.') }}
                </p>
            </div>
            <a href="{{ config('social.instagram') }}" target="_blank" rel="noopener" class="btn btn-primary btn-on-dark btn-lg shrink-0">
                {{ __('Follow on Instagram →') }}
            </a>
        </div>
    </section>

    <livewire:site.newsletter-signup source="media" />
</x-layouts::site>
