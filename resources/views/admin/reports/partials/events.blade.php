@php
    $kpis = $analytics['kpis'] ?? [];
@endphp

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-admin.kpi-card
        label="Events"
        :value="$kpis['events']['value'] ?? 0"
        :delta="$kpis['events']['delta'] ?? null"
    />
    <x-admin.kpi-card
        label="Total goal"
        :value="'$'.number_format($kpis['goal']['value'] ?? 0)"
        hint="Sum of goals across events"
    />
    <x-admin.kpi-card
        label="Total raised"
        :value="'$'.number_format($kpis['raised']['value'] ?? 0)"
        hint="Sum of donations attributed"
    />
    <x-admin.kpi-card
        label="Goal achievement"
        :value="$kpis['achievement_pct']['value'] ?? 0"
        suffix="%"
        hint="Raised ÷ goal across all events"
    />
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">By status</p>
        <div class="mt-5">
            <x-admin.donut-chart
                :segments="$analytics['status_breakdown'] ?? []"
                :centerValue="collect($analytics['status_breakdown'] ?? [])->sum('count')"
                centerLabel="Events"
            />
        </div>
    </article>

    <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Top events by revenue</p>
        <ul class="mt-3 space-y-3 text-sm">
            @forelse ($analytics['top_by_raised'] ?? [] as $i => $ev)
                @php $pct = ($ev['goal_cents'] ?? 0) > 0 ? min(100, (int) round(($ev['raised_cents'] / $ev['goal_cents']) * 100)) : 0; @endphp
                <li>
                    <div class="flex items-center justify-between">
                        <p class="truncate font-semibold text-ink-900">{{ $ev['title'] }}</p>
                        <span class="text-sm font-bold text-ink-900">${{ number_format($ev['raised_cents'] / 100) }}</span>
                    </div>
                    @if ($ev['goal_cents'])
                        <div class="mt-1 flex items-center gap-2 text-xs text-ink-500">
                            <div class="h-1 flex-1 overflow-hidden rounded-full bg-cream-100">
                                <div class="h-full rounded-full bg-gold-500" style="width: {{ $pct }}%"></div>
                            </div>
                            <span>{{ $pct }}% of ${{ number_format($ev['goal_cents'] / 100) }}</span>
                        </div>
                    @else
                        <p class="text-xs text-ink-500">{{ $ev['status'] }}</p>
                    @endif
                </li>
            @empty
                <li class="text-xs text-ink-500">No event revenue in range.</li>
            @endforelse
        </ul>
    </article>
</div>

<article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
    <p class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Goal achievement leaderboard</p>
    <p class="mt-1 text-xs text-ink-500">% of fundraising goal reached — only events with a stated goal.</p>
    <ul class="mt-4 space-y-3 text-sm">
        @forelse ($analytics['top_by_goal_pct'] ?? [] as $ev)
            <li>
                <div class="flex items-center justify-between">
                    <p class="truncate font-semibold text-ink-900">{{ $ev['title'] }}</p>
                    <span class="text-sm font-bold {{ $ev['pct'] >= 100 ? 'text-green-600' : 'text-ink-900' }}">{{ $ev['pct'] }}%</span>
                </div>
                <div class="mt-1 flex items-center gap-2 text-xs text-ink-500">
                    <div class="h-1 flex-1 overflow-hidden rounded-full bg-cream-100">
                        <div class="h-full rounded-full {{ $ev['pct'] >= 100 ? 'bg-green-500' : 'bg-brand-700' }}" style="width: {{ min(100, $ev['pct']) }}%"></div>
                    </div>
                    <span>${{ number_format($ev['raised_cents'] / 100) }} of ${{ number_format($ev['goal_cents'] / 100) }}</span>
                </div>
            </li>
        @empty
            <li class="text-xs text-ink-500">No events with stated goals in range.</li>
        @endforelse
    </ul>
</article>
