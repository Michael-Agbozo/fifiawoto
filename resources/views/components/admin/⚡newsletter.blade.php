<?php

use App\Models\NewsletterSubscriber;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'active';

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function unsubscribe(int $id): void
    {
        $sub = NewsletterSubscriber::query()->findOrFail($id);
        $sub->update(['unsubscribed_at' => now()]);
        $this->flashMessage = "{$sub->email} unsubscribed.";
    }

    public function resubscribe(int $id): void
    {
        $sub = NewsletterSubscriber::query()->findOrFail($id);
        $sub->update(['unsubscribed_at' => null, 'subscribed_at' => $sub->subscribed_at ?? now()]);
        $this->flashMessage = "{$sub->email} resubscribed.";
    }

    public function askDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'delete'), 403);

        NewsletterSubscriber::query()->whereKey($id)->delete();
        $this->confirmDeleteId = null;
        $this->flashMessage = 'Subscriber removed.';
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = $this->buildQuery()->orderBy('email')->get();
        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Name', 'Source', 'Subscribed at', 'Unsubscribed at']);
            foreach ($rows as $sub) {
                fputcsv($out, [
                    $sub->email,
                    $sub->name,
                    $sub->source,
                    $sub->subscribed_at?->toDateTimeString(),
                    $sub->unsubscribed_at?->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function buildQuery()
    {
        $q = NewsletterSubscriber::query();

        if ($this->filter === 'active') {
            $q->whereNull('unsubscribed_at');
        } elseif ($this->filter === 'unsubscribed') {
            $q->whereNotNull('unsubscribed_at');
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $q->where(fn ($w) => $w->where('email', 'like', $needle)->orWhere('name', 'like', $needle));
        }

        return $q;
    }

    #[Computed]
    public function subscribers()
    {
        return $this->buildQuery()->latest('subscribed_at')->paginate(20);
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'all' => NewsletterSubscriber::query()->count(),
            'active' => NewsletterSubscriber::query()->whereNull('unsubscribed_at')->count(),
            'unsubscribed' => NewsletterSubscriber::query()->whereNotNull('unsubscribed_at')->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach ([
            ['all',          'All subscribers', $this->counts['all'],          'sparkles', 'brand'],
            ['active',       'Active',          $this->counts['active'],       'sparkles', 'green'],
            ['unsubscribed', 'Unsubscribed',    $this->counts['unsubscribed'], 'shield',   'gray'],
        ] as [$value, $label, $count, $icon, $palette])
            <x-admin.stat-card
                wire:click="$set('filter', '{{ $value }}')"
                :icon="$icon"
                :label="$label"
                :value="(string) $count"
                :active="$filter === $value"
                :palette="$palette"
            />
        @endforeach
    </div>

    <x-admin.section-header
        title="Subscriber list"
        :subtitle="$this->subscribers->total().' subscriber(s) in view'"
    >
        <x-slot:actions>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name or email" class="w-72 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <button type="button" wire:click="exportCsv" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5 5 5-5M12 4v12"/></svg>
                Export CSV
            </button>
        </x-slot:actions>
    </x-admin.section-header>

    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700">×</button>
        </div>
    @endif

    <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->subscribers->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">No subscribers match this view yet.</div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Source</th>
                        <th class="px-6 py-3">Subscribed</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200 text-sm">
                    @foreach ($this->subscribers as $sub)
                        <tr class="transition hover:bg-cream-100/40">
                            <td class="px-6 py-3 font-semibold text-ink-900">{{ $sub->email }}</td>
                            <td class="px-6 py-3 text-ink-700">{{ $sub->name ?: '—' }}</td>
                            <td class="px-6 py-3 text-ink-700">{{ $sub->source ?: '—' }}</td>
                            <td class="px-6 py-3 text-xs text-ink-500">{{ $sub->subscribed_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-6 py-3">
                                @if ($sub->unsubscribed_at)
                                    <x-admin.status-pill palette="gray" :label="'Unsubscribed '.$sub->unsubscribed_at->diffForHumans()" />
                                @else
                                    <x-admin.status-pill palette="green" label="Active" />
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end">
                                    @if ($confirmDeleteId === $sub->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="delete({{ $sub->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                            <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700">Cancel</button>
                                        </div>
                                    @else
                                        <x-admin.actions-menu>
                                            @if ($sub->unsubscribed_at)
                                                <button type="button" wire:click="resubscribe({{ $sub->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                    <svg class="size-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                                    Resubscribe
                                                </button>
                                            @else
                                                <button type="button" wire:click="unsubscribe({{ $sub->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                    <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58"/></svg>
                                                    Unsubscribe
                                                </button>
                                            @endif
                                            <button type="button" wire:click="askDelete({{ $sub->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                                Delete
                                            </button>
                                        </x-admin.actions-menu>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div>{{ $this->subscribers->links() }}</div>
</div>
