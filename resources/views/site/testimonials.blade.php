<x-layouts::site :title="__('Testimonials')">
    <x-site.page-hero
        :title="__('Real voices from the people we walk alongside.')"
        :breadcrumb="__('Testimonials')"
        image="images/foundation/community-3.jpg"
        :alt="__('A community gathering hosted by the foundation')"
    >
        {{ __('Volunteers, beneficiaries, partners, and neighbours share what the Foundation\'s work has meant to them.') }}
    </x-site.page-hero>

    {{-- WALL OF QUOTES --}}
    <section class="section" aria-labelledby="all-stories">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="eyebrow reveal-down">{{ __('Wall of stories') }}</p>
                    <h2 id="all-stories" class="section-title text-heading reveal mt-3" style="transition-delay: 120ms">{{ __('Every voice matters') }}</h2>
                </div>
                <p class="text-sm text-ink-500">
                    {{ __(':v1 :v2 so far', ['v1' => $total, 'v2' => \Illuminate\Support\Str::plural('story', $total)]) }}
                    @if ($testimonials->hasPages())
                        <span class="text-ink-500/70"> {{ __('· page :v1 of :v2', ['v1' => $testimonials->currentPage(), 'v2' => $testimonials->lastPage()]) }}</span>
                    @endif
                </p>
            </div>

            @if ($total === 0)
                <div class="mt-12 rounded-3xl border border-dashed border-brand-100 bg-surface p-12 text-center">
                    <p class="font-serif text-2xl text-heading">{{ __('Stories coming soon') }}</p>
                    <p class="mt-3 text-sm text-ink-500">{{ __('As volunteers, beneficiaries, and partners share their experiences, they\'ll appear here.') }}</p>
                </div>
            @else
                <div class="mt-10 columns-1 gap-6 sm:columns-2 lg:columns-3 [column-fill:_balance]">
                    @foreach ($testimonials as $t)
                        @php
                            $photo = $t->photo_path ?? null;
                            $initials = collect(preg_split('/\s+/', trim((string) $t->author_name)))
                                ->filter()
                                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                ->take(2)
                                ->implode('');
                        @endphp
                        <figure class="mb-6 break-inside-avoid rounded-3xl border border-brand-100 bg-surface p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-[0_18px_50px_-20px_rgba(0,4,78,0.25)]">
                            <svg class="size-7 text-gold-400" viewBox="0 0 24 24" fill="currentColor"><path d="M9 7H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2H5V9h4V7zm10 0h-4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2h-4V9h4V7z"/></svg>
                            <blockquote class="mt-4 font-serif text-base leading-relaxed text-heading">
                                &ldquo;{{ $t->quote }}&rdquo;
                            </blockquote>
                            <figcaption class="mt-5 flex items-center gap-3 border-t border-cream-300 pt-4">
                                @if (filled($photo))
                                    <img src="{{ \App\Support\MediaUrl::for($photo) }}" alt="{{ $t->author_name }}" class="aspect-square size-12 rounded-full object-cover" loading="lazy">
                                @else
                                    <span class="grid aspect-square size-12 place-items-center rounded-full bg-brand-50 font-bold text-label">{{ $initials ?: mb_substr((string) $t->author_role, 0, 1) }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="font-serif text-base font-bold text-ink-900">{{ $t->author_name }}</p>
                                    <p class="text-sm text-ink-500">{{ $t->author_role }}</p>
                                </div>
                                @if ($t->featured)
                                    <span class="shrink-0 text-[10px] font-bold uppercase tracking-[0.24em] text-ink-500">{{ __('Featured') }}</span>
                                @endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                @if ($testimonials->hasPages())
                    <nav class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-brand-100 pt-8 sm:flex-row" aria-label="{{ __('Testimonials pagination') }}">
                        <p class="text-xs text-ink-500">
                            {{ __('Showing') }} <strong class="font-semibold text-ink-900">{{ $testimonials->firstItem() }}</strong>–<strong class="font-semibold text-ink-900">{{ $testimonials->lastItem() }}</strong> {{ __('of :v1', ['v1' => $testimonials->total()]) }}
                        </p>

                        <div class="flex items-center gap-2">
                            @if ($testimonials->onFirstPage())
                                <span class="inline-flex items-center gap-1 rounded-full border border-brand-100 px-4 py-2 text-xs font-semibold text-ink-500/50">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                                    {{ __('Previous') }}
                                </span>
                            @else
                                <a href="{{ $testimonials->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 rounded-full border border-brand-100 bg-surface px-4 py-2 text-xs font-semibold text-heading transition hover:border-heading hover:bg-brand-900 hover:text-white">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                                    {{ __('Previous') }}
                                </a>
                            @endif

                            <div class="hidden flex-wrap items-center gap-1 sm:flex">
                                @php
                                    $last = $testimonials->lastPage();
                                    $current = $testimonials->currentPage();
                                    // Show first, last, current ±1; collapse the rest with ellipses.
                                    $pages = collect(range(1, $last))
                                        ->filter(fn ($p) => $p === 1 || $p === $last || abs($p - $current) <= 1)
                                        ->values()
                                        ->all();
                                    $prev = null;
                                @endphp
                                @foreach ($pages as $page)
                                    @if ($prev !== null && $page - $prev > 1)
                                        <span class="px-2 text-xs text-ink-500">…</span>
                                    @endif
                                    @if ($page === $current)
                                        <span class="grid size-9 place-items-center rounded-full bg-brand-900 text-xs font-bold text-white" aria-current="page">{{ __($page) }}</span>
                                    @else
                                        <a href="{{ $testimonials->url($page) }}" class="grid size-9 place-items-center rounded-full text-xs font-semibold text-ink-700 transition hover:bg-cream-100 hover:text-heading">{{ __($page) }}</a>
                                    @endif
                                    @php $prev = $page; @endphp
                                @endforeach
                            </div>

                            @if ($testimonials->hasMorePages())
                                <a href="{{ $testimonials->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 rounded-full bg-brand-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gold-500">
                                    {{ __('Next') }}
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full border border-brand-100 px-4 py-2 text-xs font-semibold text-ink-500/50">
                                    {{ __('Next') }}
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                                </span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </div>
    </section>

    {{-- SHARE YOUR STORY CTA --}}
    <section class="section bg-brand-900 text-white">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 sm:px-6 lg:flex-row lg:px-8">
            <div>
                <p class="eyebrow eyebrow-light">{{ __('Share your story') }}</p>
                <h2 class="section-title mt-2">{{ __('Have a story you\'d like to share?') }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80">
                    {{ __('Reach out to the Foundation and let us know how our work has touched your life. We may feature your story here with your permission.') }}
                </p>
            </div>
            <a href="{{ route('contact') }}" class="btn btn-primary btn-on-dark btn-lg shrink-0" wire:navigate>
                {{ __('Get in touch →') }}
            </a>
        </div>
    </section>

    <livewire:site.newsletter-signup source="testimonials" />
</x-layouts::site>
