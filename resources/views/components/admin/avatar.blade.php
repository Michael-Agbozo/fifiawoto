@props([
    'src' => null,
    'name' => '',
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'size-10 text-xs',
        'lg' => 'size-16 text-base',
        'xl' => 'size-20 text-lg',
        default => 'size-12 text-sm',
    };

    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->take(2)
        ->implode('');

    $resolvedSrc = null;
    if (filled($src)) {
        $resolvedSrc = str_starts_with($src, 'http') ? $src : asset('storage/'.ltrim($src, '/'));
    }
@endphp

@if ($resolvedSrc)
    <img
        src="{{ $resolvedSrc }}"
        alt="{{ $name }}"
        loading="lazy"
        {{ $attributes->class(['aspect-square rounded-full object-cover ring-2 ring-cream-200', $sizeClasses]) }}
    >
@else
    <span
        {{ $attributes->class(['inline-flex aspect-square items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-700 ring-2 ring-cream-200', $sizeClasses]) }}
        aria-hidden="true"
    >
        {{ $initials ?: '?' }}
    </span>
@endif
