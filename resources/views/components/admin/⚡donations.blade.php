<?php

use App\Enums\PaymentMethod;
use App\Mail\DonationReceipt;
use App\Models\Donation;
use App\Models\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public bool $showForm = false;

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    // Filters
    public string $eventFilter = '';

    public string $fromDate = '';

    public string $toDate = '';

    public string $search = '';

    // Form fields
    #[Validate('required|string|max:120')]
    public string $donor_name = '';

    #[Validate('nullable|email|max:255')]
    public string $donor_email = '';

    #[Validate('required|numeric|min:0.01|max:1000000')]
    public string $amount = '';

    #[Validate('required|string|size:3')]
    public string $currency = 'USD';

    #[Validate('required|string|max:40')]
    public string $payment_method = 'mobile_money';

    #[Validate('nullable|integer|exists:events,id')]
    public ?int $event_id = null;

    #[Validate('required|date')]
    public string $received_at = '';

    #[Validate('nullable|string|max:120')]
    public string $external_reference = '';

    #[Validate('nullable|string|max:2000')]
    public string $notes = '';

    public function startCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->received_at = now()->toDateString();
        $this->payment_method = PaymentMethod::MobileMoney->value;
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $d = Donation::query()->findOrFail($id);
        $this->editingId = $d->id;
        $this->donor_name = $d->donor_name;
        $this->donor_email = (string) $d->donor_email;
        $this->amount = number_format($d->amount_cents / 100, 2, '.', '');
        $this->currency = $d->currency;
        $this->payment_method = $d->payment_method;
        $this->event_id = $d->event_id;
        $this->received_at = $d->received_at->toDateString();
        $this->external_reference = (string) $d->external_reference;
        $this->notes = (string) $d->notes;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('donations', $this->editingId ? 'update' : 'create'), 403);

        $data = $this->validate();

        $payload = [
            'donor_name' => $data['donor_name'],
            'donor_email' => $data['donor_email'] ?: null,
            'amount_cents' => (int) round(((float) $data['amount']) * 100),
            'currency' => strtoupper($data['currency']),
            'payment_method' => $data['payment_method'],
            'event_id' => $data['event_id'] ?: null,
            'received_at' => $data['received_at'],
            'external_reference' => $data['external_reference'] ?: null,
            'notes' => $data['notes'] ?: null,
            'recorded_by' => auth()->id(),
        ];

        if ($this->editingId) {
            Donation::query()->whereKey($this->editingId)->update($payload);
            $this->flashMessage = "Updated gift from {$data['donor_name']}.";
        } else {
            $donation = Donation::query()->create($payload);
            $this->flashMessage = "Recorded gift from {$data['donor_name']}.";

            if (filled($donation->donor_email)) {
                Mail::send(new DonationReceipt($donation->fresh('event')));
                $this->flashMessage .= ' Receipt emailed.';
            }
        }

        $this->resetForm();
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
        abort_unless(auth()->user()?->canDo('donations', 'delete'), 403);

        $d = Donation::query()->findOrFail($id);
        $name = $d->donor_name;
        $d->delete();

        $this->confirmDeleteId = null;
        $this->flashMessage = "Deleted gift from {$name}.";
    }

    public function exportCsv(): StreamedResponse
    {
        $query = $this->buildQuery();
        $rows = $query->orderBy('received_at')->get();

        $filename = 'donations-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Received at', 'Donor name', 'Donor email', 'Amount', 'Currency', 'Payment method', 'Event', 'External reference', 'Notes']);
            foreach ($rows as $d) {
                fputcsv($out, [
                    $d->received_at?->toDateString(),
                    $d->donor_name,
                    $d->donor_email,
                    number_format($d->amount_cents / 100, 2),
                    $d->currency,
                    $d->payment_method,
                    $d->event?->title,
                    $d->external_reference,
                    $d->notes,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function updatedEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->reset([
            'donor_name', 'donor_email', 'amount', 'payment_method', 'event_id',
            'external_reference', 'notes', 'showForm',
        ]);
        $this->currency = 'USD';
        $this->payment_method = PaymentMethod::MobileMoney->value;
        $this->resetErrorBag();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Donation>
     */
    protected function buildQuery()
    {
        $query = Donation::query()->with('event');

        if (filled($this->eventFilter)) {
            $query->where('event_id', $this->eventFilter);
        }

        if (filled($this->fromDate)) {
            $query->where('received_at', '>=', $this->fromDate);
        }

        if (filled($this->toDate)) {
            $query->where('received_at', '<=', $this->toDate);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('donor_name', 'like', $needle)
                    ->orWhere('donor_email', 'like', $needle)
                    ->orWhere('external_reference', 'like', $needle);
            });
        }

        return $query;
    }

    #[Computed]
    public function donations()
    {
        return $this->buildQuery()->latest('received_at')->paginate(15);
    }

    #[Computed]
    public function totals(): array
    {
        $filtered = (int) $this->buildQuery()->sum('amount_cents');
        $all = (int) Donation::query()->sum('amount_cents');

        return [
            'filtered' => $filtered,
            'all' => $all,
            'count' => (int) $this->buildQuery()->count(),
        ];
    }

    #[Computed]
    public function eventOptions(): array
    {
        return Event::query()
            ->orderBy('starts_at', 'desc')
            ->pluck('title', 'id')
            ->all();
    }
}; ?>

<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-cream-300 bg-white p-5">
            <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Total recorded</p>
            <p class="mt-2 font-serif text-3xl font-bold text-ink-900">${{ number_format($this->totals['all'] / 100) }}</p>
        </article>
        <article class="rounded-2xl border border-cream-300 bg-white p-5">
            <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">In current view</p>
            <p class="mt-2 font-serif text-3xl font-bold text-ink-900">${{ number_format($this->totals['filtered'] / 100) }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ $this->totals['count'] }} gift(s) match the filters</p>
        </article>
        <article class="rounded-2xl border border-cream-300 bg-white p-5">
            <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Gifts logged</p>
            <p class="mt-2 font-serif text-3xl font-bold text-ink-900">{{ $this->totals['count'] }}</p>
            <p class="mt-1 text-xs text-ink-500">Use the actions menu to export CSV</p>
        </article>
    </div>

    <x-admin.section-header
        title="Recorded gifts"
        :subtitle="$this->totals['count'].' gift(s) in view · $'.number_format($this->totals['filtered'] / 100).' total'"
    >
        <x-slot:actions>
            <select wire:model.live="eventFilter" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
                <option value="">All events</option>
                @foreach ($this->eventOptions as $id => $title)
                    <option value="{{ $id }}">{{ $title }}</option>
                @endforeach
            </select>
            <input type="date" wire:model.live="fromDate" title="From date" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-700 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <input type="date" wire:model.live="toDate" title="To date" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-700 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name, email, reference" class="w-56 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <button type="button" wire:click="startCreate" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Record donation
            </button>
            <a href="{{ route('admin.reports.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-cream-300 bg-white px-4 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 4 5-6"/></svg>
                Reports
            </a>
            <x-admin.actions-menu>
                <button type="button" wire:click="exportCsv" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                    <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5 5 5-5M12 4v12"/></svg>
                    Download CSV
                </button>
                @if (filled($eventFilter) || filled($fromDate) || filled($toDate) || filled($search))
                    <button type="button" wire:click="$reset('eventFilter','fromDate','toDate','search')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Clear all filters
                    </button>
                @endif
            </x-admin.actions-menu>
        </x-slot:actions>
    </x-admin.section-header>

    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700 hover:text-green-900" aria-label="Dismiss">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit donation' : 'Record a donation'" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Donor name</label>
                    <input type="text" wire:model="donor_name" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('donor_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Donor email <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                    <input type="email" wire:model="donor_email" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('donor_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Amount</label>
                    <input type="number" step="0.01" min="0.01" wire:model="amount" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Currency</label>
                    <input type="text" maxlength="3" wire:model="currency" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm uppercase text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Payment method</label>
                    <select wire:model.live="payment_method" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @foreach (\App\Enums\PaymentMethod::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @php $methodEnum = \App\Enums\PaymentMethod::tryFrom($payment_method); @endphp
                    @if ($methodEnum)
                        <p class="mt-1 text-xs text-ink-500">Reference hint: {{ $methodEnum->referenceHint() }}</p>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Event <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                    <select wire:model="event_id" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        <option value="">Unallocated</option>
                        @foreach ($this->eventOptions as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Received on</label>
                    <input type="date" wire:model="received_at" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('received_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">External reference <span class="font-normal normal-case tracking-normal text-ink-500">(processor id, cheque #)</span></label>
                <input type="text" wire:model="external_reference" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Notes</label>
                <textarea wire:model="notes" rows="3" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">
                    {{ $editingId ? 'Save changes' : 'Record donation' }}
                </button>
                <button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                    Cancel
                </button>
            </div>
        </form>
    </x-admin.modal>

    {{-- Table --}}
    <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->donations->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">No donations match the current filters.</div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr class="transition hover:bg-cream-100/40">
                        <th class="px-6 py-3">Received</th>
                        <th class="px-6 py-3">Donor</th>
                        <th class="px-6 py-3 text-right">Amount</th>
                        <th class="px-6 py-3">Event</th>
                        <th class="px-6 py-3">Method</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200 text-sm">
                    @foreach ($this->donations as $d)
                        <tr class="transition hover:bg-cream-100/40">
                            <td class="px-6 py-4 text-ink-700">{{ $d->received_at?->format('M j, Y') }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-ink-900">{{ $d->donor_name }}</p>
                                @if ($d->donor_email)
                                    <p class="text-xs text-ink-500">{{ $d->donor_email }}</p>
                                @endif
                                @if ($d->external_reference)
                                    <p class="mt-1 text-xs text-ink-500">Ref: {{ $d->external_reference }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-ink-900">
                                {{ $d->currency }} {{ number_format($d->amount_cents / 100, 2) }}
                            </td>
                            <td class="px-6 py-4 text-ink-700">{{ $d->event?->title ?? '—' }}</td>
                            <td class="px-6 py-4 text-ink-700">{{ \App\Enums\PaymentMethod::tryFrom($d->payment_method)?->label() ?? $d->payment_method }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end">
                                    @if ($confirmDeleteId === $d->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="delete({{ $d->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                            <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                        </div>
                                    @else
                                        <x-admin.actions-menu>
                                            <button type="button" wire:click="startEdit({{ $d->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button" wire:click="askDelete({{ $d->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
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

    <div>{{ $this->donations->links() }}</div>
</div>
