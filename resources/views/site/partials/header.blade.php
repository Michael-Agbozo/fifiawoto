@php
    $navItems = [
        ['label' => 'Home',      'route' => 'home'],
        ['label' => 'About',     'route' => 'about'],
        ['label' => 'Events',    'route' => 'events.index'],
        ['label' => 'Volunteer', 'route' => 'volunteer'],
        ['label' => 'Contact',   'route' => 'contact'],
    ];
@endphp

<header
    wire:transition="site-header"
    x-data="{ open: false, scrolled: false }"
    x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 8)"
    :class="scrolled ? 'bg-brand-900 text-white shadow-lg' : 'bg-white text-ink-900 shadow-[0_10px_40px_-15px_rgba(0,4,78,0.18)]'"
    class="sticky top-3 z-40 overflow-clip rounded-3xl transition-colors sm:top-4 lg:top-6"
>
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-5 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center" aria-label="Fifiawoto Foundation home" wire:navigate>
            <img
                x-show="!scrolled"
                src="{{ asset('images/logos/dff.svg') }}"
                alt="Dadaa Fifiawoto Nyamadi Foundation"
                class="h-12 w-auto sm:h-14"
            >
            <img
                x-show="scrolled"
                x-cloak
                src="{{ asset('images/logos/dff-sticky.svg') }}"
                alt="Dadaa Fifiawoto Nyamadi Foundation"
                class="h-12 w-auto sm:h-14"
            >
        </a>

        <nav class="hidden lg:flex lg:items-center lg:gap-10">
            @foreach ($navItems as $item)
                @php $isCurrent = request()->routeIs($item['route']); @endphp
                @if ($isCurrent)
                    <a href="{{ route($item['route']) }}" class="text-sm font-semibold text-gold-500" wire:navigate>{{ $item['label'] }}</a>
                @else
                    <a
                        href="{{ route($item['route']) }}"
                        :class="scrolled ? 'text-white/85 hover:text-white' : 'text-ink-700 hover:text-gold-500'"
                        class="text-sm font-medium transition-colors"
                        wire:navigate
                    >
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            <a
                href="{{ route('volunteer') }}"
                :class="scrolled
                    ? 'border-white/40 text-white hover:bg-white hover:text-brand-900'
                    : 'border-brand-900 text-brand-900 hover:bg-brand-900 hover:text-white'"
                class="rounded-full border-2 px-5 py-2.5 text-sm font-semibold transition-colors"
                wire:navigate
            >
                Get Involved
            </a>
            <a
                href="{{ route('donate') }}"
                class="rounded-full bg-gold-500 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900"
                wire:navigate
            >
                Donate
            </a>
        </div>

        <button
            type="button"
            :class="scrolled ? 'text-white' : 'text-ink-900'"
            class="inline-flex items-center justify-center rounded-md p-2 lg:hidden"
            @click="open = ! open"
            :aria-expanded="open"
            aria-label="Toggle navigation"
        >
            <svg x-show="!open" class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
            <svg x-show="open" x-cloak class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak class="lg:hidden">
        <nav class="space-y-1 border-t border-cream-300 bg-white px-4 pb-6 pt-2 sm:px-6">
            @foreach ($navItems as $item)
                @php $isCurrent = request()->routeIs($item['route']); @endphp
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'block rounded-md px-3 py-2 text-base font-medium',
                        'bg-cream-100 text-gold-500' => $isCurrent,
                        'text-ink-700 hover:bg-cream-100' => ! $isCurrent,
                    ])
                    wire:navigate
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
            <div class="mt-4 flex gap-3 pt-3">
                <a href="{{ route('volunteer') }}" class="flex-1 rounded-full border-2 border-brand-900 px-4 py-2 text-center text-sm font-semibold text-brand-900" wire:navigate>Get Involved</a>
                <a href="{{ route('donate') }}" class="flex-1 rounded-full bg-gold-500 px-4 py-2 text-center text-sm font-bold text-white" wire:navigate>Donate</a>
            </div>
        </nav>
    </div>
</header>
