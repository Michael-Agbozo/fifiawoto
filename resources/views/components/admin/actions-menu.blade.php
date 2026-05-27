@props([
    'label' => 'Actions',
    'align' => 'right',
])

@php
    $alignmentClasses = $align === 'left'
        ? 'left-0 origin-top-left'
        : 'right-0 origin-top-right';
@endphp

<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    {{ $attributes->class(['relative inline-block text-left']) }}
>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        class="grid aspect-square size-9 place-items-center rounded-xl border border-cream-300 bg-white text-ink-700 transition hover:border-brand-900 hover:text-brand-900"
        aria-label="{{ $label }}"
        title="{{ $label }}"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="5" cy="12" r="1.75"/>
            <circle cx="12" cy="12" r="1.75"/>
            <circle cx="19" cy="12" r="1.75"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="open = false"
        class="absolute {{ $alignmentClasses }} z-30 mt-2 w-60 overflow-hidden rounded-2xl border border-cream-300 bg-white shadow-[0_20px_60px_-20px_rgba(0,4,78,0.25)]"
        role="menu"
    >
        <div class="py-1.5">
            {{ $slot }}
        </div>
    </div>
</div>
