@props([
    'name',           // unique Alpine state key, e.g. 'volunteer'
    'title' => null,
    'subtitle' => null,
    'size' => 'lg',
])

@php
    $maxWidth = match ($size) {
        'sm' => 'max-w-md',
        'md' => 'max-w-xl',
        'xl' => 'max-w-4xl',
        default => 'max-w-2xl',
    };
@endphp

<div
    x-cloak
    x-show="{{ $name }}Open"
    x-transition.opacity.duration.200ms
    @keydown.escape.window="{{ $name }}Open = false"
    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-10"
    role="dialog"
    aria-modal="true"
>
    <button type="button" @click="{{ $name }}Open = false" class="absolute inset-0 bg-ink-900/55" aria-label="Close dialog"></button>

    <div
        x-show="{{ $name }}Open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-[0.98]"
        class="relative z-10 w-full {{ $maxWidth }} overflow-hidden rounded-3xl bg-white shadow-[0_30px_80px_-20px_rgba(0,4,78,0.45)]"
    >
        @if ($title || $subtitle)
            <div class="flex items-start justify-between gap-4 border-b border-brand-100 bg-cream-50 px-6 py-5">
                <div class="min-w-0">
                    @if ($title)
                        <h3 class="font-serif text-2xl font-bold text-brand-900">{{ $title }}</h3>
                    @endif
                    @if ($subtitle)
                        <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" @click="{{ $name }}Open = false" class="grid size-9 shrink-0 place-items-center rounded-full text-ink-500 transition hover:bg-cream-200 hover:text-brand-900" aria-label="Close">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="px-6 py-6 sm:px-8 sm:py-8">
            {{ $slot }}
        </div>
    </div>
</div>
