@props([
    'title',
    'subtitle' => null,
    'show' => false,
    'onClose' => 'cancel',
    'size' => 'lg',
])

@php
    $maxWidth = match ($size) {
        'sm' => 'sm:max-w-md',
        'md' => 'sm:max-w-xl',
        'xl' => 'sm:max-w-4xl',
        default => 'sm:max-w-2xl',
    };
@endphp

@if ($show)
    <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <button
            type="button"
            wire:click="{{ $onClose }}"
            class="fixed inset-0 bg-ink-900/55 backdrop-blur-sm animate-backdrop-enter"
            aria-label="Close"
            tabindex="-1"
        ></button>

        <div class="relative flex min-h-full items-end justify-center p-3 sm:items-center sm:p-6">
            <div
                @keydown.escape.window="$wire.{{ $onClose }}()"
                class="relative flex w-full {{ $maxWidth }} max-h-[calc(100dvh-1.5rem)] flex-col overflow-hidden rounded-t-3xl bg-white shadow-[0_30px_80px_-20px_rgba(0,4,78,0.45)] sm:max-h-[calc(100dvh-3rem)] sm:rounded-3xl animate-modal-enter"
            >
                {{-- Header (fixed) --}}
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-cream-200 bg-cream-50 px-6 py-5">
                    <div class="min-w-0 flex-1">
                        <h3 class="font-serif text-xl font-bold text-ink-900">{{ $title }}</h3>
                        @if (filled($subtitle))
                            <p class="mt-1 text-xs text-ink-500">{{ $subtitle }}</p>
                        @endif
                    </div>
                    <button
                        type="button"
                        wire:click="{{ $onClose }}"
                        class="grid size-9 shrink-0 place-items-center rounded-full text-ink-500 transition hover:bg-cream-200 hover:text-ink-900"
                        aria-label="Close"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body (scrolls if content overflows) --}}
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
@endif
