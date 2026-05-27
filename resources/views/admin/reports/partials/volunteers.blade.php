@php
    $kpis = $analytics['kpis'] ?? [];
    $funnel = $analytics['funnel'] ?? [];
    $funnelMax = collect($funnel)->max('count') ?: 1;
    $palettes = [
        'amber' => 'bg-amber-500',
        'blue' => 'bg-blue-500',
        'green' => 'bg-green-500',
        'red' => 'bg-red-500',
        'brand' => 'bg-brand-900',
        'gold' => 'bg-gold-500',
        'gray' => 'bg-ink-400',
    ];
    $interestMax = collect($analytics['interests'] ?? [])->max('count') ?: 1;
    $availabilityMax = collect($analytics['availability'] ?? [])->max('count') ?: 1;
    $countryMax = collect($analytics['top_countries'] ?? [])->max('count') ?: 1;
@endphp

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-admin.kpi-card
        label="Applications"
        :value="$kpis['applications']['value'] ?? 0"
        :delta="$kpis['applications']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Approval rate"
        :value="$kpis['approval_rate']['value'] ?? 0"
        suffix="%"
        hint="Approved ÷ all applications"
    />
    <x-admin.kpi-card
        label="Added to roster"
        :value="$kpis['roster_added']['value'] ?? 0"
        :delta="$kpis['roster_added']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Countries represented"
        :value="$kpis['countries']['value'] ?? 0"
        hint="Distinct countries in this period"
    />
</div>

<article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
    <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Application status funnel</p>
    <div class="mt-5 space-y-3">
        @forelse ($funnel as $row)
            @php $pct = round(($row['count'] / $funnelMax) * 100, 1); @endphp
            <div>
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-ink-900">{{ $row['label'] }}</span>
                    <span class="text-ink-500">{{ $row['count'] }}</span>
                </div>
                <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-cream-100">
                    <div class="h-full rounded-full {{ $palettes[$row['palette']] ?? 'bg-brand-900' }}" style="width: {{ max(2, $pct) }}%"></div>
                </div>
            </div>
        @empty
            <p class="text-xs text-ink-500">No applications in range.</p>
        @endforelse
    </div>
</article>

<div class="grid gap-4 lg:grid-cols-2">
    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Interest areas</p>
        <div class="mt-4 space-y-2.5">
            @forelse ($analytics['interests'] ?? [] as $row)
                @php $pct = round(($row['count'] / $interestMax) * 100, 1); @endphp
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-ink-900">{{ $row['label'] }}</span>
                        <span class="font-semibold text-ink-500">{{ $row['count'] }}</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-cream-100">
                        <div class="h-full rounded-full bg-brand-700" style="width: {{ max(2, $pct) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-ink-500">No interest data in range.</p>
            @endforelse
        </div>
    </article>

    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Availability mix</p>
        <div class="mt-4">
            <x-admin.donut-chart
                :segments="$analytics['availability'] ?? []"
                :centerValue="collect($analytics['availability'] ?? [])->sum('count')"
                centerLabel="Total"
            />
        </div>
    </article>
</div>

<article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
    <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Top countries by applications</p>
    <div class="mt-4 space-y-2.5">
        @forelse ($analytics['top_countries'] ?? [] as $row)
            @php $pct = round(($row['count'] / $countryMax) * 100, 1); @endphp
            <div>
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-ink-900">{{ $row['label'] }}</span>
                    <span class="text-ink-500">{{ $row['count'] }}</span>
                </div>
                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-cream-100">
                    <div class="h-full rounded-full bg-gold-500" style="width: {{ max(2, $pct) }}%"></div>
                </div>
            </div>
        @empty
            <p class="text-xs text-ink-500">No country data in range.</p>
        @endforelse
    </div>
</article>
