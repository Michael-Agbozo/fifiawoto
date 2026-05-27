<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-cream-100 font-sans text-ink-700 antialiased">
        <div class="grid min-h-screen place-items-center px-4 py-12 sm:px-6">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <a href="{{ route('home') }}" class="inline-flex" aria-label="Fifiawoto Foundation home" wire:navigate>
                        <img src="{{ asset('images/logos/dff.svg') }}" alt="Dadaa Fifiawoto Nyamadi Foundation" class="h-14 w-auto">
                    </a>
                </div>

                <div class="rounded-3xl border border-cream-300 bg-white p-8 shadow-sm sm:p-10">
                    {{ $slot }}
                </div>

                <p class="mt-8 text-center text-xs text-ink-500">
                    &copy; {{ now()->year }}, The Fifiawoto Foundation
                </p>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
