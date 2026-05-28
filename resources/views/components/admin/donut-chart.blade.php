@props([
    'segments' => [],
    'size' => 160,
    'thickness' => 24,
    'centerLabel' => null,
    'centerValue' => null,
])

@php
    $total = collect($segments)->sum('count');
    $radius = ($size - $thickness) / 2;
    $circumference = 2 * M_PI * $radius;
    $colors = ['#c1932b', '#00044e', '#df0000', '#1d4ed8', '#15803d', '#7c3aed', '#b45309', '#475569'];
    $offset = 0;
@endphp

<div class="flex flex-wrap items-center gap-6">
    <div class="relative" style="width: {{ $size }}px; height: {{ $size }}px;">
        <svg viewBox="0 0 {{ $size }} {{ $size }}" width="{{ $size }}" height="{{ $size }}" class="-rotate-90">
            <circle
                cx="{{ $size / 2 }}"
                cy="{{ $size / 2 }}"
                r="{{ $radius }}"
                fill="none"
                stroke="#f5efe1"
                stroke-width="{{ $thickness }}"
            />
            @if ($total > 0)
                @foreach ($segments as $i => $seg)
                    @php
                        $count = (int) ($seg['count'] ?? 0);
                        if ($count <= 0) continue;
                        $length = ($count / $total) * $circumference;
                        $dashArray = "{$length} {$circumference}";
                        $color = $colors[$i % count($colors)];
                    @endphp
                    <circle
                        cx="{{ $size / 2 }}"
                        cy="{{ $size / 2 }}"
                        r="{{ $radius }}"
                        fill="none"
                        stroke="{{ $color }}"
                        stroke-width="{{ $thickness }}"
                        stroke-dasharray="{{ $dashArray }}"
                        stroke-dashoffset="-{{ $offset }}"
                    />
                    @php $offset += $length; @endphp
                @endforeach
            @endif
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
            @if ($centerValue !== null)
                <span class="font-serif text-2xl font-bold text-ink-900">{{ $centerValue }}</span>
            @endif
            @if ($centerLabel !== null)
                <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-500">{{ $centerLabel }}</span>
            @endif
        </div>
    </div>

    <ul class="flex-1 space-y-2 text-sm">
        @forelse ($segments as $i => $seg)
            @php
                $count = (int) ($seg['count'] ?? 0);
                $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                $color = $colors[$i % count($colors)];
            @endphp
            <li class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-2 min-w-0">
                    <span class="inline-block size-2.5 shrink-0 rounded-full" style="background: {{ $color }};"></span>
                    <span class="truncate text-ink-900">{{ $seg['label'] }}</span>
                </span>
                <span class="text-xs text-ink-500">{{ $count }} <span class="text-ink-400">·</span> {{ $pct }}%</span>
            </li>
        @empty
            <li class="text-xs text-ink-500">No data in range.</li>
        @endforelse
    </ul>
</div>
