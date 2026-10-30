<x-layouts::site :title="'Meet '.$child->public_display_name" description="Learn about a child’s approved public introduction and explore sponsorship through the Fifiawoto Foundation." :noindex="true">
    <div class="mx-auto max-w-6xl px-6 pb-6 pt-10 sm:px-10">
        <a href="{{ route('sponsor-a-child') }}#children" class="inline-flex items-center gap-3 text-sm font-semibold text-label underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500"><span aria-hidden="true">←</span> All children</a>
    </div>
    <section aria-labelledby="profile-heading" class="mx-3 rounded-3xl bg-cream-100 px-6 py-8 sm:mx-4 sm:px-10 lg:mx-6 lg:py-12">
        <div class="mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[1.1fr_1fr] lg:gap-16">
            <figure class="overflow-hidden rounded-2xl bg-cream-200">
                <img src="{{ Storage::disk('public')->url($child->public_photo_path) }}" alt="{{ $child->public_display_name }}" class="aspect-[5/4] w-full object-cover object-center">
                @if (Str::endsWith($child->public_display_name, '(Demo)'))
                    <figcaption class="bg-surface px-5 py-3 text-xs text-ink-500">Illustrative foundation photograph</figcaption>
                @endif
            </figure>
            <div class="min-w-0 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold-500">A future with possibility</p>
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
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold-500">In their own chapter</p>
                <h2 id="story-heading" class="mt-4 break-words text-3xl text-heading sm:text-4xl">A little more about {{ $child->public_display_name }}</h2>
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
    <section aria-labelledby="support-heading" class="mx-3 rounded-3xl bg-brand-900 px-6 py-12 text-white sm:mx-4 sm:px-10 lg:mx-6">
        <div class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[1fr_auto] lg:items-center">
            <div class="max-w-xl">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold-300">The next chapter</p>
                <h2 id="support-heading" class="mt-4 text-3xl sm:text-4xl">A connection starts with you.</h2>
                <p class="mt-5 leading-7 text-paper-dim">Choose how you would like to support {{ $child->public_display_name }}, then review your sponsorship request on the next screen. Our team will confirm the arrangements with you.</p>
            </div>
            <div class="flex max-w-sm flex-col items-start gap-5">
                <a href="{{ route('sponsor-a-child.checkout', $child->public_sponsorship_slug) }}" class="inline-flex rounded-full bg-gold-500 px-7 py-4 text-sm font-bold text-white transition hover:bg-white hover:text-brand-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">Sponsor {{ $child->public_display_name }} <span aria-hidden="true" class="ml-3">→</span></a>
            </div>
        </div>
    </section>
</x-layouts::site>
