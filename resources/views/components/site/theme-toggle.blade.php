@props([
    'size' => 'md',   // 'sm' | 'md' (md = default for header use)
])

@php
    $sizes = [
        'sm' => 'size-8',
        'md' => 'size-9 sm:size-10',
    ];
    $btnSize = $sizes[$size] ?? $sizes['md'];
@endphp

{{-- Icon-only sun/moon toggle. Backed by the global Alpine `theme` store
     in resources/js/app.js. Aria label flips with state so screen readers
     announce the destination action, not the current state. --}}
<button
    type="button"
    @click="$store.theme.toggle()"
    :aria-label="$store.theme.isDark() ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="$store.theme.isDark() ? 'Switch to light mode' : 'Switch to dark mode'"
    {{ $attributes->class([
        'group inline-grid place-items-center rounded-full border border-cream-300 bg-cream-50 text-ink-700 shadow-sm transition hover:border-brand-700 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2 focus-visible:ring-offset-cream-50',
        $btnSize,
    ]) }}
>
    {{-- Sun (visible in dark mode → "switch to light") --}}
    <svg
        x-cloak
        x-show="$store.theme.isDark()"
        class="size-4 sm:size-[18px]"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
    </svg>

    {{-- Moon (visible in light mode → "switch to dark") --}}
    <svg
        x-cloak
        x-show="!$store.theme.isDark()"
        class="size-4 sm:size-[18px]"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
    </svg>
</button>
