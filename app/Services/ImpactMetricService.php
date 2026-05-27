<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\SupportStatus;
use App\Enums\VolunteerApplicationStatus;
use App\Models\Beneficiary;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\VolunteerApplication;
use Illuminate\Support\Carbon;

class ImpactMetricService
{
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

        $thisMonthDonations = (int) Donation::query()
            ->whereBetween('received_at', [$monthStart, $now])
            ->sum('amount_cents');
        $lastMonthDonations = (int) Donation::query()
            ->whereBetween('received_at', [$previousMonthStart, $previousMonthEnd])
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
                'value' => '$'.number_format($thisMonthDonations / 100),
                'raw' => $thisMonthDonations,
                'delta' => $this->percentDelta($thisMonthDonations, $lastMonthDonations),
                'icon' => 'heart',
                'hint' => 'vs last month',
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
        $cents = (int) Donation::query()->sum('amount_cents');

        return '$'.number_format($cents / 100, 2);
    }

    public function totalDonationsCents(): int
    {
        return (int) Donation::query()->sum('amount_cents');
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
     * Recent activity feed for the dashboard table.
     *
     * @return array<int, array{when: Carbon|null, kind: string, label: string, status: string, status_class: string, name: string, detail: string, category: string}>
     */
    public function recentActivity(int $limit = 12): array
    {
        $entries = collect();

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
                    'detail' => '$'.number_format($d->amount_cents / 100),
                    'category' => $d->event?->title ?? 'Unallocated',
                    'label' => 'Donation',
                    'status' => 'Recorded',
                    'status_class' => $this->statusClass('completed'),
                ]);
            });

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

        return $entries
            ->sortByDesc(fn ($e) => optional($e['when'])->timestamp ?? 0)
            ->take($limit)
            ->values()
            ->all();
    }

    private function percentDelta(int|float $current, int|float $previous): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : null;
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
