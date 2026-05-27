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
            <nav class="reveal-down flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.22em] text-white/85 sm:text-sm" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="text-gold-500 transition hover:text-white" wire:navigate>Home</a>
                <svg class="size-3 text-gold-500" viewBox="0 0 8 8" fill="currentColor" aria-hidden="true">
                    <path d="M2,6.9L4.8,4L2,1.1L2.6,0l4,4l-4,4L2,6.9z"/>
                </svg>
                <span class="text-gold-500" aria-current="page">{{ $breadcrumb }}</span>
            </nav>
        @else
            <div></div>
        @endif

        {{-- Title (anchored to bottom of hero) --}}
        <div class="flex flex-col gap-3 {{ $alignClasses }}">
            <h1 class="reveal font-serif text-4xl font-bold leading-[1.05] text-white sm:text-5xl lg:text-6xl xl:text-[80px] xl:leading-none" style="transition-delay: 120ms">
                {{ $title }}
            </h1>
            @if ($slot->isNotEmpty())
                <p class="reveal max-w-2xl text-sm leading-relaxed text-white/90 sm:text-base lg:text-lg" style="transition-delay: 240ms">
                    {{ $slot }}
                </p>
            @endif
        </div>
    </div>
</section>
