<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Enums\VolunteerApplicationStatus;
use App\Models\Beneficiary;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\VolunteerApplication;
use App\Models\VolunteerHourLog;
use App\Support\Money;
use App\Support\SiteSettings;
use Illuminate\Support\Carbon;

class ImpactMetricService
{
    /**
     * Figures for the public home page. Saved baselines cover the work done before this
     * system existed: children and hours are added on top of them, while communities and
     * countries use whichever is higher, because new records often repeat places already counted.
     *
     * @return array{children: int, communities: int, countries: int, volunteer_hours: int}
     */
    public function publicStats(): array
    {
        $supported = Beneficiary::query()->whereIn('status', [
            SupportStatus::Approved->value,
            SupportStatus::Active->value,
            SupportStatus::Completed->value,
        ]);

        $children = (clone $supported)->where(fn ($query) => $query
            ->where('category', SupportCategory::ChildEducation->value)
            ->orWhereDate('date_of_birth', '>', today()->subYears(18)->toDateString()))
            ->count();

        return [
            'children' => (int) SiteSettings::get('baseline_children') + $children,
            'communities' => max((int) SiteSettings::get('baseline_communities'), (clone $supported)->whereNotNull('region')->distinct()->count('region')),
            'countries' => max((int) SiteSettings::get('baseline_countries'), (clone $supported)->whereNotNull('country')->distinct()->count('country')),
            'volunteer_hours' => (int) SiteSettings::get('baseline_volunteer_hours') + (int) floor((float) VolunteerHourLog::query()->sum('hours')),
        ];
    }

    /**
     * KPI cards with current value + month-over-month delta.
     *
     * @return array<int, array{label: string, value: string, raw: int|float, delta: float|null, icon: string, hint: string|null}>
     */
    public function dashboardCards(): array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $monthStart->copy()->subSecond();

        // Month-to-date is compared with the same span of last month (e.g. 1st–28th vs 1st–28th),
        // capped at last month's end for short months.
        $previousSpanEnd = $previousMonthStart->copy()
            ->addSeconds((int) $monthStart->diffInSeconds($now))
            ->min($previousMonthEnd);

        $thisMonthDonations = (int) Donation::query()
            ->inBaseCurrency()
            ->whereBetween('received_at', [$monthStart, $now])
            ->sum('amount_cents');
        $lastMonthDonations = (int) Donation::query()
            ->inBaseCurrency()
            ->whereBetween('received_at', [$previousMonthStart, $previousSpanEnd])
            ->sum('amount_cents');

        $activeBeneficiaries = Beneficiary::query()->active()->count();
        $lastMonthBeneficiaries = Beneficiary::query()
            ->where('created_at', '<', $previousMonthEnd)
            ->whereIn('status', [SupportStatus::Approved->value, SupportStatus::Active->value])
            ->count();

        $upcomingEvents = Event::query()
            ->where('status', EventStatus::Published->value)
            ->where('starts_at', '>=', $now->copy()->startOfDay())
            ->count();
        $previousUpcoming = Event::query()
            ->where('status', EventStatus::Published->value)
            ->whereBetween('starts_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $activeVolunteers = VolunteerApplication::query()
            ->where('status', VolunteerApplicationStatus::Approved->value)
            ->count();
        $lastMonthVolunteers = VolunteerApplication::query()
            ->where('reviewed_at', '<', $previousMonthEnd)
            ->where('status', VolunteerApplicationStatus::Approved->value)
            ->count();

        return [
            [
                'label' => 'Donations this month',
                'value' => Money::fromCents($thisMonthDonations),
                'raw' => $thisMonthDonations,
                'delta' => $this->percentDelta($thisMonthDonations, $lastMonthDonations),
                'icon' => 'heart',
                'hint' => 'vs same period last month',
            ],
            [
                'label' => 'Active beneficiaries',
                'value' => (string) $activeBeneficiaries,
                'raw' => $activeBeneficiaries,
                'delta' => $this->percentDelta($activeBeneficiaries, $lastMonthBeneficiaries),
                'icon' => 'users',
                'hint' => 'Approved or actively supported',
            ],
            [
                'label' => 'Upcoming events',
                'value' => (string) $upcomingEvents,
                'raw' => $upcomingEvents,
                'delta' => $this->percentDelta($upcomingEvents, $previousUpcoming),
                'icon' => 'calendar',
                'hint' => 'Published programmes still ahead',
            ],
            [
                'label' => 'Active volunteers',
                'value' => (string) $activeVolunteers,
                'raw' => $activeVolunteers,
                'delta' => $this->percentDelta($activeVolunteers, $lastMonthVolunteers),
                'icon' => 'hand',
                'hint' => $this->pendingVolunteerApplications().' awaiting review',
            ],
        ];
    }

    /**
     * Daily donation totals for the last N days. Used by the bar chart.
     *
     * @return array<int, array{date: string, label: string, total_cents: int}>
     */
    public function dailyDonations(int $days = 30): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);

        $rows = Donation::query()
            ->inBaseCurrency()
            ->selectRaw('DATE(received_at) as day, SUM(amount_cents) as total')
            ->whereBetween('received_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $series[] = [
                'date' => $key,
                'label' => $date->format('M j'),
                'total_cents' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Cumulative donation totals for the last N days (line chart series).
     *
     * @return array<int, array{label: string, value: int}>
     */
    public function cumulativeDonations(int $days = 30): array
    {
        $running = 0;
        $series = [];

        foreach ($this->dailyDonations($days) as $day) {
            $running += $day['total_cents'];
            $series[] = [
                'label' => $day['label'],
                'value' => $running,
            ];
        }

        return $series;
    }

    public function totalDonations(): string
    {
        return Money::fromCents($this->totalDonationsCents(), 2);
    }

    public function totalDonationsCents(): int
    {
        return (int) Donation::query()->inBaseCurrency()->sum('amount_cents');
    }

    public function pendingVolunteerApplications(): int
    {
        return VolunteerApplication::query()
            ->where('status', VolunteerApplicationStatus::New->value)
            ->count();
    }

    public function newContactMessages(): int
    {
        return ContactMessage::query()
            ->where('status', 'new')
            ->count();
    }

    public function newsletterSubscriberCount(): int
    {
        return NewsletterSubscriber::query()
            ->whereNull('unsubscribed_at')
            ->count();
    }

    /**
     * Recent activity feed for the dashboard table. Pass $kind (volunteer,
     * contact, donation, newsletter) to only read that source, so the limit
     * applies after filtering.
     *
     * @return array<int, array{when: Carbon|null, kind: string, label: string, status: string, status_class: string, name: string, detail: string, category: string}>
     */
    public function recentActivity(int $limit = 12, ?string $kind = null): array
    {
        $entries = collect();
        $wants = fn (string $source): bool => $kind === null || $kind === $source;

        if ($wants('volunteer')) {
            VolunteerApplication::query()
                ->latest()
                ->limit($limit)
                ->get()
                ->each(function (VolunteerApplication $a) use ($entries) {
                    $entries->push([
                        'when' => $a->created_at,
                        'kind' => 'volunteer',
                        'name' => $a->full_name,
                        'detail' => $a->email ?? $a->phone,
                        'category' => 'Volunteer application',
                        'label' => 'Volunteer',
                        'status' => $a->status->label(),
                        'status_class' => $this->statusClass($a->status->value),
                    ]);
                });
        }

        if ($wants('contact')) {
            ContactMessage::query()
                ->latest()
                ->limit($limit)
                ->get()
                ->each(function (ContactMessage $m) use ($entries) {
                    $entries->push([
                        'when' => $m->created_at,
                        'kind' => 'contact',
                        'name' => $m->full_name,
                        'detail' => $m->email,
                        'category' => $m->subject->label(),
                        'label' => 'Contact',
                        'status' => $m->status->label(),
                        'status_class' => $this->statusClass($m->status->value),
                    ]);
                });
        }

        if ($wants('donation')) {
            Donation::query()
                ->with('event')
                ->latest('received_at')
                ->limit($limit)
                ->get()
                ->each(function (Donation $d) use ($entries) {
                    $entries->push([
                        'when' => $d->received_at,
                        'kind' => 'donation',
                        'name' => $d->donor_name,
                        'detail' => $d->currency === Money::baseCurrency()
                            ? Money::fromCents($d->amount_cents)
                            : $d->currency.' '.number_format($d->amount_cents / 100),
                        'category' => $d->event?->title ?? 'Unallocated',
                        'label' => 'Donation',
                        'status' => 'Recorded',
                        'status_class' => $this->statusClass('completed'),
                    ]);
                });
        }

        if ($wants('newsletter')) {
            NewsletterSubscriber::query()
                ->latest()
                ->limit($limit)
                ->get()
                ->each(function (NewsletterSubscriber $s) use ($entries) {
                    $entries->push([
                        'when' => $s->subscribed_at,
                        'kind' => 'newsletter',
                        'name' => $s->email,
                        'detail' => $s->name ?? '—',
                        'category' => 'Newsletter',
                        'label' => 'Subscriber',
                        'status' => 'Subscribed',
                        'status_class' => $this->statusClass('completed'),
                    ]);
                });
        }

        return $entries
            ->sortByDesc(fn ($e) => optional($e['when'])->timestamp ?? 0)
            ->take($limit)
            ->values()
            ->all();
    }

    private function percentDelta(int|float $current, int|float $previous): ?float
    {
        if ($previous == 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function statusClass(string $status): string
    {
        return match (true) {
            in_array($status, ['new', 'pending_review', 'pending']) => 'amber',
            in_array($status, ['under_review', 'in_progress']) => 'blue',
            in_array($status, ['approved', 'completed', 'active', 'resolved']) => 'green',
            in_array($status, ['rejected', 'archived', 'cancelled']) => 'red',
            default => 'gray',
        };
    }
}
