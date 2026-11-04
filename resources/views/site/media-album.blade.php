@php
    /** @var array<string, mixed> $album */
    $photos = $album['photos'];
    $cover = $photos->first();
@endphp

<x-layouts::site :title="$album['title'].' — Gallery'" :description="'Photos from '.$album['title'].($album['date'] ? ', '.$album['date']->translatedFormat('j F Y') : '').'.'">
    {{-- Album header --}}
    <section class="bg-fluted relative mx-3 mt-3 rounded-3xl text-white sm:mx-4 sm:mt-4 lg:mx-6 lg:mt-6" aria-labelledby="album-heading">
        <div class="mx-auto grid max-w-7xl items-end gap-10 px-5 pb-12 pt-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)] lg:px-8 lg:pb-16">
            <div>
                <nav aria-label="{{ __('Breadcrumb') }}">
                    <ol class="inline-flex flex-wrap items-center gap-1 rounded-full bg-white/10 py-1.5 pl-1.5 pr-4 text-sm ring-1 ring-white/20 backdrop-blur-md">
                        <li><a href="{{ route('home') }}" class="rounded-full px-2.5 py-1 text-white/75 hover:text-white" wire:navigate>{{ __('Home') }}</a></li>
                        <li aria-hidden="true" class="text-white/40">›</li>
                        <li><a href="{{ route('media') }}" class="rounded-full px-2.5 py-1 text-white/75 hover:text-white" wire:navigate>{{ __('Media Gallery') }}</a></li>
                        <li aria-hidden="true" class="text-white/40">›</li>
                        <li><span class="px-1 font-semibold text-white" aria-current="page">{{ __($album['title']) }}</span></li>
                    </ol>
                </nav>

                <p class="eyebrow eyebrow-light mt-8">{{ __(':v1 gallery', ['v1' => $album['kind']]) }}</p>
                <h1 id="album-heading" class="mt-3 font-serif text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-6xl">{{ __($album['title']) }}</h1>

                <dl class="mt-8 flex flex-wrap gap-x-8 gap-y-4 text-sm">
                    @if ($album['date'])
                        <div>
                            <dt class="text-white/60">{{ __('Date') }}</dt>
                            <dd class="mt-1 font-semibold">{{ $album['date']->translatedFormat('j F Y') }}</dd>
                        </div>
                    @endif
                    @if ($album['place'])
                        <div>
                            <dt class="text-white/60">{{ __('Place') }}</dt>
                            <dd class="mt-1 font-semibold">{{ __($album['place']) }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-white/60">{{ __('In this album') }}</dt>
                        <dd class="mt-1 font-semibold">{{ $photos->count() }} {{ Str::plural('item', $photos->count()) }}</dd>
                    </div>
                </dl>

                @if ($album['event_url'])
                    <a href="{{ $album['event_url'] }}" class="btn btn-outline-light mt-8" wire:navigate>{{ __('About this event') }} <span aria-hidden="true">→</span></a>
                @endif
            </div>

            <div class="relative mx-auto hidden w-full max-w-md lg:block" aria-hidden="true">
                <img src="{{ $cover['thumb'] }}" alt="" class="aspect-[4/3] w-full rotate-2 rounded-3xl object-cover shadow-[0_40px_80px_-30px_rgba(0,0,0,0.75)] ring-1 ring-white/20">
            </div>
        </div>
    </section>

    {{-- Photos --}}
    <section
        class="section"
        aria-label="{{ __('Photos') }}"
        x-data="{
            items: @js($photos),
            open: null,
            show(i) { this.open = i },
            step(d) { this.open = (this.open + d + this.items.length) % this.items.length },
        }"
        @keydown.escape.window="open = null"
        @keydown.arrow-right.window="if (open !== null) step(1)"
        @keydown.arrow-left.window="if (open !== null) step(-1)"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <ul class="columns-2 gap-4 sm:columns-3 lg:columns-4">
                @foreach ($photos as $i => $photo)
                    <li class="glass-in mb-4 break-inside-avoid" style="--i: {{ $loop->index % 4 }}">
                        <button type="button" class="group relative block w-full overflow-hidden rounded-2xl bg-cream-200" @click="show({{ $i }})" aria-label="Open {{ $photo['type'] === 'video' ? 'video' : 'photo' }} {{ $i + 1 }} of {{ $photos->count() }}">
                            <img src="{{ $photo['thumb'] }}" alt="{{ $photo['caption'] ?: $album['title'] }}" loading="lazy" class="w-full object-cover transition duration-500 group-hover:scale-105">
                            @if ($photo['type'] === 'video')
                                <span class="absolute inset-0 grid place-items-center bg-black/25" aria-hidden="true">
                                    <span class="grid size-14 place-items-center rounded-full bg-white/90 text-heading shadow-lg"><svg class="ml-0.5 size-6" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
                                </span>
                            @endif
                            @if ($photo['caption'])
                                <span class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 pb-2.5 pt-8 text-left text-xs text-white opacity-0 transition group-hover:opacity-100">{{ __($photo['caption']) }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Viewer: photos full screen, videos play inline --}}
        <div x-show="open !== null" x-cloak
            x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 backdrop-blur-0" x-transition:enter-end="opacity-100 backdrop-blur-xl"
            x-transition:leave="transition duration-300 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-[#020524]/80 p-4 backdrop-blur-xl" role="dialog" aria-modal="true" aria-label="{{ __('Gallery viewer') }}" @click.self="open = null">
            <template x-if="open !== null">
                <figure class="glass-in flex w-full max-w-5xl flex-col items-center">
                    <template x-if="items[open].type === 'video' && items[open].embed">
                        <div class="aspect-video w-full overflow-hidden rounded-2xl bg-black">
                            <iframe :src="items[open].embed" class="h-full w-full" title="{{ __('Video') }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                        </div>
                    </template>
                    <template x-if="items[open].type === 'video' && ! items[open].embed">
                        <video :src="items[open].url" :poster="items[open].thumb" controls class="max-h-[78vh] w-full rounded-2xl bg-black"></video>
                    </template>
                    <template x-if="items[open].type !== 'video'">
                        <img :src="items[open].url" :alt="items[open].caption || @js($album['title'])" class="max-h-[78vh] w-auto rounded-2xl object-contain">
                    </template>
                    <figcaption class="mt-4 text-center text-sm text-white/85">
                        <span x-show="items[open].caption" x-text="items[open].caption"></span>
                        <span class="ml-2 text-white/50" x-text="`${open + 1} / ${items.length}`"></span>
                    </figcaption>
                </figure>
            </template>
            <button type="button" class="absolute right-4 top-4 grid size-11 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" @click="open = null" aria-label="{{ __('Close') }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
            <button type="button" x-show="items.length > 1" class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" @click="step(-1)" aria-label="{{ __('Previous') }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" x-show="items.length > 1" class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" @click="step(1)" aria-label="{{ __('Next') }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
    </section>

    {{-- More albums --}}
    @if ($moreAlbums->isNotEmpty())
        <section class="section bg-cream-50" aria-labelledby="more-albums-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 id="more-albums-heading" class="section-title text-heading">{{ __('More albums') }}</h2>
                    <a href="{{ route('media') }}" class="btn btn-secondary" wire:navigate>{{ __('All albums') }}</a>
                </div>
                <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($moreAlbums as $other)
                        <li>
                            <a href="{{ $other['gallery_url'] }}" class="tilt group block overflow-hidden rounded-3xl border border-cream-300 bg-surface shadow-sm" wire:navigate>
                                <img src="{{ $other['photos']->first()['thumb'] }}" alt="" loading="lazy" class="aspect-[16/10] w-full object-cover transition duration-700 group-hover:scale-105">
                                <div class="p-5">
                                    <p class="text-xs text-ink-500">{{ $other['date']?->translatedFormat('j M Y') }} · {{ $other['photos']->count() }} {{ Str::plural('item', $other['photos']->count()) }}</p>
                                    <h3 class="mt-1 font-serif text-lg font-bold text-heading group-hover:text-gold-500">{{ __($other['title']) }}</h3>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-layouts::site>
