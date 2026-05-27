<?php

use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Event;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use App\Services\ReportAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component
{
    public string $category = 'overview';

    public string $fromDate = '';

    public string $toDate = '';

    public function mount(): void
    {
        $this->fromDate = now()->subMonths(3)->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    protected function rowsForCategory(): array
    {
        $from = $this->fromDate ?: '1970-01-01';
        $to = $this->toDate ?: now()->toDateString();

        return match ($this->category) {
            'donations' => Donation::query()
                ->with('event')
                ->whereBetween('received_at', [$from, $to])
                ->orderBy('received_at')
                ->get()
                ->map(fn ($d) => [
                    'Received at' => $d->received_at?->toDateString(),
                    'Donor' => $d->donor_name,
                    'Amount' => number_format($d->amount_cents / 100, 2),
                    'Currency' => $d->currency,
                    'Method' => $d->payment_method,
                    'Event' => $d->event?->title ?? '',
                ])
                ->all(),
            'volunteers' => VolunteerApplication::query()
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->get()
                ->map(fn ($a) => [
                    'Submitted' => $a->created_at?->toDateString(),
                    'Name' => $a->full_name,
                    'Email' => $a->email,
                    'Country' => $a->country,
                    'Availability' => $a->availability->label(),
                    'Status' => $a->status->label(),
                ])
                ->all(),
            'events' => Event::query()
                ->whereBetween('starts_at', [$from, $to])
                ->withSum('donations as raised_cents', 'amount_cents')
                ->orderBy('starts_at')
                ->get()
                ->map(fn ($e) => [
                    'Starts' => $e->starts_at?->toDateString(),
                    'Title' => $e->title,
                    'Location' => $e->location.', '.$e->country,
                    'Status' => $e->status->label(),
                    'Goal' => $e->goal_cents ? number_format($e->goal_cents / 100, 2) : '—',
                    'Raised' => number_format(((int) $e->raised_cents) / 100, 2),
                ])
                ->all(),
            'beneficiaries' => Beneficiary::query()
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->get()
                ->map(fn ($b) => [
                    'Recorded' => $b->created_at?->toDateString(),
                    'Name' => $b->full_name,
                    'Country' => $b->country,
                    'Region' => $b->region,
                    'Category' => $b->category->label(),
                    'Status' => $b->status->label(),
                ])
                ->all(),
            default => [],
        };
    }

    #[Computed]
    public function rows(): array
    {
        return $this->rowsForCategory();
    }

    #[Computed]
    public function analytics(): array
    {
        $from = $this->fromDate ?: '1970-01-01';
        $to = $this->toDate ?: now()->toDateString();

        return app(ReportAnalyticsService::class)->for($this->category, $from, $to);
    }

    public function downloadCsv(): StreamedResponse
    {
        $rows = $this->rowsForCategory();
        $filename = "report-{$this->category}-".now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if (empty($rows)) {
                fputcsv($out, ['No rows in selected range']);
            } else {
                fputcsv($out, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function downloadPdf()
    {
        $filename = "report-{$this->category}-".now()->format('Y-m-d-His').'.pdf';

        $pdf = Pdf::loadView('reports.pdf', [
            'category' => $this->category,
            'categoryLabel' => $this->categoryLabel(),
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
            'rows' => $this->rowsForCategory(),
            'analytics' => $this->analytics,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(fn () => print ($pdf->output()), $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'overview' => 'Foundation overview',
            'donations' => 'Donations',
            'volunteers' => 'Volunteer applications',
            'events' => 'Events',
            'beneficiaries' => 'Beneficiaries',
            default => 'Report',
        };
    }
}; ?>

@php
    $categories = [
        'overview' => 'Overview',
        'donations' => 'Donations',
        'volunteers' => 'Volunteers',
        'events' => 'Events',
        'beneficiaries' => 'Beneficiaries',
    ];
@endphp

<div class="space-y-6">
    <x-admin.section-header
        title="Reports & analytics"
        :subtitle="$categoryLabel = $this->categoryLabel().' · '.($this->fromDate ?: 'beginning').' → '.($this->toDate ?: 'today')"
    >
        <x-slot:actions>
            <input type="date" wire:model.live="fromDate" title="From date" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-700 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <input type="date" wire:model.live="toDate" title="To date" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-700 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <button type="button" wire:click="downloadPdf" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l2 2 4-4M5 19h14M7 4h10v8H7z"/></svg>
                Download PDF
            </button>
            <x-admin.actions-menu>
                <button type="button" wire:click="downloadCsv" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                    <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5 5 5-5M12 4v12"/></svg>
                    Download CSV
                </button>
                <button type="button" wire:click="downloadPdf" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                    <svg class="size-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l2 2 4-4M5 19h14M7 4h10v8H7z"/></svg>
                    Download PDF
                </button>
                @if (filled($fromDate) || filled($toDate))
                    <button type="button" wire:click="$reset('fromDate','toDate')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Clear date range
                    </button>
                @endif
            </x-admin.actions-menu>
        </x-slot:actions>
    </x-admin.section-header>

    {{-- Category tabs --}}
    <div class="flex flex-wrap gap-1 border-b border-cream-200">
        @foreach ($categories as $value => $label)
            <button
                type="button"
                wire:click="$set('category', '{{ $value }}')"
                @class([
                    'font-sans -mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] transition',
                    'border-brand-900 text-brand-900' => $category === $value,
                    'border-transparent text-ink-500 hover:text-ink-900' => $category !== $value,
                ])
            >{{ $label }}</button>
        @endforeach
    </div>

    @php
        $analytics = $this->analytics;
    @endphp

    @if ($category === 'overview')
        @include('admin.reports.partials.overview', ['analytics' => $analytics])
    @elseif ($category === 'donations')
        @include('admin.reports.partials.donations', ['analytics' => $analytics])
    @elseif ($category === 'volunteers')
        @include('admin.reports.partials.volunteers', ['analytics' => $analytics])
    @elseif ($category === 'events')
        @include('admin.reports.partials.events', ['analytics' => $analytics])
    @elseif ($category === 'beneficiaries')
        @include('admin.reports.partials.beneficiaries', ['analytics' => $analytics])
    @endif

    {{-- Detail table (skipped on overview) --}}
    @if ($category !== 'overview')
        <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <header class="border-b border-cream-200 px-6 py-4">
                <h3 class="font-serif text-lg font-bold text-ink-900">Detail table</h3>
                <p class="mt-0.5 text-xs text-ink-500">{{ count($this->rows) }} row(s) in this view · export via the actions menu</p>
            </header>
            @if (empty($this->rows))
                <div class="p-10 text-center text-sm text-ink-500">No rows for that category and range.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                            <tr>
                                @foreach (array_keys($this->rows[0]) as $heading)
                                    <th class="px-6 py-3">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cream-200 text-sm">
                            @foreach ($this->rows as $row)
                                <tr class="transition hover:bg-cream-100/40">
                                    @foreach ($row as $cell)
                                        <td class="px-6 py-3 text-ink-700">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
