@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class(['flex flex-wrap items-end justify-between gap-3']) }}>
    <div>
        <h2 class="font-serif text-xl font-bold text-ink-900 sm:text-2xl">{{ $title }}</h2>
        @if (filled($subtitle))
            <p class="mt-0.5 text-xs text-ink-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
