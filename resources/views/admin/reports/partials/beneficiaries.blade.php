@php
    $kpis = $analytics['kpis'] ?? [];
    $countryMax = collect($analytics['by_country'] ?? [])->max('count') ?: 1;
@endphp

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-admin.kpi-card
        label="Beneficiary records"
        :value="$kpis['records']['value'] ?? 0"
        :delta="$kpis['records']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Public applications"
        :value="$kpis['applications']['value'] ?? 0"
        :delta="$kpis['applications']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Conversion rate"
        :value="$kpis['conversion_rate']['value'] ?? 0"
        suffix="%"
        hint="Applications → approved beneficiaries"
    />
    <x-admin.kpi-card
        label="Countries served"
        :value="$kpis['countries_served']['value'] ?? 0"
        hint="Distinct countries this period"
    />
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">By category</p>
        <div class="mt-5">
            <x-admin.donut-chart
                :segments="$analytics['by_category'] ?? []"
                :centerValue="collect($analytics['by_category'] ?? [])->sum('count')"
                centerLabel="Records"
            />
        </div>
    </article>

    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">By status</p>
        <div class="mt-5">
            <x-admin.donut-chart
                :segments="$analytics['by_status'] ?? []"
                :centerValue="collect($analytics['by_status'] ?? [])->sum('count')"
                centerLabel="Records"
            />
        </div>
    </article>
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Top countries</p>
        <div class="mt-4 space-y-2.5">
            @forelse ($analytics['by_country'] ?? [] as $row)
                @php $pct = round(($row['count'] / $countryMax) * 100, 1); @endphp
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-ink-900">{{ $row['label'] }}</span>
                        <span class="text-ink-500">{{ $row['count'] }}</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-cream-100">
                        <div class="h-full rounded-full bg-brand-700" style="width: {{ max(2, $pct) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-ink-500">No country data in range.</p>
            @endforelse
        </div>
    </article>

    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">By gender</p>
        <div class="mt-5">
            <x-admin.donut-chart
                :segments="$analytics['by_gender'] ?? []"
                :centerValue="collect($analytics['by_gender'] ?? [])->sum('count')"
                centerLabel="Records"
            />
        </div>
    </article>
</div>
