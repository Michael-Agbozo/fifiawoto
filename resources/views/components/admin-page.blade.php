@props([
    'title',
    'kicker' => null,
    'description' => null,
])

<x-layouts::admin :title="$title">
    @if (filled($kicker) || filled($description) || isset($actions))
        <div class="-mt-2 mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                @if (filled($kicker))
                    <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-ink-500">{{ $kicker }}</p>
                @endif
                @if (filled($description))
                    <p class="mt-1 max-w-2xl text-sm text-ink-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-3">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</x-layouts::admin>
