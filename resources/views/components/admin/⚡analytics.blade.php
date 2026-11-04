<?php

use App\Models\PageVisit;
use App\Models\SiteConversion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url]
    public int $days = 30;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canDo('analytics', 'view'), 403);

        $this->updatedDays();
    }

    public function updatedDays(): void
    {
        $this->days = in_array($this->days, [7, 30, 90], true) ? $this->days : 30;
    }

    private function since(): CarbonInterface
    {
        return now()->subDays($this->days - 1)->startOfDay();
    }

    /** @return Builder<PageVisit> */
    private function visits(): Builder
    {
        return PageVisit::query()->where('created_at', '>=', $this->since());
    }

    /** @return Builder<SiteConversion> */
    private function conversions(): Builder
    {
        return SiteConversion::query()->where('created_at', '>=', $this->since());
    }

    /** @return array{views: int, visitors: int, conversions: int} */
    #[Computed]
    public function totals(): array
    {
        return [
            'views' => $this->visits()->count(),
            'visitors' => $this->visits()->distinct()->count('visitor_hash'),
            'conversions' => $this->conversions()->count(),
        ];
    }

    /** One entry per day, including days with no visits. */
    #[Computed]
    public function daily(): Collection
    {
        $counts = $this->visits()
            ->selectRaw('date(created_at) as day, count(*) as views')
            ->groupBy('day')
            ->pluck('views', 'day');

        return collect(range($this->days - 1, 0))->map(function (int $daysAgo) use ($counts) {
            $day = today()->subDays($daysAgo);

            return ['label' => $day->format('M j'), 'views' => (int) ($counts[$day->toDateString()] ?? 0)];
        });
    }

    #[Computed]
    public function topPages(): Collection
    {
        return $this->visits()
            ->select('path', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_hash) as visitors'))
            ->groupBy('path')->orderByDesc('views')->limit(10)->get();
    }

    #[Computed]
    public function sources(): Collection
    {
        return $this->breakdown('source');
    }

    #[Computed]
    public function campaigns(): Collection
    {
        return $this->breakdown('campaign');
    }

    #[Computed]
    public function conversionTypes(): Collection
    {
        return $this->conversions()->select('type', DB::raw('count(*) as total'))->groupBy('type')->orderByDesc('total')->get();
    }

    /** Visitors and completed actions side by side, for each source or campaign. */
    private function breakdown(string $column): Collection
    {
        $conversions = $this->conversions()->whereNotNull($column)
            ->select($column, DB::raw('count(*) as total'))->groupBy($column)->pluck('total', $column);

        return $this->visits()->whereNotNull($column)
            ->select($column.' as name', DB::raw('count(distinct visitor_hash) as visitors'))
            ->groupBy($column)->orderByDesc('visitors')->limit(10)->get()
            ->map(fn ($row) => ['name' => $row->name, 'visitors' => (int) $row->visitors, 'conversions' => (int) ($conversions[$row->name] ?? 0)]);
    }
}; ?>

@php
    $tableClass = 'overflow-hidden rounded-3xl border border-cream-300 bg-surface shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]';
    $maxViews = max(1, $this->daily->max('views'));
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <label for="an_days" class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Period</label>
        <select id="an_days" wire:model.live.number="days" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs font-semibold text-ink-700 focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <x-admin.stat-card icon="sparkles" label="Page views" :value="number_format($this->totals['views'])" palette="brand" />
        <x-admin.stat-card icon="users" label="Daily visitors, added up" :value="number_format($this->totals['visitors'])" palette="green" />
        <x-admin.stat-card icon="heart" label="Forms completed" :value="number_format($this->totals['conversions'])" palette="amber" />
    </div>

    <div class="{{ $tableClass }} p-5 sm:p-6">
        <h2 class="font-serif text-lg text-heading">Page views per day</h2>
        <ol class="mt-4 flex h-32 items-end gap-px sm:gap-1" aria-label="Page views per day">
            @foreach ($this->daily as $day)
                <li class="flex h-full min-w-0 flex-1 items-end" title="{{ $day['label'] }}: {{ $day['views'] }}">
                    <span class="sr-only">{{ $day['label'] }}: {{ $day['views'] }}</span>
                    <span class="block w-full rounded-t bg-brand-700" style="height: {{ max($day['views'] > 0 ? 3 : 0, round($day['views'] / $maxViews * 100)) }}%"></span>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="{{ $tableClass }}">
            <h2 class="px-6 pt-5 font-serif text-lg text-heading">Top pages</h2>
            <table class="mt-3 w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr><th class="px-6 py-3">Page</th><th class="px-6 py-3 text-right">Views</th><th class="px-6 py-3 text-right">Visitors</th></tr>
                </thead>
                <tbody class="divide-y divide-cream-200">
                    @forelse ($this->topPages as $page)
                        <tr>
                            <td class="break-all px-6 py-2.5 text-ink-900">{{ $page->path }}</td>
                            <td class="px-6 py-2.5 text-right text-ink-700">{{ number_format($page->views) }}</td>
                            <td class="px-6 py-2.5 text-right text-ink-700">{{ number_format($page->visitors) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-6 text-center text-ink-500">No visits in this period yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="{{ $tableClass }}">
            <h2 class="px-6 pt-5 font-serif text-lg text-heading">Forms completed</h2>
            <table class="mt-3 w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr><th class="px-6 py-3">Form</th><th class="px-6 py-3 text-right">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-cream-200">
                    @forelse ($this->conversionTypes as $conversion)
                        <tr>
                            <td class="px-6 py-2.5 text-ink-900">{{ \App\Models\SiteConversion::LABELS[$conversion->type] ?? $conversion->type }}</td>
                            <td class="px-6 py-2.5 text-right text-ink-700">{{ number_format($conversion->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-6 py-6 text-center text-ink-500">No forms completed in this period yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @foreach ([['Where visitors come from', $this->sources, 'No visits in this period yet.'], ['Campaigns', $this->campaigns, 'No campaign links used yet. Add ?utm_source=facebook&utm_campaign=christmas-appeal to the links you share.']] as [$heading, $rows, $empty])
            <div class="{{ $tableClass }}">
                <h2 class="px-6 pt-5 font-serif text-lg text-heading">{{ $heading }}</h2>
                <table class="mt-3 w-full text-left text-sm">
                    <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                        <tr><th class="px-6 py-3">Name</th><th class="px-6 py-3 text-right">Visitors</th><th class="px-6 py-3 text-right">Forms</th></tr>
                    </thead>
                    <tbody class="divide-y divide-cream-200">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="break-all px-6 py-2.5 text-ink-900">{{ $row['name'] }}</td>
                                <td class="px-6 py-2.5 text-right text-ink-700">{{ number_format($row['visitors']) }}</td>
                                <td class="px-6 py-2.5 text-right text-ink-700">{{ number_format($row['conversions']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-6 text-center text-ink-500">{{ $empty }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</div>
