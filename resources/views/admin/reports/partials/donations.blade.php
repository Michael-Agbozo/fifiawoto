@php
    $kpis = $analytics['kpis'] ?? [];
    $daily = $analytics['daily'] ?? [];
    $maxDaily = collect($daily)->max('cents') ?: 1;
    $width = 720;
    $height = 200;
    $stepX = count($daily) > 1 ? $width / (count($daily) - 1) : 0;
    $points = collect($daily)
        ->map(function ($pt, $i) use ($stepX, $maxDaily, $height) {
            $x = round($i * $stepX, 2);
            $y = round($height - ($pt['cents'] / $maxDaily) * ($height - 20), 2);

            return "$x,$y";
        })
        ->implode(' ');
    $areaPoints = $points ? "0,{$height} ".$points." ".($stepX * (count($daily) - 1)).",{$height}" : '';
    $totalMethod = collect($analytics['by_method'] ?? [])->sum('cents') ?: 1;
@endphp

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-admin.kpi-card
        label="Total raised"
        :value="'$'.number_format($kpis['total']['value'] ?? 0)"
        :delta="$kpis['total']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Gifts logged"
        :value="$kpis['count']['value'] ?? 0"
        :delta="$kpis['count']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Average gift"
        :value="'$'.number_format($kpis['avg']['value'] ?? 0, 2)"
        :delta="$kpis['avg']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Unique donors"
        :value="$kpis['unique_donors']['value'] ?? 0"
        :delta="$kpis['unique_donors']['delta'] ?? null"
    />
</div>

<article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Daily donation trend</p>
        <p class="text-xs text-ink-500">{{ count($daily) }} days · peak ${{ number_format($maxDaily / 100) }}</p>
    </div>
    <div class="mt-5 overflow-x-auto">
        <svg viewBox="0 0 {{ $width }} {{ $height + 30 }}" preserveAspectRatio="none" class="h-52 w-full min-w-[600px]">
            <defs>
                <linearGradient id="dailyArea" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#00044e" stop-opacity="0.3"/>
                    <stop offset="100%" stop-color="#00044e" stop-opacity="0"/>
                </linearGradient>
            </defs>
            @if ($points)
                <polygon points="{{ $areaPoints }}" fill="url(#dailyArea)" />
                <polyline points="{{ $points }}" fill="none" stroke="#00044e" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
            @endif
            @foreach ($daily as $i => $pt)
                @if ($i % max(1, intdiv(count($daily), 8)) === 0)
                    @php
                        $cx = round($i * $stepX, 2);
                    @endphp
                    <text x="{{ $cx }}" y="{{ $height + 20 }}" text-anchor="middle" font-size="9" fill="#6b6b85">{{ $pt['label'] }}</text>
                @endif
            @endforeach
        </svg>
    </div>
</article>

<div class="grid gap-4 lg:grid-cols-2">
    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">By payment method</p>
        <div class="mt-5 space-y-3">
            @forelse ($analytics['by_method'] ?? [] as $row)
                @php $pct = round(($row['cents'] / $totalMethod) * 100, 1); @endphp
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold capitalize text-ink-900">{{ $row['label'] }}</span>
                        <span class="text-ink-500">${{ number_format($row['cents'] / 100) }} · {{ $row['count'] }} gift(s)</span>
                    </div>
                    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-cream-100">
                        <div class="h-full rounded-full bg-gold-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-ink-500">No payment data in range.</p>
            @endforelse
        </div>
    </article>

    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Top 10 donors</p>
        <ol class="mt-3 space-y-2 text-sm">
            @forelse ($analytics['top_donors'] ?? [] as $i => $donor)
                <li class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid size-6 shrink-0 place-items-center rounded-full bg-cream-100 text-[10px] font-bold text-ink-700">{{ $i + 1 }}</span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink-900">{{ $donor['donor_name'] }}</p>
                            <p class="truncate text-xs text-ink-500">{{ $donor['gift_count'] }} gift(s){{ $donor['donor_email'] ? ' · '.$donor['donor_email'] : '' }}</p>
                        </div>
                    </div>
                    <span class="text-sm font-bold text-ink-900">${{ number_format($donor['total_cents'] / 100) }}</span>
                </li>
            @empty
                <li class="text-xs text-ink-500">No donor activity in range.</li>
            @endforelse
        </ol>
    </article>
</div>

<article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
    <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Top events by revenue</p>
    <ul class="mt-3 space-y-2 text-sm">
        @forelse ($analytics['top_events'] ?? [] as $ev)
            @php $pct = ($ev['goal_cents'] ?? 0) > 0 ? min(100, (int) round(($ev['raised_cents'] / $ev['goal_cents']) * 100)) : 0; @endphp
            <li>
                <div class="flex items-center justify-between">
                    <p class="truncate font-semibold text-ink-900">{{ $ev['title'] }}</p>
                    <span class="text-sm font-bold text-ink-900">${{ number_format($ev['raised_cents'] / 100) }}</span>
                </div>
                <div class="mt-1 flex items-center gap-2 text-xs text-ink-500">
                    @if ($ev['goal_cents'])
                        <div class="h-1 flex-1 overflow-hidden rounded-full bg-cream-100">
                            <div class="h-full rounded-full bg-gold-500" style="width: {{ $pct }}%"></div>
                        </div>
                        <span>{{ $pct }}% of ${{ number_format($ev['goal_cents'] / 100) }}</span>
                    @else
                        <span>{{ $ev['status'] }}</span>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-xs text-ink-500">No event revenue in range.</li>
        @endforelse
    </ul>
</article>
