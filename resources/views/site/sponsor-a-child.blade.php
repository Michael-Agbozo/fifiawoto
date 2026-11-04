<x-layouts::site :title="__('Sponsor a Child')" :description="__('Explore child sponsorship with Fifiawoto. Meet children whose families have agreed to share their stories and enquire about supporting their future.')">
    <x-site.page-hero
        :title="__('Sponsor a Child')"
        :breadcrumb="__('Sponsor a Child')"
        image="images/foundation/school-donation.jpg"
        :alt="__('Foundation education outreach with school supplies')"
    >
        {{ __('Walk alongside a child’s journey, and help open doors to learning, wellbeing and a brighter tomorrow.') }}
    </x-site.page-hero>
    <section id="children" aria-labelledby="children-heading" class="scroll-mt-32 px-5 pb-20 sm:px-10 lg:px-16">
        <div class="relative mx-auto -mt-10 max-w-6xl">
            <form method="GET" action="{{ route('sponsor-a-child') }}#children" class="grid items-end gap-5 rounded-3xl border border-brand-100 bg-surface p-6 shadow-xl sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1fr_auto]">
                <div class="sm:col-span-2 lg:col-span-1">
                    <label for="sponsor-search" class="text-sm font-semibold text-heading">{{ __('Search children') }}</label>
                    <div class="relative mt-2">
                        <svg aria-hidden="true" class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-label" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="8.5" cy="8.5" r="5.5" /><path d="m13 13 4 4" stroke-linecap="round" /></svg>
                        <input id="sponsor-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="80" placeholder="{{ __('Search by name') }}" class="min-w-0 w-full rounded-xl border border-brand-200 bg-surface py-3 pl-11 pr-4 text-heading placeholder:text-ink-500 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    </div>
                </div>
                <div>
                    <label for="sponsor-gender" class="text-sm font-semibold text-heading">{{ __('Gender') }}</label>
                    <div class="relative mt-2">
                        <select id="sponsor-gender" name="gender" class="w-full appearance-none rounded-xl border border-brand-200 bg-surface py-3 pl-4 pr-12 text-heading focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30 forced-colors:appearance-auto">
                            <option value="">{{ __('Either') }}</option>
                            <option value="male" @selected(($filters['gender'] ?? '') === 'male')>{{ __('Boy') }}</option>
                            <option value="female" @selected(($filters['gender'] ?? '') === 'female')>{{ __('Girl') }}</option>
                        </select>
                        <svg aria-hidden="true" class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-label forced-colors:hidden" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m5 7.5 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </div>
                </div>
                <div>
                    <label for="sponsor-age" class="text-sm font-semibold text-heading">{{ __('Age group') }}</label>
                    <div class="relative mt-2">
                        <select id="sponsor-age" name="age" class="w-full appearance-none rounded-xl border border-brand-200 bg-surface py-3 pl-4 pr-12 text-heading focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30 forced-colors:appearance-auto">
                            <option value="">{{ __('All ages') }}</option>
                            @foreach (['0-5', '6-11', '12-17'] as $age)
                                <option value="{{ $age }}" @selected(($filters['age'] ?? '') === $age)>{{ __(':v1 years', ['v1' => $age]) }}</option>
                            @endforeach
                        </select>
                        <svg aria-hidden="true" class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-label forced-colors:hidden" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m5 7.5 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </div>
                </div>
                <div>
                    <label for="sponsor-country" class="text-sm font-semibold text-heading">{{ __('Country') }}</label>
                    <div class="relative mt-2">
                        <select id="sponsor-country" name="country" class="w-full appearance-none rounded-xl border border-brand-200 bg-surface py-3 pl-4 pr-12 text-heading focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30 forced-colors:appearance-auto">
                            <option value="">{{ __('All countries') }}</option>
                            @foreach ($countries as $country)
                                <option @selected(($filters['country'] ?? '') === $country)>{{ __($country) }}</option>
                            @endforeach
                        </select>
                        <svg aria-hidden="true" class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-label forced-colors:hidden" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m5 7.5 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </div>
                </div>
                <button class="btn btn-primary">{{ __('Find a child') }}</button>
            </form>
        </div>
        <div class="mx-auto mt-14 max-w-7xl">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ __('Small beginnings · lasting change') }}</p>
                    <h2 id="children-heading" class="section-title text-heading mt-3">{{ __('A connection that starts with care.') }}</h2>
                </div>
                <a href="{{ route('sponsor-a-child') }}#children" class="font-semibold text-label underline underline-offset-4">{{ __('Reset filters') }}</a>
            </div>
            <p class="mt-4 max-w-2xl text-ink-500">{{ __('Meet children whose guardians have approved these public introductions. Our team will guide you through the support options available.') }}</p>
            <p class="mt-7 text-sm text-ink-500">{{ $children->total() }} {{ Str::plural('profile', $children->total()) }}</p>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-7 xl:grid-cols-3">
                @forelse ($children as $child)
                    <article class="tilt group flex min-w-0 flex-col overflow-hidden rounded-3xl border border-brand-100 bg-surface shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] transition-shadow hover:shadow-[0_18px_50px_-20px_rgba(0,4,78,0.25)]">
                        <a href="{{ route('sponsor-a-child.show', $child->public_sponsorship_slug) }}" class="flex h-full flex-col focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-gold-500">
                            <div class="relative overflow-hidden bg-cream-100">
                                <img src="{{ Storage::disk('public')->url($child->public_photo_path) }}" alt="{{ $child->public_display_name }}" loading="lazy" class="aspect-[5/4] w-full object-cover object-center transition duration-500 motion-safe:group-hover:scale-105">
                                <span class="absolute bottom-2 left-2 rounded-full bg-brand-900 px-2.5 py-1 text-[10px] font-semibold text-white sm:bottom-4 sm:left-5 sm:px-4 sm:py-2 sm:text-xs">{{ $child->public_country }}</span>
                                @if ($child->is_sponsored)
                                    <span class="absolute right-2 top-2 rounded-full bg-gold-500 px-2.5 py-1 text-[10px] font-semibold text-white sm:right-4 sm:top-4 sm:px-4 sm:py-2 sm:text-xs">{{ __('Sponsored') }}</span>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col p-3 sm:p-7">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-500 sm:text-xs sm:tracking-[0.2em]"><span class="hidden sm:inline">{{ __('A story to discover') }} <span aria-hidden="true" class="text-gold-500">/</span> </span>{{ __(':v1 years', ['v1' => $child->date_of_birth->age]) }}</p>
                                <h3 class="mt-1.5 break-words text-lg leading-tight text-heading sm:mt-3 sm:text-2xl">{{ __('Meet :v1', ['v1' => $child->public_display_name]) }}</h3>
                                <p class="mt-4 hidden break-words text-sm leading-7 text-ink-500 sm:line-clamp-3">{{ $child->public_story }}</p>
                                <div class="mt-auto pt-3 sm:pt-6">
                                    <span class="flex items-center justify-between gap-2 border-t border-brand-100 pt-3 text-xs font-bold sm:gap-3 sm:pt-5 sm:text-sm text-label transition-colors group-hover:text-gold-500">{{ __('Read my story') }} <span aria-hidden="true" class="flex size-8 shrink-0 items-center justify-center rounded-full bg-cream-100 text-base sm:size-10 sm:text-lg transition-colors group-hover:bg-gold-500 group-hover:text-white">→</span></span>
                                </div>
                            </div>
                        </a>
                    </article>
                @empty
                    <div class="col-span-full rounded-3xl border border-dashed border-brand-200 bg-cream-50 px-7 py-14 text-center">
                        <h3 class="text-2xl text-heading">{{ __('Every connection deserves care.') }}</h3>
                        <p class="mx-auto mt-4 max-w-xl text-ink-500">{{ __('There are no public profiles matching your search right now. You can still make a difference — speak with our team about current sponsorship opportunities.') }}</p>
                        <a href="{{ route('contact', ['subject' => 'sponsorship']) }}#contact-form" class="mt-7 inline-flex rounded-full bg-gold-500 px-6 py-3 font-bold text-white">{{ __('Talk to our team') }}</a>
                    </div>
                @endforelse
            </div>
            <div class="mt-10">{{ $children->onEachSide(1)->links() }}</div>
        </div>
    </section>
    <section class="section bg-brand-900 px-6 text-white sm:px-10">
        <div class="mx-auto max-w-7xl">
            <p class="eyebrow eyebrow-light">{{ __('How it works') }}</p>
            <h2 class="section-title mt-4 max-w-2xl">{{ __('One thoughtful step.') }}<br>{{ __('A world of possibility.') }}</h2>
            <div class="mt-8 grid gap-5 sm:mt-12 md:grid-cols-3 md:gap-10">
                @foreach ([['01', 'Find a connection', 'Read a child’s approved introduction and learn about their hopes.'], ['02', 'Choose your support', 'Choose a suggested monthly pledge or your own amount, then review your sponsorship request.'], ['03', 'Support the journey', 'Agree the next steps with our team, including how appropriate updates can be shared.']] as [$number, $heading, $copy])
                    <div class="flex gap-4 border-t border-white/25 pt-5 md:block md:pt-6">
                        <p class="text-sm font-bold text-gold-300 max-md:mt-1">{{ __($number) }}</p>
                        <div>
                            <h3 class="text-xl md:mt-4 md:text-2xl">{{ __($heading) }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-white/80 md:mt-4 md:text-base">{{ __($copy) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <section class="section mx-auto grid max-w-7xl gap-12 px-6 lg:grid-cols-2 lg:px-10">
        <div>
            <p class="eyebrow">{{ __('Care beyond a profile') }}</p>
            <h2 class="section-title text-heading mt-4">{{ __('A child’s dignity comes first.') }}</h2>
            <p class="mt-6 leading-relaxed text-ink-500">{{ __('Sponsorship begins with listening. We work with families to understand their priorities, from education and school supplies to wellbeing and everyday essentials. Our team can explain what support is currently needed.') }}</p>
            <p class="mt-4 leading-relaxed text-ink-500">{{ __('Connections and updates are coordinated by the foundation, with the child’s safety and guardian’s wishes at the centre.') }}</p>
        </div>
        <div class="space-y-4">
            @foreach ($faqs as $faq)
                <details class="rounded-2xl border border-brand-100 bg-surface p-6">
                    <summary class="cursor-pointer font-semibold text-heading">{{ $faq->question }}</summary>
                    <p class="mt-4 text-sm leading-relaxed text-ink-500">{{ $faq->answer }}</p>
                </details>
            @endforeach
        </div>
    </section>
</x-layouts::site>
