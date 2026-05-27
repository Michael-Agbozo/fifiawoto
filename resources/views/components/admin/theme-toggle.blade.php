{{-- Admin variant of the theme toggle. Same Alpine store as the public site,
     styled to sit alongside the admin sidebar/header chrome. --}}
<button
    type="button"
    @click="$store.theme.toggle()"
    :aria-label="$store.theme.isDark() ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="$store.theme.isDark() ? 'Switch to light mode' : 'Switch to dark mode'"
    {{ $attributes->class([
        'group inline-flex items-center gap-2 rounded-xl border border-cream-300 bg-cream-50 px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-700 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2 focus-visible:ring-offset-cream-50',
    ]) }}
>
    {{-- Sun (dark mode active) --}}
    <svg
        x-cloak
        x-show="$store.theme.isDark()"
        class="size-4 shrink-0"
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

    {{-- Moon (light mode active) --}}
    <svg
        x-cloak
        x-show="!$store.theme.isDark()"
        class="size-4 shrink-0"
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

    <span x-text="$store.theme.isDark() ? 'Light' : 'Dark'"></span>
</button>
