@props([
    'title',
    'breadcrumb' => null,    // current-page label (e.g. "About"). "Home" link is rendered automatically.
    'image',                 // background photo path under public/
    'alt' => '',
    'align' => 'left',       // 'left' (default) or 'center'
])

@php
    $alignClasses = $align === 'center'
        ? 'items-center text-center'
        : 'items-start text-left';
@endphp

{{-- Full-bleed photo hero with a navy wash, breadcrumb at top-left, headline anchored bottom.
     Inset into the framed card shell exactly like the other heroes so it carries the same
     rounded corners and side gap. --}}
<section class="relative isolate mx-3 mt-3 overflow-hidden rounded-3xl sm:mx-4 sm:mt-4 lg:mx-6 lg:mt-6">
    <img
        src="{{ asset($image) }}"
        alt="{{ $alt }}"
        class="absolute inset-0 -z-20 h-full w-full object-cover"
        fetchpriority="high"
    >

    {{-- Navy color-wash overlay — two layers for depth: a flat tint plus a
         gradient that darkens toward the bottom so the headline reads cleanly
         against any photo. --}}
    <div class="absolute inset-0 -z-10 bg-brand-900/55"></div>
    <div
        class="absolute inset-0 -z-10"
        style="background-image: linear-gradient(180deg, rgba(0,4,78,0.25) 0%, rgba(0,4,78,0.7) 70%, rgba(0,4,78,0.85) 100%);"
    ></div>

    <div class="relative mx-auto flex min-h-[280px] max-w-7xl flex-col justify-between gap-12 px-5 py-10 text-white sm:min-h-[340px] sm:px-6 sm:py-14 lg:min-h-[420px] lg:px-8 lg:py-16">
        {{-- Breadcrumb (top) --}}
        @if ($breadcrumb)
            <nav class="reveal-down self-start" aria-label="{{ __('Breadcrumb') }}">
                <ol class="inline-flex items-center gap-1 rounded-full bg-white/10 py-1.5 pl-1.5 pr-4 text-sm ring-1 ring-white/20 backdrop-blur-md">
                    <li>
                        <a href="{{ route('home') }}" class="group inline-flex items-center gap-2 rounded-full py-1 pl-1.5 pr-2 text-white/75 transition hover:bg-white/10 hover:text-white" wire:navigate>
                            <span class="grid size-6 place-items-center rounded-full bg-white/15 transition group-hover:bg-gold-500" aria-hidden="true">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/></svg>
                            </span>
                            {{ __('Home') }}
                        </a>
                    </li>
                    <li aria-hidden="true" class="text-white/40">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                    </li>
                    <li>
                        <span class="font-semibold text-white" aria-current="page">{{ __($breadcrumb) }}</span>
                    </li>
                </ol>
            </nav>
            <script type="application/ld+json">{!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $breadcrumb, 'item' => url()->current()],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @else
            <div></div>
        @endif

        {{-- Title (anchored to bottom of hero) --}}
        <div class="flex flex-col gap-3 {{ $alignClasses }}">
            <h1 class="reveal font-serif text-4xl font-bold leading-[1.05] text-white sm:text-5xl lg:text-6xl xl:text-[80px] xl:leading-none" style="transition-delay: 120ms">
                {{ __($title) }}
            </h1>
            @if ($slot->isNotEmpty())
                <p class="reveal max-w-2xl text-sm leading-relaxed text-white/90 sm:text-base lg:text-lg" style="transition-delay: 240ms">
                    {{ $slot }}
                </p>
            @endif
        </div>
    </div>
</section>
