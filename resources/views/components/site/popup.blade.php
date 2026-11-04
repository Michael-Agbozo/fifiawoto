@php
    $config = \App\Support\SitePopup::config();
    $shouldShow = \App\Support\SitePopup::shouldShow($config, \App\Support\SitePopup::currentPage());
@endphp

@if ($shouldShow)
    <div
        x-data="sitePopup(@js([
            'key' => \App\Support\SitePopup::dismissalKey($config),
            'frequency' => $config['frequency'],
            'trigger' => $config['trigger'],
            'delay' => max(0, (int) $config['trigger_delay_seconds']) * 1000,
            'scroll' => (int) $config['trigger_scroll_percent'],
        ]))"
        x-show="open"
        x-cloak
        x-effect="document.body.classList.toggle('overflow-hidden', open)"
        x-transition.opacity.duration.200ms
        @keydown.escape.window="dismiss()"
        class="fixed inset-0 z-50 flex items-end justify-center px-4 pb-6 sm:items-center sm:pb-0"
        role="dialog"
        aria-modal="true"
        aria-labelledby="site-popup-heading"
        data-site-popup
    >
        <button type="button" @click="dismiss()" class="absolute inset-0 bg-night/55" tabindex="-1" aria-label="{{ __('Close') }}"></button>

        <div
            x-show="open"
            x-trap.inert="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative z-10 w-full max-w-lg overflow-hidden rounded-3xl bg-surface shadow-[0_30px_80px_-20px_rgba(0,4,78,0.45)]"
        >
            <button type="button" @click="dismiss()" class="absolute right-4 top-4 grid size-9 place-items-center rounded-full text-ink-500 transition hover:bg-cream-200 hover:text-heading" aria-label="{{ __('Close') }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>

            <div class="px-6 py-8 sm:px-8">
                @include('components.site.popup-types.'.$config['type'], ['config' => $config])
            </div>
        </div>
    </div>
@endif
