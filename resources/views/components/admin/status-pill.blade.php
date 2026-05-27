@props([
    'palette' => 'gray',
    'label',
])

@php
    $dotClasses = [
        'amber' => 'bg-amber-500',
        'blue'  => 'bg-blue-500',
        'green' => 'bg-green-500',
        'red'   => 'bg-red-500',
        'brand' => 'bg-brand-700',
        'gold'  => 'bg-gold-500',
        'gray'  => 'bg-ink-500',
    ];

    $textClasses = [
        'amber' => 'text-amber-600',
        'blue'  => 'text-blue-600',
        'green' => 'text-green-600',
        'red'   => 'text-red-600',
        'brand' => 'text-brand-700',
        'gold'  => 'text-gold-600',
        'gray'  => 'text-ink-500',
    ];

    $dotClass = $dotClasses[$palette] ?? $dotClasses['gray'];
    $textClass = $textClasses[$palette] ?? $textClasses['gray'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 text-xs font-semibold', $textClass]) }}>
    <span class="size-1.5 shrink-0 rounded-full {{ $dotClass }}"></span>
    {{ $label }}
</span>
