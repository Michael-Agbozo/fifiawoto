<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit;
use App\Support\CsvCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only view of the audit trail. There is deliberately no edit or delete
 * action here, and AuditLog itself refuses updates and deletes.
 */
new class extends Component
{
    use WithPagination;

    public string $userFilter = '';

    public string $actionFilter = '';

    public string $fromDate = '';

    public string $toDate = '';

    public string $search = '';

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['userFilter', 'actionFilter', 'fromDate', 'toDate', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['userFilter', 'actionFilter', 'fromDate', 'toDate', 'search']);
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorizeAccess();

        $query = $this->buildQuery()->with('user');
        $filters = array_filter([
            'person' => $this->userFilter,
            'action' => $this->actionFilter,
            'from' => $this->fromDate,
            'to' => $this->toDate,
            'search' => $this->search,
        ]);

        Audit::record('audit_log.exported', 'Downloaded the audit log as CSV'.($filters ? ' (filtered).' : '.'), properties: [
            'filters' => $filters,
            'rows' => (clone $query)->count(),
        ]);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'Person', 'Action', 'Description', 'Record type', 'Record ID', 'Details', 'IP address']);

            foreach ($query->lazyByIdDesc(500, 'id') as $entry) {
                fputcsv($out, CsvCell::row([
                    $entry->created_at?->toDateTimeString(),
                    $this->actorName($entry),
                    $entry->action,
                    $entry->description,
                    $entry->subject_type ? class_basename($entry->subject_type) : null,
                    $entry->subject_id,
                    $entry->properties ? json_encode($entry->properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                    $entry->ip,
                ]));
            }

            fclose($out);
        }, 'audit-log-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function actorName(AuditLog $entry): string
    {
        return $entry->user?->name
            ?? ($entry->properties['actor_name'] ?? null)
            ?? ($entry->user_id ? 'Deleted user' : 'System or website visitor');
    }

    /**
     * The before/after pairs of an entry, for display. Fields changed without a
     * stored value (sensitive ones) show as changed only.
     *
     * @return array<int, array{field: string, before: ?string, after: ?string}>
     */
    public function changeRows(AuditLog $entry): array
    {
        $properties = $entry->properties ?? [];
        $before = (array) ($properties['before'] ?? []);
        $after = (array) ($properties['after'] ?? []);
        $fields = array_values(array_unique([...((array) ($properties['fields'] ?? [])), ...array_keys($before), ...array_keys($after)]));

        return array_map(fn (string $field): array => [
            'field' => Str::headline($field),
            'before' => array_key_exists($field, $before) ? $this->displayValue($before[$field]) : null,
            'after' => array_key_exists($field, $after) ? $this->displayValue($after[$field]) : null,
        ], $fields);
    }

    /**
     * Any other recorded details (counts, file names, filters...), flattened for display.
     *
     * @return array<string, string>
     */
    public function extraDetails(AuditLog $entry): array
    {
        $extra = array_diff_key($entry->properties ?? [], array_flip(['fields', 'before', 'after', 'actor_name']));

        return collect($extra)
            ->mapWithKeys(fn (mixed $value, string $key): array => [Str::headline($key) => $this->displayValue($value)])
            ->all();
    }

    protected function displayValue(mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '—',
            is_bool($value) => $value ? 'Yes' : 'No',
            is_array($value) => $value === [] ? '—' : (array_is_list($value) ? implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value)) : json_encode($value)),
            default => Str::limit((string) $value, 160),
        };
    }

    /** @return Builder<AuditLog> */
    protected function buildQuery(): Builder
    {
        $query = AuditLog::query();

        if (filled($this->userFilter)) {
            $this->userFilter === 'system'
                ? $query->whereNull('user_id')
                : $query->where('user_id', (int) $this->userFilter);
        }

        if (filled($this->actionFilter)) {
            str_ends_with($this->actionFilter, '.*')
                ? $query->where('action', 'like', Str::before($this->actionFilter, '.*').'.%')
                : $query->where('action', $this->actionFilter);
        }

        if ($from = $this->parsedDate($this->fromDate)) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to = $this->parsedDate($this->toDate)) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        if (filled($this->search)) {
            $query->where('description', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%');
        }

        return $query;
    }

    protected function parsedDate(string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /** Role-locked like user management: a per-user permission grant alone doesn't open it. */
    protected function authorizeAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->hasRole(UserRole::Owner, UserRole::SuperAdmin) && $user->canDo('audit_log', 'view'),
            403,
        );
    }

    #[Computed]
    public function entries()
    {
        return $this->buildQuery()
            ->with('user')
            ->latest('created_at')
            ->latest('id')
            ->paginate(25);
    }

    /** @return array<int, string> */
    #[Computed]
    public function people(): array
    {
        return User::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Action keys grouped by area, e.g. ['beneficiary' => ['beneficiary.created', ...]].
     *
     * @return array<string, array<int, string>>
     */
    #[Computed]
    public function actionGroups(): array
    {
        return AuditLog::query()->distinct()->orderBy('action')->pluck('action')
            ->groupBy(fn (string $action): string => Str::before($action, '.'))
            ->map(fn ($actions) => $actions->values()->all())
            ->all();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return filled($this->userFilter) || filled($this->actionFilter) || filled($this->fromDate) || filled($this->toDate) || filled($this->search);
    }
}; ?>

<div class="space-y-6">
    <x-admin.section-header
        title="Activity"
        :subtitle="$this->entries->total().' entr'.($this->entries->total() === 1 ? 'y' : 'ies').($this->hasFilters ? ' match the filters' : ' recorded').' · newest first'"
    >
        <x-slot:actions>
            <select wire:model.live="userFilter" aria-label="Person" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-heading hover:text-heading focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
                <option value="">Everyone</option>
                <option value="system">System or website visitor</option>
                @foreach ($this->people as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <select wire:model.live="actionFilter" aria-label="Action" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-heading hover:text-heading focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
                <option value="">All actions</option>
                @foreach ($this->actionGroups as $area => $actions)
                    <optgroup label="{{ \Illuminate\Support\Str::headline($area) }}">
                        <option value="{{ $area }}.*">All {{ \Illuminate\Support\Str::headline($area) }} actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <input type="date" wire:model.live="fromDate" title="From date" aria-label="From date" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs text-ink-700 focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
            <input type="date" wire:model.live="toDate" title="To date" aria-label="To date" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs text-ink-700 focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search descriptions" aria-label="Search descriptions" class="w-56 rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
            <x-admin.actions-menu>
                <button type="button" wire:click="exportCsv" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-heading">
                    <svg class="size-4 text-heading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5 5 5-5M12 4v12"/></svg>
                    Download CSV
                </button>
                @if ($this->hasFilters)
                    <button type="button" wire:click="clearFilters" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-heading">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Clear all filters
                    </button>
                @endif
            </x-admin.actions-menu>
        </x-slot:actions>
    </x-admin.section-header>

    <p class="text-xs text-ink-500">Entries can't be edited or deleted. Sensitive fields such as case notes, stories and contact details show as changed without their contents; passwords and security codes are never recorded.</p>

    <div class="overflow-hidden rounded-3xl border border-cream-300 bg-surface shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->entries->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">
                {{ $this->hasFilters ? 'No entries match the current filters.' : 'Nothing has been recorded yet.' }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                        <tr>
                            <th class="px-6 py-3">When</th>
                            <th class="px-6 py-3">Person</th>
                            <th class="px-6 py-3">What happened</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-200 text-sm">
                        @foreach ($this->entries as $entry)
                            @php
                                $changes = $this->changeRows($entry);
                                $details = $this->extraDetails($entry);
                            @endphp
                            <tr wire:key="audit-{{ $entry->id }}" class="align-top transition hover:bg-cream-100/40">
                                <td class="whitespace-nowrap px-6 py-4 text-ink-700">
                                    <p>{{ $entry->created_at?->format('M j, Y') }}</p>
                                    <p class="text-xs text-ink-500">{{ $entry->created_at?->format('g:i:s A') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-ink-900">{{ $this->actorName($entry) }}</p>
                                    @if ($entry->ip)
                                        <p class="font-mono text-xs text-ink-500">{{ $entry->ip }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-ink-900">{{ $entry->description }}</p>
                                    <p class="mt-1 font-mono text-[11px] text-ink-500">{{ $entry->action }}@if ($entry->subject_type) · {{ class_basename($entry->subject_type) }} #{{ $entry->subject_id }}@endif</p>

                                    @if ($changes || $details)
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-xs font-semibold text-heading">Details</summary>
                                            <dl class="mt-2 space-y-1 text-xs">
                                                @foreach ($changes as $change)
                                                    <div class="flex flex-wrap gap-x-2">
                                                        <dt class="font-semibold text-ink-700">{{ $change['field'] }}</dt>
                                                        <dd class="text-ink-500">
                                                            @if ($change['before'] === null && $change['after'] === null)
                                                                changed
                                                            @else
                                                                {{ $change['before'] ?? '—' }} → {{ $change['after'] ?? '—' }}
                                                            @endif
                                                        </dd>
                                                    </div>
                                                @endforeach
                                                @foreach ($details as $label => $value)
                                                    <div class="flex flex-wrap gap-x-2">
                                                        <dt class="font-semibold text-ink-700">{{ $label }}</dt>
                                                        <dd class="break-all text-ink-500">{{ $value }}</dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div>{{ $this->entries->links() }}</div>
</div>
