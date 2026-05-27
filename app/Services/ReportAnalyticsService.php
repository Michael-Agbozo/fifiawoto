<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerInterest;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\Donation;
use App\Models\Event;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use Illuminate\Support\Carbon;

class ReportAnalyticsService
{
    /**
     * Build analytics payload for the chosen category over [$from, $to].
     * The returned shape varies per category and is consumed by the reports view + PDF template.
     *
     * @return array<string, mixed>
     */
    public function for(string $category, string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        $days = max(1, $start->diffInDays($end) + 1);

        $priorStart = $start->copy()->subDays($days);
        $priorEnd = $start->copy()->subSecond();

        return match ($category) {
            'overview' => $this->overview($start, $end, $priorStart, $priorEnd),
            'donations' => $this->donations($start, $end, $priorStart, $priorEnd),
            'volunteers' => $this->volunteers($start, $end, $priorStart, $priorEnd),
            'events' => $this->events($start, $end, $priorStart, $priorEnd),
            'beneficiaries' => $this->beneficiaries($start, $end, $priorStart, $priorEnd),
            default => [],
        };
    }

    /**
     * @return array{value:int|float, delta:?float}
     */
    protected function withDelta(int|float $current, int|float $prior): array
    {
        if ($prior == 0) {
            return ['value' => $current, 'delta' => $current > 0 ? null : 0.0];
        }

        return [
            'value' => $current,
            'delta' => round((($current - $prior) / $prior) * 100, 1),
        ];
    }

    protected function overview(Carbon $start, Carbon $end, Carbon $priorStart, Carbon $priorEnd): array
    {
        $donationsCurrent = (int) Donation::query()->whereBetween('received_at', [$start, $end])->sum('amount_cents');
        $donationsPrior = (int) Donation::query()->whereBetween('received_at', [$priorStart, $priorEnd])->sum('amount_cents');

        $volunteerAppsCurrent = VolunteerApplication::query()->whereBetween('created_at', [$start, $end])->count();
        $volunteerAppsPrior = VolunteerApplication::query()->whereBetween('created_at', [$priorStart, $priorEnd])->count();

        $beneficiariesCurrent = Beneficiary::query()->whereBetween('created_at', [$start, $end])->count();
        $beneficiariesPrior = Beneficiary::query()->whereBetween('created_at', [$priorStart, $priorEnd])->count();

        $eventsCurrent = Event::query()->whereBetween('starts_at', [$start, $end])->count();
        $eventsPrior = Event::query()->whereBetween('starts_at', [$priorStart, $priorEnd])->count();

        // 12-month rolling donation line
        $line = [];
        $cursor = $end->copy()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $mStart = $cursor->copy();
            $mEnd = $cursor->copy()->endOfMonth();
            $line[] = [
                'label' => $mStart->format('M'),
                'sublabel' => $mStart->format('Y'),
                'cents' => (int) Donation::query()->whereBetween('received_at', [$mStart, $mEnd])->sum('amount_cents'),
            ];
            $cursor->subMonth();
        }
        $line = array_reverse($line);

        $beneficiaryCategories = $this->categoryBreakdown(SupportCategory::class, Beneficiary::query()->whereBetween('created_at', [$start, $end]), 'category');

        return [
            'kpis' => [
                'donations' => $this->withDelta($donationsCurrent / 100, $donationsPrior / 100),
                'volunteer_apps' => $this->withDelta($volunteerAppsCurrent, $volunteerAppsPrior),
                'beneficiaries' => $this->withDelta($beneficiariesCurrent, $beneficiariesPrior),
                'events' => $this->withDelta($eventsCurrent, $eventsPrior),
            ],
            'donation_line' => $line,
            'beneficiary_categories' => $beneficiaryCategories,
            'top_donors' => $this->topDonors($start, $end, 5),
            'top_events' => $this->topEvents($start, $end, 5),
        ];
    }

    protected function donations(Carbon $start, Carbon $end, Carbon $priorStart, Carbon $priorEnd): array
    {
        $currentRows = Donation::query()->whereBetween('received_at', [$start, $end])->get();
        $priorRows = Donation::query()->whereBetween('received_at', [$priorStart, $priorEnd])->get();

        $currentTotal = (int) $currentRows->sum('amount_cents');
        $priorTotal = (int) $priorRows->sum('amount_cents');

        $uniqueCurrent = $currentRows->pluck('donor_email')->filter()->unique()->count() + $currentRows->whereNull('donor_email')->pluck('donor_name')->unique()->count();
        $uniquePrior = $priorRows->pluck('donor_email')->filter()->unique()->count() + $priorRows->whereNull('donor_email')->pluck('donor_name')->unique()->count();

        $avgCurrent = $currentRows->count() > 0 ? $currentTotal / $currentRows->count() : 0;
        $avgPrior = $priorRows->count() > 0 ? $priorTotal / $priorRows->count() : 0;

        // Daily series — last min(60, range_days) days
        $days = (int) min(60, max(7, $start->diffInDays($end) + 1));
        $daily = [];
        $cursor = $end->copy()->startOfDay();
        for ($i = 0; $i < $days; $i++) {
            $dStart = $cursor->copy()->startOfDay();
            $dEnd = $cursor->copy()->endOfDay();
            $daily[] = [
                'date' => $cursor->toDateString(),
                'label' => $cursor->format('M j'),
                'cents' => (int) Donation::query()->whereBetween('received_at', [$dStart, $dEnd])->sum('amount_cents'),
            ];
            $cursor->subDay();
        }
        $daily = array_reverse($daily);

        $byMethod = $currentRows
            ->groupBy('payment_method')
            ->map(fn ($g) => ['cents' => (int) $g->sum('amount_cents'), 'count' => $g->count()])
            ->sortByDesc('cents')
            ->take(6)
            ->map(fn ($v, $k) => ['label' => $k, 'cents' => $v['cents'], 'count' => $v['count']])
            ->values()
            ->all();

        return [
            'kpis' => [
                'total' => $this->withDelta($currentTotal / 100, $priorTotal / 100),
                'count' => $this->withDelta($currentRows->count(), $priorRows->count()),
                'avg' => $this->withDelta(round($avgCurrent / 100, 2), round($avgPrior / 100, 2)),
                'unique_donors' => $this->withDelta($uniqueCurrent, $uniquePrior),
            ],
            'daily' => $daily,
            'by_method' => $byMethod,
            'top_donors' => $this->topDonors($start, $end, 10),
            'top_events' => $this->topEvents($start, $end, 5),
        ];
    }

    protected function volunteers(Carbon $start, Carbon $end, Carbon $priorStart, Carbon $priorEnd): array
    {
        $apps = VolunteerApplication::query()->whereBetween('created_at', [$start, $end])->get();
        $priorApps = VolunteerApplication::query()->whereBetween('created_at', [$priorStart, $priorEnd])->count();

        $approvedCount = $apps->where('status', VolunteerApplicationStatus::Approved)->count();
        $approvalRate = $apps->count() > 0 ? round(($approvedCount / $apps->count()) * 100, 1) : 0.0;

        $rosterCurrent = Volunteer::query()->whereBetween('assigned_at', [$start, $end])->count();
        $rosterPrior = Volunteer::query()->whereBetween('assigned_at', [$priorStart, $priorEnd])->count();

        $statusFunnel = [];
        foreach (VolunteerApplicationStatus::cases() as $s) {
            $statusFunnel[] = [
                'label' => $s->label(),
                'count' => $apps->where('status', $s)->count(),
                'palette' => $s->palette(),
            ];
        }

        // Interests breakdown (interests is JSON array)
        $interestCounts = [];
        foreach ($apps as $app) {
            foreach ((array) ($app->interests ?? []) as $interest) {
                $key = VolunteerInterest::tryFrom($interest)?->label() ?? $interest;
                $interestCounts[$key] = ($interestCounts[$key] ?? 0) + 1;
            }
        }
        arsort($interestCounts);
        $interestRows = collect($interestCounts)
            ->take(6)
            ->map(fn ($count, $label) => ['label' => $label, 'count' => $count])
            ->values()
            ->all();

        $availabilityCounts = [];
        foreach ($apps as $app) {
            $key = $app->availability?->label() ?? 'Unspecified';
            $availabilityCounts[$key] = ($availabilityCounts[$key] ?? 0) + 1;
        }
        arsort($availabilityCounts);

        $topCountries = $apps
            ->groupBy('country')
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $country) => ['label' => $country, 'count' => $count])
            ->values()
            ->all();

        return [
            'kpis' => [
                'applications' => $this->withDelta($apps->count(), $priorApps),
                'approval_rate' => ['value' => $approvalRate, 'delta' => null],
                'roster_added' => $this->withDelta($rosterCurrent, $rosterPrior),
                'countries' => ['value' => $apps->pluck('country')->unique()->count(), 'delta' => null],
            ],
            'funnel' => $statusFunnel,
            'interests' => $interestRows,
            'availability' => collect($availabilityCounts)->map(fn ($c, $k) => ['label' => $k, 'count' => $c])->values()->all(),
            'top_countries' => $topCountries,
        ];
    }

    protected function events(Carbon $start, Carbon $end, Carbon $priorStart, Carbon $priorEnd): array
    {
        $events = Event::query()
            ->whereBetween('starts_at', [$start, $end])
            ->withSum(['donations as raised_cents' => function ($q) use ($start, $end) {
                $q->whereBetween('received_at', [$start, $end]);
            }], 'amount_cents')
            ->get();

        $priorCount = Event::query()->whereBetween('starts_at', [$priorStart, $priorEnd])->count();

        $totalGoal = (int) $events->sum('goal_cents');
        $totalRaised = (int) $events->sum('raised_cents');
        $achievementRate = $totalGoal > 0 ? round(($totalRaised / $totalGoal) * 100, 1) : 0.0;

        $statusBreakdown = [];
        foreach (EventStatus::cases() as $s) {
            $statusBreakdown[] = [
                'label' => $s->label(),
                'count' => $events->where('status', $s)->count(),
                'palette' => $s->palette(),
            ];
        }

        $topByRaised = $events
            ->sortByDesc('raised_cents')
            ->take(5)
            ->map(fn ($e) => [
                'title' => $e->title,
                'status' => $e->status->label(),
                'raised_cents' => (int) $e->raised_cents,
                'goal_cents' => (int) $e->goal_cents,
            ])
            ->values()
            ->all();

        $topByGoalPct = $events
            ->filter(fn ($e) => $e->goal_cents > 0)
            ->map(function ($e) {
                $e->goal_pct = ($e->raised_cents / $e->goal_cents) * 100;

                return $e;
            })
            ->sortByDesc('goal_pct')
            ->take(5)
            ->map(fn ($e) => [
                'title' => $e->title,
                'pct' => round($e->goal_pct, 1),
                'raised_cents' => (int) $e->raised_cents,
                'goal_cents' => (int) $e->goal_cents,
            ])
            ->values()
            ->all();

        return [
            'kpis' => [
                'events' => $this->withDelta($events->count(), $priorCount),
                'goal' => ['value' => $totalGoal / 100, 'delta' => null],
                'raised' => ['value' => $totalRaised / 100, 'delta' => null],
                'achievement_pct' => ['value' => $achievementRate, 'delta' => null],
            ],
            'status_breakdown' => $statusBreakdown,
            'top_by_raised' => $topByRaised,
            'top_by_goal_pct' => $topByGoalPct,
        ];
    }

    protected function beneficiaries(Carbon $start, Carbon $end, Carbon $priorStart, Carbon $priorEnd): array
    {
        $current = Beneficiary::query()->whereBetween('created_at', [$start, $end])->get();
        $priorCount = Beneficiary::query()->whereBetween('created_at', [$priorStart, $priorEnd])->count();

        $applicationsCurrent = BeneficiaryApplication::query()->whereBetween('created_at', [$start, $end])->count();
        $applicationsPrior = BeneficiaryApplication::query()->whereBetween('created_at', [$priorStart, $priorEnd])->count();

        $convertedFromApps = BeneficiaryApplication::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('converted_beneficiary_id')
            ->count();
        $conversionRate = $applicationsCurrent > 0 ? round(($convertedFromApps / $applicationsCurrent) * 100, 1) : 0.0;

        $categoryBreakdown = [];
        foreach (SupportCategory::cases() as $c) {
            $categoryBreakdown[] = [
                'label' => $c->label(),
                'count' => $current->where('category', $c)->count(),
            ];
        }
        $categoryBreakdown = collect($categoryBreakdown)->sortByDesc('count')->values()->all();

        $statusBreakdown = [];
        foreach (SupportStatus::cases() as $s) {
            $statusBreakdown[] = [
                'label' => $s->label(),
                'count' => $current->where('status', $s)->count(),
                'palette' => $s->palette(),
            ];
        }

        $topCountries = $current
            ->groupBy('country')
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $country) => ['label' => $country, 'count' => $count])
            ->values()
            ->all();

        $genderBreakdown = $current
            ->groupBy(fn ($b) => $b->gender ?: 'unspecified')
            ->map(fn ($g) => $g->count())
            ->map(fn ($count, $gender) => ['label' => ucfirst($gender), 'count' => $count])
            ->values()
            ->all();

        return [
            'kpis' => [
                'records' => $this->withDelta($current->count(), $priorCount),
                'applications' => $this->withDelta($applicationsCurrent, $applicationsPrior),
                'conversion_rate' => ['value' => $conversionRate, 'delta' => null],
                'countries_served' => ['value' => $current->pluck('country')->unique()->count(), 'delta' => null],
            ],
            'by_category' => $categoryBreakdown,
            'by_status' => $statusBreakdown,
            'by_country' => $topCountries,
            'by_gender' => $genderBreakdown,
        ];
    }

    /**
     * @return array<int, array{donor_name:string, donor_email:?string, total_cents:int, gift_count:int}>
     */
    protected function topDonors(Carbon $start, Carbon $end, int $limit): array
    {
        return Donation::query()
            ->whereBetween('received_at', [$start, $end])
            ->selectRaw('donor_name, donor_email, SUM(amount_cents) as total_cents, COUNT(*) as gift_count')
            ->groupBy('donor_name', 'donor_email')
            ->orderByDesc('total_cents')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'donor_name' => (string) $r->donor_name,
                'donor_email' => $r->donor_email,
                'total_cents' => (int) $r->total_cents,
                'gift_count' => (int) $r->gift_count,
            ])
            ->all();
    }

    /**
     * @return array<int, array{title:string, status:string, raised_cents:int, goal_cents:int}>
     */
    protected function topEvents(Carbon $start, Carbon $end, int $limit): array
    {
        return Event::query()
            ->withSum(['donations as raised_cents' => function ($q) use ($start, $end) {
                $q->whereBetween('received_at', [$start, $end]);
            }], 'amount_cents')
            ->orderByDesc('raised_cents')
            ->limit($limit)
            ->get()
            ->map(fn ($e) => [
                'title' => (string) $e->title,
                'status' => $e->status->label(),
                'raised_cents' => (int) ($e->raised_cents ?? 0),
                'goal_cents' => (int) ($e->goal_cents ?? 0),
            ])
            ->all();
    }

    /**
     * Generic enum-based breakdown helper.
     */
    protected function categoryBreakdown(string $enumClass, $query, string $field): array
    {
        $counts = (clone $query)
            ->selectRaw("$field, COUNT(*) as total")
            ->groupBy($field)
            ->pluck('total', $field)
            ->all();

        return collect($enumClass::cases())
            ->map(fn ($case) => [
                'label' => $case->label(),
                'count' => (int) ($counts[$case->value] ?? 0),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
