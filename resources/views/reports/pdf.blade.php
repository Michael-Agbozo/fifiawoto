<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $categoryLabel }} report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 0; }
        .page { padding: 30px 36px; }
        h1 { font-size: 22px; margin: 0 0 4px 0; color: #00044e; }
        h2 { font-size: 12px; margin: 22px 0 8px 0; color: #00044e; text-transform: uppercase; letter-spacing: 0.18em; }
        .meta { color: #4a4a66; font-size: 10px; margin-bottom: 18px; }
        .kpi-grid { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .kpi-grid td { width: 25%; padding: 10px; vertical-align: top; border: 1px solid #e6e1d4; }
        .kpi-label { color: #00044e; font-size: 8px; text-transform: uppercase; letter-spacing: 0.18em; font-weight: bold; }
        .kpi-value { font-size: 16px; font-weight: bold; margin-top: 4px; color: #1a1a2e; }
        .kpi-delta { font-size: 9px; margin-top: 4px; font-weight: bold; }
        .kpi-delta.up { color: #15803d; }
        .kpi-delta.down { color: #b91c1c; }
        .kpi-foot { color: #6b6b85; font-size: 9px; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border-bottom: 1px solid #e6e1d4; padding: 5px 8px; text-align: left; font-size: 10px; }
        table.data thead th { background: #f5efe1; color: #00044e; text-transform: uppercase; font-size: 8px; letter-spacing: 0.18em; }
        .bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
        .bar-label { width: 110px; font-size: 9px; color: #4a4a66; }
        .bar-track { flex: 1; height: 9px; background: #f5efe1; border-radius: 3px; overflow: hidden; }
        .bar-fill { height: 100%; background: #c1932b; }
        .bar-fill.brand { background: #00044e; }
        .bar-fill.green { background: #15803d; }
        .bar-fill.red { background: #b91c1c; }
        .bar-fill.amber { background: #d97706; }
        .bar-fill.blue { background: #1d4ed8; }
        .bar-fill.gold { background: #c1932b; }
        .bar-fill.gray { background: #64748b; }
        .bar-amount { width: 110px; text-align: right; font-size: 10px; font-weight: bold; color: #1a1a2e; }
        .footer { margin-top: 24px; color: #6b6b85; font-size: 8px; border-top: 1px solid #e6e1d4; padding-top: 8px; }
        .two-col { width: 100%; border-collapse: collapse; }
        .two-col td { vertical-align: top; padding: 0 8px 0 0; }
        .pct-bar { width: 100%; height: 5px; background: #f5efe1; border-radius: 2px; overflow: hidden; margin-top: 2px; }
        .pct-bar > div { height: 100%; background: #c1932b; }
    </style>
</head>
<body>
<div class="page">
    <h1>{{ $categoryLabel }} report</h1>
    <p class="meta">
        Date range: <strong>{{ $fromDate ?: 'beginning' }}</strong> — <strong>{{ $toDate ?: 'today' }}</strong>
        · Generated {{ $generatedAt->format('M j, Y · g:i A') }}
    </p>

    @php
        $kpis = $analytics['kpis'] ?? [];
        $renderKpi = function ($cells) {
            return $cells;
        };
        $delta = function ($v) {
            if ($v === null) return '';
            $cls = $v > 0 ? 'up' : ($v < 0 ? 'down' : '');
            $sign = $v > 0 ? '+' : '';
            return '<span class="kpi-delta '.$cls.'">'.$sign.number_format((float) $v, 1).'% vs prior</span>';
        };
    @endphp

    <h2>Summary</h2>
    <table class="kpi-grid">
        <tr>
            @if ($category === 'overview')
                <td><div class="kpi-label">Total raised</div><div class="kpi-value">${{ number_format($kpis['donations']['value'] ?? 0) }}</div>{!! $delta($kpis['donations']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Volunteer apps</div><div class="kpi-value">{{ $kpis['volunteer_apps']['value'] ?? 0 }}</div>{!! $delta($kpis['volunteer_apps']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Beneficiaries</div><div class="kpi-value">{{ $kpis['beneficiaries']['value'] ?? 0 }}</div>{!! $delta($kpis['beneficiaries']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Events</div><div class="kpi-value">{{ $kpis['events']['value'] ?? 0 }}</div>{!! $delta($kpis['events']['delta'] ?? null) !!}</td>
            @elseif ($category === 'donations')
                <td><div class="kpi-label">Total raised</div><div class="kpi-value">${{ number_format($kpis['total']['value'] ?? 0) }}</div>{!! $delta($kpis['total']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Gifts logged</div><div class="kpi-value">{{ $kpis['count']['value'] ?? 0 }}</div>{!! $delta($kpis['count']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Avg gift</div><div class="kpi-value">${{ number_format($kpis['avg']['value'] ?? 0, 2) }}</div>{!! $delta($kpis['avg']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Unique donors</div><div class="kpi-value">{{ $kpis['unique_donors']['value'] ?? 0 }}</div>{!! $delta($kpis['unique_donors']['delta'] ?? null) !!}</td>
            @elseif ($category === 'volunteers')
                <td><div class="kpi-label">Applications</div><div class="kpi-value">{{ $kpis['applications']['value'] ?? 0 }}</div>{!! $delta($kpis['applications']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Approval rate</div><div class="kpi-value">{{ $kpis['approval_rate']['value'] ?? 0 }}%</div></td>
                <td><div class="kpi-label">Added to roster</div><div class="kpi-value">{{ $kpis['roster_added']['value'] ?? 0 }}</div>{!! $delta($kpis['roster_added']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Countries</div><div class="kpi-value">{{ $kpis['countries']['value'] ?? 0 }}</div></td>
            @elseif ($category === 'events')
                <td><div class="kpi-label">Events</div><div class="kpi-value">{{ $kpis['events']['value'] ?? 0 }}</div>{!! $delta($kpis['events']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Total goal</div><div class="kpi-value">${{ number_format($kpis['goal']['value'] ?? 0) }}</div></td>
                <td><div class="kpi-label">Total raised</div><div class="kpi-value">${{ number_format($kpis['raised']['value'] ?? 0) }}</div></td>
                <td><div class="kpi-label">Achievement</div><div class="kpi-value">{{ $kpis['achievement_pct']['value'] ?? 0 }}%</div></td>
            @elseif ($category === 'beneficiaries')
                <td><div class="kpi-label">Records</div><div class="kpi-value">{{ $kpis['records']['value'] ?? 0 }}</div>{!! $delta($kpis['records']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Applications</div><div class="kpi-value">{{ $kpis['applications']['value'] ?? 0 }}</div>{!! $delta($kpis['applications']['delta'] ?? null) !!}</td>
                <td><div class="kpi-label">Conversion</div><div class="kpi-value">{{ $kpis['conversion_rate']['value'] ?? 0 }}%</div></td>
                <td><div class="kpi-label">Countries served</div><div class="kpi-value">{{ $kpis['countries_served']['value'] ?? 0 }}</div></td>
            @endif
        </tr>
    </table>

    {{-- Category-specific analytics --}}
    @if ($category === 'overview')
        @php $line = $analytics['donation_line'] ?? []; $maxLine = collect($line)->max('cents') ?: 1; @endphp
        @if (! empty($line))
            <h2>12-month donation trend</h2>
            @foreach ($line as $pt)
                <div class="bar-row">
                    <div class="bar-label">{{ $pt['label'] }} {{ $pt['sublabel'] }}</div>
                    <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, (int) round($pt['cents'] / $maxLine * 100)) }}%;"></div></div>
                    <div class="bar-amount">${{ number_format($pt['cents'] / 100) }}</div>
                </div>
            @endforeach
        @endif

        @php $cats = $analytics['beneficiary_categories'] ?? []; $maxCat = collect($cats)->max('count') ?: 1; @endphp
        @if (collect($cats)->sum('count') > 0)
            <h2>Beneficiaries by category</h2>
            @foreach ($cats as $row)
                @if ($row['count'] > 0)
                    <div class="bar-row">
                        <div class="bar-label">{{ $row['label'] }}</div>
                        <div class="bar-track"><div class="bar-fill brand" style="width: {{ max(2, (int) round($row['count'] / $maxCat * 100)) }}%;"></div></div>
                        <div class="bar-amount">{{ $row['count'] }}</div>
                    </div>
                @endif
            @endforeach
        @endif

        <table class="two-col">
            <tr>
                <td style="width: 50%;">
                    <h2>Top donors</h2>
                    <table class="data">
                        <thead><tr><th>Donor</th><th>Gifts</th><th style="text-align:right;">Total</th></tr></thead>
                        <tbody>
                        @forelse ($analytics['top_donors'] ?? [] as $donor)
                            <tr>
                                <td>{{ $donor['donor_name'] }}</td>
                                <td>{{ $donor['gift_count'] }}</td>
                                <td style="text-align:right;">${{ number_format($donor['total_cents'] / 100) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No donor activity in range.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%;">
                    <h2>Top events</h2>
                    <table class="data">
                        <thead><tr><th>Event</th><th>Status</th><th style="text-align:right;">Raised</th></tr></thead>
                        <tbody>
                        @forelse ($analytics['top_events'] ?? [] as $ev)
                            <tr>
                                <td>{{ $ev['title'] }}</td>
                                <td>{{ $ev['status'] }}</td>
                                <td style="text-align:right;">${{ number_format($ev['raised_cents'] / 100) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No event revenue in range.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    @elseif ($category === 'donations')
        @php $method = $analytics['by_method'] ?? []; $totalMethod = collect($method)->sum('cents') ?: 1; @endphp
        @if (! empty($method))
            <h2>By payment method</h2>
            @foreach ($method as $row)
                <div class="bar-row">
                    <div class="bar-label">{{ ucfirst($row['label']) }}</div>
                    <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, (int) round($row['cents'] / $totalMethod * 100)) }}%;"></div></div>
                    <div class="bar-amount">${{ number_format($row['cents'] / 100) }} · {{ $row['count'] }}</div>
                </div>
            @endforeach
        @endif

        <h2>Top donors</h2>
        <table class="data">
            <thead><tr><th>#</th><th>Donor</th><th>Email</th><th>Gifts</th><th style="text-align:right;">Total</th></tr></thead>
            <tbody>
            @forelse ($analytics['top_donors'] ?? [] as $i => $donor)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $donor['donor_name'] }}</td>
                    <td>{{ $donor['donor_email'] ?: '—' }}</td>
                    <td>{{ $donor['gift_count'] }}</td>
                    <td style="text-align:right;">${{ number_format($donor['total_cents'] / 100) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No donor activity in range.</td></tr>
            @endforelse
            </tbody>
        </table>
    @elseif ($category === 'volunteers')
        @php $funnel = $analytics['funnel'] ?? []; $funnelMax = collect($funnel)->max('count') ?: 1; @endphp
        @if (collect($funnel)->sum('count') > 0)
            <h2>Application status funnel</h2>
            @foreach ($funnel as $row)
                <div class="bar-row">
                    <div class="bar-label">{{ $row['label'] }}</div>
                    <div class="bar-track"><div class="bar-fill {{ $row['palette'] ?? 'brand' }}" style="width: {{ max(2, (int) round($row['count'] / $funnelMax * 100)) }}%;"></div></div>
                    <div class="bar-amount">{{ $row['count'] }}</div>
                </div>
            @endforeach
        @endif

        @php $interests = $analytics['interests'] ?? []; $interestMax = collect($interests)->max('count') ?: 1; @endphp
        @if (! empty($interests))
            <h2>Top interest areas</h2>
            @foreach ($interests as $row)
                <div class="bar-row">
                    <div class="bar-label">{{ $row['label'] }}</div>
                    <div class="bar-track"><div class="bar-fill brand" style="width: {{ max(2, (int) round($row['count'] / $interestMax * 100)) }}%;"></div></div>
                    <div class="bar-amount">{{ $row['count'] }}</div>
                </div>
            @endforeach
        @endif

        @php $countries = $analytics['top_countries'] ?? []; $countryMax = collect($countries)->max('count') ?: 1; @endphp
        @if (! empty($countries))
            <h2>Top countries</h2>
            @foreach ($countries as $row)
                <div class="bar-row">
                    <div class="bar-label">{{ $row['label'] }}</div>
                    <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, (int) round($row['count'] / $countryMax * 100)) }}%;"></div></div>
                    <div class="bar-amount">{{ $row['count'] }}</div>
                </div>
            @endforeach
        @endif
    @elseif ($category === 'events')
        @php $status = $analytics['status_breakdown'] ?? []; $statusMax = collect($status)->max('count') ?: 1; @endphp
        @if (collect($status)->sum('count') > 0)
            <h2>Events by status</h2>
            @foreach ($status as $row)
                <div class="bar-row">
                    <div class="bar-label">{{ $row['label'] }}</div>
                    <div class="bar-track"><div class="bar-fill {{ $row['palette'] ?? 'brand' }}" style="width: {{ max(2, (int) round($row['count'] / $statusMax * 100)) }}%;"></div></div>
                    <div class="bar-amount">{{ $row['count'] }}</div>
                </div>
            @endforeach
        @endif

        <h2>Goal achievement leaderboard</h2>
        <table class="data">
            <thead><tr><th>Event</th><th>Raised</th><th>Goal</th><th style="text-align:right;">% achieved</th></tr></thead>
            <tbody>
            @forelse ($analytics['top_by_goal_pct'] ?? [] as $ev)
                <tr>
                    <td>{{ $ev['title'] }}</td>
                    <td>${{ number_format($ev['raised_cents'] / 100) }}</td>
                    <td>${{ number_format($ev['goal_cents'] / 100) }}</td>
                    <td style="text-align:right;">{{ $ev['pct'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="4">No events with stated goals in range.</td></tr>
            @endforelse
            </tbody>
        </table>
    @elseif ($category === 'beneficiaries')
        @php $byCat = $analytics['by_category'] ?? []; $catMax = collect($byCat)->max('count') ?: 1; @endphp
        @if (collect($byCat)->sum('count') > 0)
            <h2>By category</h2>
            @foreach ($byCat as $row)
                @if ($row['count'] > 0)
                    <div class="bar-row">
                        <div class="bar-label">{{ $row['label'] }}</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, (int) round($row['count'] / $catMax * 100)) }}%;"></div></div>
                        <div class="bar-amount">{{ $row['count'] }}</div>
                    </div>
                @endif
            @endforeach
        @endif

        @php $byStatus = $analytics['by_status'] ?? []; $statusMax = collect($byStatus)->max('count') ?: 1; @endphp
        @if (collect($byStatus)->sum('count') > 0)
            <h2>By status</h2>
            @foreach ($byStatus as $row)
                @if ($row['count'] > 0)
                    <div class="bar-row">
                        <div class="bar-label">{{ $row['label'] }}</div>
                        <div class="bar-track"><div class="bar-fill {{ $row['palette'] ?? 'brand' }}" style="width: {{ max(2, (int) round($row['count'] / $statusMax * 100)) }}%;"></div></div>
                        <div class="bar-amount">{{ $row['count'] }}</div>
                    </div>
                @endif
            @endforeach
        @endif

        @php $countries = $analytics['by_country'] ?? []; $countryMax = collect($countries)->max('count') ?: 1; @endphp
        @if (! empty($countries))
            <h2>Top countries</h2>
            @foreach ($countries as $row)
                <div class="bar-row">
                    <div class="bar-label">{{ $row['label'] }}</div>
                    <div class="bar-track"><div class="bar-fill brand" style="width: {{ max(2, (int) round($row['count'] / $countryMax * 100)) }}%;"></div></div>
                    <div class="bar-amount">{{ $row['count'] }}</div>
                </div>
            @endforeach
        @endif
    @endif

    {{-- Detail rows (only when not overview) --}}
    @if ($category !== 'overview' && ! empty($rows))
        <h2>{{ $categoryLabel }} detail</h2>
        <table class="data">
            <thead>
                <tr>
                    @foreach (array_keys($rows[0]) as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generated by the Dadaa Fifiawoto Nyamadi Foundation admin · {{ config('app.url') }}
    </div>
</div>
</body>
</html>
