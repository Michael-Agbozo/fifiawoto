@php
    $iconPaths = [
        'heart'    => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
        'users'    => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'hand'     => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z',
    ];

    $dotClasses = [
        'amber' => 'bg-amber-500',
        'blue'  => 'bg-blue-500',
        'green' => 'bg-green-500',
        'red'   => 'bg-red-500',
        'gray'  => 'bg-ink-500',
    ];
    $textClasses = [
        'amber' => 'text-amber-600',
        'blue'  => 'text-blue-600',
        'green' => 'text-green-600',
        'red'   => 'text-red-600',
        'gray'  => 'text-ink-500',
    ];

    $maxDaily = max(1, max(array_column($dailyDonations, 'total_cents')));
    $totalLast30 = array_sum(array_column($dailyDonations, 'total_cents'));

    $maxCumulative = max(1, max(array_column($cumulativeDonations, 'value')));
    $points = [];
    $count = max(1, count($cumulativeDonations) - 1);
    foreach ($cumulativeDonations as $i => $row) {
        $x = round(($i / $count) * 100, 2);
        $y = round(100 - (($row['value'] / $maxCumulative) * 100), 2);
        $points[] = "{$x},{$y}";
    }
    $linePoints = implode(' ', $points);
    $areaPoints = '0,100 '.$linePoints.' 100,100';
@endphp

<x-layouts::admin title="Dashboard">
    {{-- Banner --}}
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 px-6 py-6 text-white shadow-[0_30px_60px_-30px_rgba(0,4,78,0.7)] sm:px-10">
        <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-gold-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-12 left-20 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-gold-400">
                    <span class="size-1.5 rounded-full bg-gold-400"></span>
                    Foundation snapshot
                </p>
                <h2 class="mt-3 font-serif text-2xl font-bold text-white sm:text-3xl">
                    Welcome back, {{ auth()->user()->name }}. Here's what's happening across the foundation.
                </h2>
            </div>
            <div class="flex shrink-0 flex-wrap gap-3 sm:flex-col">
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-2.5 text-sm font-bold text-brand-900 shadow-sm transition hover:bg-gold-500 hover:text-white" wire:navigate>
                    View report
                </a>
                <a href="{{ route('admin.beneficiary-applications.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/30 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white hover:text-brand-900" wire:navigate>
                    Review applications
                </a>
            </div>
        </div>
    </section>

    {{-- Overview header --}}
    <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-serif text-xl font-bold text-ink-900 sm:text-2xl">Overview</h2>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                {{ now()->subDays(29)->format('d M Y') }} — {{ now()->format('d M Y') }}
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                <span>Last 30 days</span>
                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </button>
            <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-gold-500" wire:navigate>
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export
            </a>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <article class="rounded-3xl border border-cream-300 bg-white p-5 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)] transition hover:-translate-y-0.5 hover:shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid aspect-square size-9 place-items-center rounded-xl bg-brand-50 text-brand-700">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPaths[$card['icon']] ?? $iconPaths['heart'] }}"/>
                            </svg>
                        </span>
                        <p class="font-sans text-sm font-medium text-ink-700">{{ $card['label'] }}</p>
                    </div>
                    <span class="text-ink-500/40" title="{{ $card['hint'] }}">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9.25"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 17.25h.008v.008H12v-.008Z"/></svg>
                    </span>
                </div>
                <p class="mt-4 font-serif text-3xl font-bold text-ink-900">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-ink-500">
                    @if ($card['delta'] === null)
                        <span class="text-ink-500">No prior period</span>
                    @elseif ($card['delta'] >= 0)
                        <span class="font-semibold text-green-600">+{{ rtrim(rtrim(number_format($card['delta'], 1), '0'), '.') }}%</span>
                    @else
                        <span class="font-semibold text-red-600">{{ rtrim(rtrim(number_format($card['delta'], 1), '0'), '.') }}%</span>
                    @endif
                    <span class="text-ink-500">from last month</span>
                </p>
            </article>
        @endforeach
    </div>

    {{-- Charts row --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-[1.6fr_1fr]">
        {{-- Bar chart --}}
        <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-sans text-sm font-medium text-ink-700">Donations this period</p>
                    <p class="mt-2 font-serif text-3xl font-bold text-ink-900">${{ number_format($totalLast30 / 100) }}</p>
                    <p class="mt-1 text-xs text-ink-500">Last 30 days · daily totals</p>
                </div>
                <button type="button" class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                    Last 30 days
                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
            </div>

            <div class="relative mt-6 h-56">
                {{-- Gridlines --}}
                <div class="pointer-events-none absolute inset-0 grid grid-rows-4 text-[10px] text-ink-500/60">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="flex items-end border-b border-cream-200/80">
                            <span class="-mb-1 -ml-1">{{ $i === 0 ? '$'.number_format($maxDaily / 100) : '' }}</span>
                        </div>
                    @endfor
                </div>

                {{-- Bars --}}
                <div class="absolute inset-0 flex items-end gap-1 pl-6">
                    @foreach ($dailyDonations as $day)
                        @php $h = $day['total_cents'] > 0 ? max(2, round(($day['total_cents'] / $maxDaily) * 100, 2)) : 0; @endphp
                        <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                            <div class="w-full max-w-[14px] rounded-t bg-cream-200 transition group-hover:bg-cream-300" style="height: 100%;"></div>
                            <div class="absolute inset-x-0 bottom-0 mx-auto w-full max-w-[14px] rounded-t bg-gradient-to-t from-brand-900 to-brand-500 transition group-hover:from-gold-500 group-hover:to-gold-400" style="height: {{ $h }}%;"></div>
                            <span class="pointer-events-none absolute -top-7 hidden whitespace-nowrap rounded-md bg-brand-900 px-2 py-0.5 text-[10px] font-semibold text-white shadow-lg group-hover:block">
                                ${{ number_format($day['total_cents'] / 100) }}
                                <span class="block text-[9px] text-white/70">{{ $day['label'] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-3 grid grid-cols-6 text-[10px] text-ink-500">
                @foreach ($dailyDonations as $i => $day)
                    @if ($i % 5 === 0)
                        <span>{{ $day['label'] }}</span>
                    @endif
                @endforeach
            </div>
        </article>

        {{-- Line chart --}}
        <article class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <p class="font-sans text-sm font-medium text-ink-700">Cumulative revenue</p>
            <p class="mt-2 font-serif text-3xl font-bold text-ink-900">{{ $totalDonations }}</p>
            <p class="mt-1 text-xs text-ink-500">
                <span class="font-semibold text-green-600">${{ number_format($totalLast30 / 100) }}</span>
                <span>in the last 30 days</span>
            </p>

            <div class="relative mt-6 h-44">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                    <defs>
                        <linearGradient id="lineGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#df0000" stop-opacity="0.25"/>
                            <stop offset="100%" stop-color="#df0000" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <polygon points="{{ $areaPoints }}" fill="url(#lineGradient)"/>
                    <polyline points="{{ $linePoints }}" fill="none" stroke="#00044e" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"/>
                    @if (! empty($points))
                        @php $last = end($points); [$lx, $ly] = array_map('floatval', explode(',', $last)); @endphp
                        <circle cx="{{ $lx }}" cy="{{ $ly }}" r="0.8" fill="#df0000" stroke="white" stroke-width="0.3" vector-effect="non-scaling-stroke"/>
                    @endif
                </svg>
            </div>

            <div class="mt-3 flex justify-between text-[10px] text-ink-500">
                <span>{{ $cumulativeDonations[0]['label'] ?? '' }}</span>
                <span>{{ end($cumulativeDonations)['label'] ?? '' }}</span>
            </div>
        </article>
    </div>

    @php
        $tabTargets = [
            'all' => null,
            'volunteer' => route('admin.volunteers.index'),
            'donation' => route('admin.donations.index'),
            'contact' => null,
            'newsletter' => null,
        ];
    @endphp

    {{-- Recent activity table with tabs --}}
    <article
        class="mt-6 rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]"
        x-data="{ tab: 'all' }"
    >
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-cream-200 px-6 py-5">
            <div>
                <h3 class="font-serif text-lg font-bold text-ink-900">Recent activity</h3>
                <p class="mt-0.5 text-xs text-ink-500">Submissions and gifts across every channel</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    :href="'{{ route('admin.dashboard.export') }}?tab=' + tab"
                    class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-900"
                >
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5 5 5-5M12 4v12"/></svg>
                    Export CSV
                </a>
                <a
                    href="{{ route('admin.volunteers.index') }}"
                    x-show="tab === 'volunteer'"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900"
                >
                    View all volunteers →
                </a>
                <a
                    href="{{ route('admin.donations.index') }}"
                    x-show="tab === 'donation'"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900"
                >
                    View all donations →
                </a>
                <a
                    href="{{ route('admin.reports.index') }}"
                    x-show="tab === 'all'"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900"
                >
                    Full report →
                </a>
            </div>
        </header>

        <div class="flex flex-wrap gap-1 border-b border-cream-200 px-4">
            @foreach ([
                ['all',        'All',          count($activity)],
                ['volunteer',  'Volunteers',   collect($activity)->where('kind', 'volunteer')->count()],
                ['donation',   'Donations',    collect($activity)->where('kind', 'donation')->count()],
                ['contact',    'Messages',     collect($activity)->where('kind', 'contact')->count()],
                ['newsletter', 'Newsletter',   collect($activity)->where('kind', 'newsletter')->count()],
            ] as [$key, $label, $count])
                <button
                    type="button"
                    @click="tab = @js($key)"
                    :class="tab === @js($key) ? 'border-brand-900 text-brand-900' : 'border-transparent text-ink-500 hover:text-ink-900'"
                    class="font-sans -mb-px inline-flex items-center gap-2 border-b-2 px-3 py-3 text-xs font-semibold uppercase tracking-[0.18em] transition"
                >
                    {{ $label }}
                    @if ($count > 0)
                        <span :class="tab === @js($key) ? 'bg-brand-900 text-white' : 'bg-cream-200 text-ink-500'" class="inline-flex min-w-[1.25rem] items-center justify-center rounded-md px-1.5 py-0.5 text-[10px] font-bold transition">{{ $count }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        @if (empty($activity))
            <div class="px-6 py-10 text-center text-sm text-ink-500">
                Nothing has happened yet. Once volunteers apply, donations land, contact forms come in, or newsletter signups occur, they'll surface here.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">When</th>
                            <th class="px-6 py-3">Detail</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-200">
                        @foreach ($activity as $entry)
                            @php
                                $kind = $entry['kind'];
                                $palette = $entry['status_class'];
                                $dotClass = $dotClasses[$palette] ?? $dotClasses['gray'];
                                $textClass = $textClasses[$palette] ?? $textClasses['gray'];
                                $rowTarget = match ($kind) {
                                    'volunteer' => route('admin.volunteers.index'),
                                    'donation' => route('admin.donations.index'),
                                    default => null,
                                };
                            @endphp
                            <tr x-show="tab === 'all' || tab === @js($kind)" class="transition hover:bg-cream-100/40">
                                <td class="px-6 py-3"><p class="font-medium text-ink-900">{{ $entry['name'] }}</p></td>
                                <td class="px-6 py-3 text-ink-700">{{ optional($entry['when'])->format('M j, Y') ?? '—' }}</td>
                                <td class="px-6 py-3 text-ink-700">{{ $entry['detail'] }}</td>
                                <td class="px-6 py-3 text-ink-700">{{ $entry['category'] }}</td>
                                <td class="px-6 py-3 text-ink-700">{{ $entry['label'] }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $textClass }}">
                                        <span class="size-1.5 rounded-full {{ $dotClass }}"></span>
                                        {{ $entry['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if ($rowTarget)
                                        <a href="{{ $rowTarget }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-900" aria-label="Open in module">
                                            Open →
                                        </a>
                                    @else
                                        <span class="text-xs text-ink-500/70">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <footer class="flex items-center justify-between border-t border-cream-200 px-6 py-4 text-xs text-ink-500">
            <span>Showing {{ count($activity) }} most recent entries</span>
            <a href="{{ route('admin.dashboard.export') }}" class="inline-flex items-center gap-1.5 rounded-md border border-cream-300 px-2.5 py-1 font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5 5 5-5M12 4v12"/></svg>
                Download all as CSV
            </a>
        </footer>
    </article>
</x-layouts::admin>
