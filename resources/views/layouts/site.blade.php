<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-cream-200 font-sans text-ink-700 antialiased">
        {{-- Cream "frame" with two stacked white cards inside: the header on
             top (its own rounded pill so it stays framed even when sticky)
             and the main content + footer below. The space-y class gives a
             small cream gap between them so each reads as its own surface. --}}
        <div class="space-y-3 p-3 sm:space-y-4 sm:p-4 lg:space-y-6 lg:p-6">
            @include('site.partials.header')

            <div class="overflow-clip rounded-3xl bg-white shadow-[0_10px_40px_-15px_rgba(0,4,78,0.18)]">
                <main wire:transition="site-main" id="main" class="flex flex-col">
                    {{ $slot }}
                </main>

                @include('site.partials.footer')
            </div>
        </div>

        @livewireScripts
    </body>
</html>
