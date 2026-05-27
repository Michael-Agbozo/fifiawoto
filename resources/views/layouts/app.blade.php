@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-cream-100 font-sans text-ink-700 antialiased">
        @php
            $user = auth()->user();
            $navItems = [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                ['label' => 'Profile',   'route' => 'profile.edit'],
            ];
        @endphp

        <header
            x-data="{ menuOpen: false, userMenu: false }"
            class="sticky top-0 z-40 bg-white shadow-sm"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('dashboard') }}" class="flex items-center" aria-label="Fifiawoto Foundation" wire:navigate>
                    <img src="{{ asset('images/logos/dff.svg') }}" alt="Dadaa Fifiawoto Nyamadi Foundation" class="h-11 w-auto sm:h-12">
                </a>

                <nav class="hidden lg:flex lg:items-center lg:gap-8">
                    @foreach ($navItems as $item)
                        @php $isCurrent = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'text-sm font-medium transition-colors',
                                'text-gold-500' => $isCurrent,
                                'text-ink-700 hover:text-gold-500' => ! $isCurrent,
                            ])
                            wire:navigate
                        >
                            {{ __($item['label']) }}
                        </a>
                    @endforeach
                </nav>

                <div class="hidden items-center gap-3 lg:flex">
                    @if ($user)
                        <div class="relative" @click.outside="userMenu = false">
                            <button
                                type="button"
                                @click="userMenu = !userMenu"
                                class="flex items-center gap-3 rounded-full border border-cream-300 bg-white px-3 py-1.5 text-sm text-ink-700 hover:border-gold-500"
                            >
                                <span class="flex aspect-square size-8 items-center justify-center rounded-full bg-brand-900 text-xs font-bold text-white">
                                    {{ $user->initials() }}
                                </span>
                                <span class="font-medium">{{ $user->name }}</span>
                                <svg class="size-4 text-ink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                </svg>
                            </button>

                            <div
                                x-show="userMenu"
                                x-cloak
                                x-transition.opacity
                                class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-cream-300 bg-white py-2 shadow-lg"
                            >
                                <div class="border-b border-cream-200 px-4 py-3">
                                    <p class="text-sm font-semibold text-ink-900">{{ $user->name }}</p>
                                    <p class="text-xs text-ink-500">{{ $user->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-cream-100 hover:text-gold-500" wire:navigate>
                                    {{ __('Settings') }}
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        data-test="logout-button"
                                        class="block w-full px-4 py-2 text-left text-sm text-ink-700 hover:bg-cream-100 hover:text-gold-500"
                                    >
                                        {{ __('Log out') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>

                <button
                    type="button"
                    @click="menuOpen = !menuOpen"
                    class="inline-flex items-center justify-center rounded-md p-2 text-ink-700 lg:hidden"
                    aria-label="Toggle navigation"
                >
                    <svg x-show="!menuOpen" class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    <svg x-show="menuOpen" x-cloak class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div x-show="menuOpen" x-cloak class="lg:hidden">
                <nav class="space-y-1 border-t border-cream-300 bg-white px-4 pb-6 pt-2 sm:px-6">
                    @foreach ($navItems as $item)
                        @php $isCurrent = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'block rounded-md px-3 py-2 text-base font-medium',
                                'bg-cream-100 text-gold-500' => $isCurrent,
                                'text-ink-700 hover:bg-cream-100' => ! $isCurrent,
                            ])
                            wire:navigate
                        >
                            {{ __($item['label']) }}
                        </a>
                    @endforeach
                    @if ($user)
                        <div class="mt-2 border-t border-cream-200 pt-2">
                            <div class="px-3 py-2 text-xs uppercase tracking-[0.18em] text-ink-500">{{ $user->name }}</div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-base font-medium text-ink-700 hover:bg-cream-100 hover:text-gold-500">
                                    {{ __('Log out') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (filled($title))
                <h1 class="mb-6 font-serif text-3xl font-bold text-ink-900">{{ $title }}</h1>
            @endif

            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
