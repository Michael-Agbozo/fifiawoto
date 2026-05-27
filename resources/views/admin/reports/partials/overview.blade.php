@php
    $kpis = $analytics['kpis'] ?? [];
    $line = $analytics['donation_line'] ?? [];
    $maxLine = collect($line)->max('cents') ?: 1;
    $width = 720;
    $height = 180;
    $stepX = count($line) > 1 ? $width / (count($line) - 1) : 0;
    $points = collect($line)
        ->map(function ($pt, $i) use ($stepX, $maxLine, $height) {
            $x = round($i * $stepX, 2);
            $y = round($height - ($pt['cents'] / $maxLine) * ($height - 20), 2);

            return "$x,$y";
        })
        ->implode(' ');
    $areaPoints = $points ? "0,{$height} ".$points." ".($stepX * (count($line) - 1)).",{$height}" : '';
@endphp

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-admin.kpi-card
        label="Total raised"
        :value="'$'.number_format($kpis['donations']['value'] ?? 0)"
        :delta="$kpis['donations']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Volunteer applications"
        :value="$kpis['volunteer_apps']['value'] ?? 0"
        :delta="$kpis['volunteer_apps']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Beneficiaries onboarded"
        :value="$kpis['beneficiaries']['value'] ?? 0"
        :delta="$kpis['beneficiaries']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Events in range"
        :value="$kpis['events']['value'] ?? 0"
        :delta="$kpis['events']['delta'] ?? null"
    />
</div>

<article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Donations · 12-month trend</p>
        <p class="text-xs text-ink-500">Each point = a calendar month</p>
    </div>
    <div class="mt-5 overflow-x-auto">
        <svg viewBox="0 0 {{ $width }} {{ $height + 30 }}" preserveAspectRatio="none" class="h-48 w-full min-w-[600px]">
            <defs>
                <linearGradient id="overviewArea" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#c1932b" stop-opacity="0.35"/>
                    <stop offset="100%" stop-color="#c1932b" stop-opacity="0"/>
                </linearGradient>
            </defs>
            @if ($points)
                <polygon points="{{ $areaPoints }}" fill="url(#overviewArea)" />
                <polyline points="{{ $points }}" fill="none" stroke="#c1932b" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
            @endif
            @foreach ($line as $i => $pt)
                @php
                    $cx = round($i * $stepX, 2);
                    $cy = round($height - ($pt['cents'] / $maxLine) * ($height - 20), 2);
                @endphp
                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3.5" fill="#c1932b"/>
                <text x="{{ $cx }}" y="{{ $height + 20 }}" text-anchor="middle" font-size="9" fill="#6b6b85">{{ $pt['label'] }}</text>
            @endforeach
        </svg>
    </div>
</article>

<div class="grid gap-4 lg:grid-cols-2">
    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Beneficiaries by category</p>
        <div class="mt-5">
            <x-admin.donut-chart
                :segments="$analytics['beneficiary_categories'] ?? []"
                :centerValue="collect($analytics['beneficiary_categories'] ?? [])->sum('count')"
                centerLabel="Records"
            />
        </div>
    </article>

    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Top donors</p>
        <ul class="mt-4 space-y-3 text-sm">
            @forelse ($analytics['top_donors'] ?? [] as $donor)
                <li class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink-900">{{ $donor['donor_name'] }}</p>
                        <p class="truncate text-xs text-ink-500">{{ $donor['gift_count'] }} gift(s)</p>
                    </div>
                    <span class="text-sm font-bold text-ink-900">${{ number_format($donor['total_cents'] / 100) }}</span>
                </li>
            @empty
                <li class="text-xs text-ink-500">No donor activity in range.</li>
            @endforelse
        </ul>

        <p class="mt-6 font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Top events</p>
        <ul class="mt-3 space-y-2 text-sm">
            @forelse ($analytics['top_events'] ?? [] as $ev)
                <li class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink-900">{{ $ev['title'] }}</p>
                        <p class="truncate text-xs text-ink-500">{{ $ev['status'] }}</p>
                    </div>
                    <span class="text-sm font-bold text-ink-900">${{ number_format($ev['raised_cents'] / 100) }}</span>
                </li>
            @empty
                <li class="text-xs text-ink-500">No event revenue in range.</li>
            @endforelse
        </ul>
    </article>
</div>
