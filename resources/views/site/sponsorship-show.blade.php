<x-layouts::site :title="'Meet '.$child->public_display_name" description="Learn about a child’s approved public introduction and explore sponsorship through the Fifiawoto Foundation." :noindex="true">
    <div class="mx-auto max-w-6xl px-6 pb-6 pt-10 sm:px-10">
        <nav aria-label="Breadcrumb">
            <ol class="inline-flex max-w-full flex-wrap items-center gap-1 rounded-full bg-cream-100 py-1 pl-1 pr-4 text-sm ring-1 ring-cream-300">
                <li>
                    <a href="{{ route('home') }}" class="group inline-flex items-center gap-1.5 rounded-full py-0.5 pl-0.5 pr-1.5 text-ink-500 transition hover:text-heading" wire:navigate>
                        <span class="grid size-7 place-items-center rounded-full bg-surface text-heading ring-1 ring-cream-300 transition group-hover:bg-gold-500 group-hover:text-white group-hover:ring-gold-500" aria-hidden="true">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/></svg>
                        </span>
                        Home
                    </a>
                </li>
                <li aria-hidden="true" class="text-ink-500/60"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg></li>
                <li><a href="{{ route('sponsor-a-child') }}#children" class="px-1 text-ink-500 transition hover:text-heading" wire:navigate>Sponsor a Child</a></li>
                <li aria-hidden="true" class="text-ink-500/60"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg></li>
                <li><span class="pl-1 font-semibold text-heading" aria-current="page">{{ $child->public_display_name }}</span></li>
            </ol>
        </nav>
    </div>
    <section aria-labelledby="profile-heading" class="mx-3 rounded-3xl bg-cream-100 px-6 py-8 sm:mx-4 sm:px-10 lg:mx-6 lg:py-12">
        <div class="mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[1.1fr_1fr] lg:gap-16">
            <figure class="overflow-hidden rounded-2xl bg-cream-200">
                <img src="{{ Storage::disk('public')->url($child->public_photo_path) }}" alt="{{ $child->public_display_name }}" class="aspect-[5/4] w-full object-cover object-center">
            </figure>
            <div class="min-w-0 py-3">
                <p class="eyebrow">A future with possibility</p>
                <h1 id="profile-heading" class="mt-5 break-words text-4xl leading-tight text-heading sm:text-5xl lg:text-6xl">Meet {{ $child->public_display_name }}</h1>
                <p class="mt-6 max-w-md text-lg leading-relaxed text-heading-soft">Every connection begins with getting to know someone. Take a moment to read {{ $child->public_display_name }}’s story.</p>
                <dl class="mt-8 grid grid-cols-2 gap-5 border-t border-brand-100 pt-6">
                    <div><dt class="text-xs font-semibold uppercase tracking-widest text-ink-500">Age</dt><dd class="mt-2 text-lg font-semibold text-heading">{{ $child->date_of_birth->age }} years</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-widest text-ink-500">Country</dt><dd class="mt-2 break-words text-lg font-semibold text-heading">{{ $child->public_country }}</dd></div>
                </dl>
            </div>
        </div>
    </section>
    <section aria-labelledby="story-heading" class="mx-auto max-w-6xl px-6 py-14 sm:px-10 sm:py-20">
        <div class="grid gap-8 lg:grid-cols-[1fr_2fr] lg:gap-16">
            <header>
                <p class="eyebrow">In their own chapter</p>
                <h2 id="story-heading" class="section-title text-heading mt-4 break-words">A little more about {{ $child->public_display_name }}</h2>
                <p class="mt-5 max-w-xs text-sm leading-7 text-ink-500">
                    @if (Str::endsWith($child->public_display_name, '(Demo)'))
                        An illustrative preview of a longer child profile. Real introductions are shared only with guardian consent.
                    @else
                        An introduction shared with guardian consent. Our team coordinates every conversation with care for each family’s privacy.
                    @endif
                </p>
            </header>
            <article aria-label="{{ $child->public_display_name }}’s story" class="min-w-0" x-data="{ activeTab: 'overview', tabs: ['overview', 'community', 'country'], selectTab(tab) { this.activeTab = tab; this.$nextTick(() => this.$refs[tab].focus()); } }">
                <div role="tablist" aria-label="About {{ $child->public_display_name }}" aria-orientation="horizontal" class="flex flex-nowrap border-b border-brand-100" @keydown.arrow-right.prevent="selectTab(tabs[(tabs.indexOf(activeTab) + 1) % tabs.length])" @keydown.arrow-left.prevent="selectTab(tabs[(tabs.indexOf(activeTab) + tabs.length - 1) % tabs.length])" @keydown.home.prevent="selectTab(tabs[0])" @keydown.end.prevent="selectTab(tabs[tabs.length - 1])">
                    @foreach (['overview' => 'Overview', 'community' => 'Community', 'country' => 'Country'] as $tab => $label)
                        <button type="button" role="tab" id="story-tab-{{ $tab }}" aria-controls="story-panel-{{ $tab }}" x-ref="{{ $tab }}" :aria-selected="activeTab === '{{ $tab }}'" :tabindex="activeTab === '{{ $tab }}' ? 0 : -1" @click="activeTab = '{{ $tab }}'" :class="activeTab === '{{ $tab }}' ? 'border-gold-500 text-label' : 'border-transparent text-ink-500'" class="min-w-0 flex-1 whitespace-nowrap border-b-2 px-2 py-4 text-sm font-semibold transition-colors hover:text-gold-500 focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-gold-500 sm:px-6 sm:text-base">{{ $label }}</button>
                    @endforeach
                </div>
                @foreach ([
                    ['overview', $child->public_story, null],
                    ['community', $child->public_community_story, 'Our team can share more about the community and the support available to children and their families.'],
                    ['country', $child->public_country_story, 'Our team can share more about life in this country and the foundation’s work there.'],
                ] as [$tab, $story, $fallback])
                    <div role="tabpanel" id="story-panel-{{ $tab }}" aria-labelledby="story-tab-{{ $tab }}" tabindex="0" x-show="activeTab === '{{ $tab }}'" @if ($tab !== 'overview') x-cloak @endif class="space-y-6 pt-8 text-base leading-8 text-heading-soft focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500 sm:text-lg sm:leading-9">
                        @if ($tab === 'country')
                            <p class="font-semibold text-heading">{{ $child->public_country }}</p>
                        @endif
                        @foreach (preg_split('/\R\s*\R/u', trim((string) $story) !== '' ? trim((string) $story) : (string) $fallback) as $paragraph)
                            <p class="whitespace-pre-line break-words [overflow-wrap:anywhere]">{{ $paragraph }}</p>
                        @endforeach
                    </div>
                @endforeach
            </article>
        </div>
    </section>
    @if ($child->sponsorshipPhotos->isNotEmpty())
        @php
            $galleryItems = $child->sponsorshipPhotos->map(fn ($photo) => [
                'src' => Storage::disk('public')->url($photo->path),
                'caption' => $photo->caption,
                'sensitive' => $photo->is_sensitive,
            ])->values();
        @endphp
        <section
            aria-labelledby="gallery-heading"
            class="mx-auto max-w-6xl px-6 pb-14 sm:px-10 sm:pb-20"
            x-data="{
                items: @js($galleryItems),
                revealed: {},
                open: null,
                isHidden(i) { return this.items[i].sensitive && ! this.revealed[i] },
                show(i) { if (! this.isHidden(i)) this.open = i },
                step(d) { let i = this.open; do { i = (i + d + this.items.length) % this.items.length } while (this.isHidden(i) && i !== this.open); this.open = i },
            }"
            @keydown.escape.window="open = null"
            @keydown.arrow-right.window="if (open !== null) step(1)"
            @keydown.arrow-left.window="if (open !== null) step(-1)"
        >
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Gallery</p>
                    <h2 id="gallery-heading" class="section-title mt-3 text-heading">{{ $child->public_display_name }}’s world in pictures</h2>
                    <p class="section-intro mt-3">Photos shared with guardian consent to help you understand {{ $child->public_display_name }}’s situation and needs.</p>
                </div>
                @if ($child->sponsorshipPhotos->contains('is_sensitive', true))
                    <p class="flex max-w-xs items-start gap-2 rounded-2xl bg-cream-100 px-4 py-3 text-xs leading-relaxed text-ink-500">
                        <svg class="mt-0.5 size-4 shrink-0 text-heading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                        Some photos show medical or difficult conditions. They stay blurred until you choose to view them.
                    </p>
                @endif
            </div>

            <ul class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                @foreach ($child->sponsorshipPhotos as $index => $photo)
                    <li @class(['relative overflow-hidden rounded-2xl bg-cream-200', 'col-span-2 row-span-2 lg:col-span-2' => $loop->first && $loop->count > 2])>
                        <button type="button" class="group block h-full w-full" @click="show({{ $index }})" :tabindex="isHidden({{ $index }}) ? -1 : 0" aria-label="Open photo {{ $loop->iteration }} of {{ $loop->count }}">
                            <img
                                src="{{ Storage::disk('public')->url($photo->path) }}"
                                alt="{{ $photo->caption ?: 'Photo of '.$child->public_display_name }}"
                                loading="lazy"
                                class="aspect-[4/3] h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                @if ($photo->is_sensitive) :class="isHidden({{ $index }}) ? 'scale-110 blur-2xl brightness-75' : ''" @endif
                            >
                            @if ($photo->caption)
                                <span class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-4 pb-3 pt-8 text-left text-sm text-white" x-show="! isHidden({{ $index }})">{{ $photo->caption }}</span>
                            @endif
                        </button>

                        @if ($photo->is_sensitive)
                            <div x-show="isHidden({{ $index }})" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-brand-900/40 p-4 text-center text-white">
                                <span class="grid size-11 place-items-center rounded-full bg-white/15 ring-1 ring-white/30 backdrop-blur" aria-hidden="true">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.9 17.9A10.1 10.1 0 0 1 12 20c-7 0-10-8-10-8a18.4 18.4 0 0 1 5.1-6M9.9 4.2A9.1 9.1 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.2 3.2M14.1 14.1a3 3 0 1 1-4.2-4.2M2 2l20 20"/></svg>
                                </span>
                                <p class="text-sm font-semibold">Sensitive image</p>
                                <p class="hidden max-w-[16rem] text-xs text-white/80 sm:block">This photo may be difficult to see.</p>
                                <button type="button" class="btn btn-light btn-sm" @click="revealed[{{ $index }}] = true">View image</button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Lightbox --}}
            <div x-show="open !== null" x-cloak
                x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-300 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center bg-[#020524]/80 p-4 backdrop-blur-xl" role="dialog" aria-modal="true" aria-label="Photo viewer" @click.self="open = null">
                <template x-if="open !== null">
                    <figure class="relative max-h-full max-w-5xl">
                        <img :src="items[open].src" :alt="items[open].caption || 'Photo of {{ $child->public_display_name }}'" class="max-h-[80vh] w-auto rounded-2xl object-contain">
                        <figcaption x-show="items[open].caption" x-text="items[open].caption" class="mt-3 text-center text-sm text-white/85"></figcaption>
                    </figure>
                </template>
                <button type="button" class="absolute right-4 top-4 grid size-11 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" @click="open = null" aria-label="Close">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
                <button type="button" x-show="items.length > 1" class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" @click="step(-1)" aria-label="Previous photo">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button type="button" x-show="items.length > 1" class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" @click="step(1)" aria-label="Next photo">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </section>
    @endif

    <section aria-labelledby="support-heading" class="mx-3 rounded-3xl bg-brand-900 px-6 py-12 text-white sm:mx-4 sm:px-10 lg:mx-6">
        <div class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[1fr_auto] lg:items-center">
            <div class="max-w-xl">
                <p class="eyebrow eyebrow-light">The next chapter</p>
                <h2 id="support-heading" class="section-title mt-4">A connection starts with you.</h2>
                <p class="mt-5 leading-7 text-paper-dim">Choose how you would like to support {{ $child->public_display_name }}, then review your sponsorship request on the next screen. Our team will confirm the arrangements with you.</p>
            </div>
            <div class="flex max-w-sm flex-col items-start gap-5">
                <a href="{{ route('sponsor-a-child.checkout', $child->public_sponsorship_slug) }}" class="inline-flex rounded-full bg-gold-500 px-7 py-4 text-sm font-bold text-white transition hover:bg-white hover:text-brand-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Sponsor {{ $child->public_display_name }} <span aria-hidden="true" class="ml-3">→</span></a>
            </div>
        </div>
    </section>
</x-layouts::site>
