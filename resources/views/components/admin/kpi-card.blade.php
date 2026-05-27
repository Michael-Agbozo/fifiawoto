@props([
    'label',
    'value',
    'delta' => null,
    'prefix' => '',
    'suffix' => '',
    'hint' => null,
])

@php
    $deltaSign = $delta === null ? null : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'));
    $deltaClass = match ($deltaSign) {
        'up' => 'text-green-600',
        'down' => 'text-red-600',
        'flat' => 'text-ink-500',
        default => null,
    };
    $deltaLabel = $delta === null ? null : ($delta > 0 ? '+' : '').number_format((float) $delta, 1).'%';
@endphp

<article {{ $attributes->class(['rounded-2xl border border-cream-300 bg-white p-5 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]']) }}>
    <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.22em] text-brand-700">{{ $label }}</p>
    <p class="mt-2 font-serif text-3xl font-bold text-ink-900">{{ $prefix }}{{ $value }}{{ $suffix }}</p>
    @if ($deltaLabel !== null)
        <p class="mt-1 flex items-center gap-1 text-xs font-semibold {{ $deltaClass }}">
            @if ($deltaSign === 'up')
                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 15 7-7 7 7"/></svg>
            @elseif ($deltaSign === 'down')
                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 9 7 7 7-7"/></svg>
            @else
                <span class="size-1.5 rounded-full bg-ink-400"></span>
            @endif
            {{ $deltaLabel }} <span class="font-normal text-ink-500">vs prior period</span>
        </p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-ink-500">{{ $hint }}</p>
    @endif
</article>
