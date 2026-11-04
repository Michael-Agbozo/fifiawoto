<?php

use App\Enums\PaymentMethod;
use App\Enums\SponsorshipStatus;
use App\Mail\DonationReceipt;
use App\Mail\SponsorshipActivated;
use App\Mail\SponsorshipReminder;
use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Sponsorship;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public string $search = '';

    public ?string $flashMessage = null;

    public ?string $errorMessage = null;

    // Add / edit form
    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $beneficiary_id = null;

    public string $sponsor_name = '';

    public string $sponsor_email = '';

    public string $sponsor_phone = '';

    public string $monthly_amount = '';

    public string $started_on = '';

    public bool $reminders_enabled = true;

    public string $notes = '';

    // Activate
    public ?int $activatingId = null;

    public string $activate_on = '';

    // Record payment
    public ?int $payingId = null;

    public string $payment_amount = '';

    public int $payment_months = 1;

    public string $payment_method = 'mobile_money';

    public string $payment_received_at = '';

    public string $payment_reference = '';

    public ?int $viewingId = null;

    public ?int $confirmDeleteId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'view'), 403);
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'create'), 403);

        $this->resetForm();
        $this->started_on = today()->toDateString();
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'update'), 403);

        $sponsorship = Sponsorship::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $sponsorship->id;
        $this->beneficiary_id = $sponsorship->beneficiary_id;
        $this->sponsor_name = $sponsorship->sponsor_name;
        $this->sponsor_email = $sponsorship->sponsor_email;
        $this->sponsor_phone = (string) $sponsorship->sponsor_phone;
        $this->monthly_amount = number_format($sponsorship->monthly_amount_cents / 100, 2, '.', '');
        $this->started_on = (string) $sponsorship->started_on?->toDateString();
        $this->reminders_enabled = $sponsorship->reminders_enabled;
        $this->notes = (string) $sponsorship->notes;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->reset(['activatingId', 'payingId', 'viewingId', 'confirmDeleteId']);
    }

    /**
     * Adding a sponsor by hand records an arrangement staff already agreed, so it starts active.
     */
    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', $this->editingId ? 'update' : 'create'), 403);

        $existing = $this->editingId ? Sponsorship::query()->findOrFail($this->editingId) : null;
        $isActive = ! $existing || $existing->status === SponsorshipStatus::Active;

        $data = $this->validate([
            'beneficiary_id' => $existing ? ['nullable'] : ['required', 'integer', Rule::exists('beneficiaries', 'id')],
            'sponsor_name' => ['required', 'string', 'max:130'],
            'sponsor_email' => ['required', 'email', 'max:255'],
            'sponsor_phone' => ['nullable', 'string', 'max:40'],
            'monthly_amount' => ['required', 'numeric', 'min:1', 'max:10000'],
            'started_on' => [$isActive ? 'required' : 'nullable', 'date'],
            'reminders_enabled' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = [
            'sponsor_name' => $data['sponsor_name'],
            'sponsor_email' => $data['sponsor_email'],
            'sponsor_phone' => $data['sponsor_phone'] ?: null,
            'monthly_amount_cents' => (int) round(((float) $data['monthly_amount']) * 100),
            'reminders_enabled' => $data['reminders_enabled'],
            'notes' => $data['notes'] ?: null,
        ];

        if ($existing) {
            $existing->update($payload + ['started_on' => $data['started_on'] ?: null]);
            $existing->syncNextDueOn();
            $this->flashMessage = "Updated {$data['sponsor_name']}.";
            $this->resetForm();

            return;
        }

        if ($this->hasActiveSponsor((int) $data['beneficiary_id'])) {
            $this->addError('beneficiary_id', 'This child already has an active sponsor.');

            return;
        }

        $sponsorship = Sponsorship::query()->create($payload + [
            'beneficiary_id' => $data['beneficiary_id'],
            'currency' => Money::baseCurrency(),
        ]);
        $sponsorship->activate(Carbon::parse($data['started_on']));

        $this->flashMessage = "Added {$data['sponsor_name']} as a sponsor.";
        $this->resetForm();
    }

    public function startActivate(int $id): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'update'), 403);

        $this->activatingId = Sponsorship::query()->findOrFail($id)->id;
        $this->activate_on = today()->toDateString();
        $this->resetErrorBag();
    }

    public function activate(): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'update'), 403);

        $this->validate(['activate_on' => ['required', 'date']]);

        $sponsorship = DB::transaction(function (): ?Sponsorship {
            $sponsorship = Sponsorship::query()->lockForUpdate()->findOrFail($this->activatingId);

            if ($sponsorship->status !== SponsorshipStatus::Pending || $this->hasActiveSponsor($sponsorship->beneficiary_id)) {
                return null;
            }

            $sponsorship->activate(Carbon::parse($this->activate_on));

            return $sponsorship;
        });

        if (! $sponsorship) {
            $this->addError('activate_on', 'This child already has an active sponsor, or this request was already handled.');

            return;
        }

        Mail::to($sponsorship->sponsor_email)->queue(new SponsorshipActivated($sponsorship->load('beneficiary')));

        $this->activatingId = null;
        $this->flashMessage = "{$sponsorship->sponsor_name} is now an active sponsor. Confirmation emailed.";
    }

    public function decline(int $id): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'update'), 403);

        $sponsorship = Sponsorship::query()->where('status', SponsorshipStatus::Pending->value)->findOrFail($id);
        $sponsorship->update(['status' => SponsorshipStatus::Declined]);
        $this->flashMessage = "Declined the request from {$sponsorship->sponsor_name}.";
    }

    public function end(int $id): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'update'), 403);

        $sponsorship = Sponsorship::query()->active()->findOrFail($id);
        $sponsorship->update(['status' => SponsorshipStatus::Ended, 'ended_on' => today()]);
        $this->flashMessage = "Ended {$sponsorship->sponsor_name}'s sponsorship. The child shows as available again.";
    }

    public function startPayment(int $id): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'update') && auth()->user()->canDo('donations', 'create'), 403);

        $sponsorship = Sponsorship::query()->active()->findOrFail($id);
        $this->payingId = $sponsorship->id;
        $this->payment_amount = number_format($sponsorship->monthly_amount_cents / 100, 2, '.', '');
        $this->payment_months = 1;
        $this->payment_method = PaymentMethod::MobileMoney->value;
        $this->payment_received_at = today()->toDateString();
        $this->payment_reference = '';
        $this->resetErrorBag();
    }

    /**
     * A payment is a normal donation linked to the pledge, so it counts in donation totals
     * and reports, gets the usual receipt, and moves the next due date forward.
     */
    public function recordPayment(): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'update') && auth()->user()->canDo('donations', 'create'), 403);

        $data = $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'payment_months' => ['required', 'integer', 'min:0', 'max:24'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_received_at' => ['required', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $sponsorship = Sponsorship::query()->active()->with('beneficiary')->findOrFail($this->payingId);

        $donation = Donation::query()->create([
            'sponsorship_id' => $sponsorship->id,
            'months_covered' => $data['payment_months'],
            'donor_name' => $sponsorship->sponsor_name,
            'donor_email' => $sponsorship->sponsor_email,
            'amount_cents' => (int) round(((float) $data['payment_amount']) * 100),
            'currency' => Money::baseCurrency(),
            'payment_method' => $data['payment_method'],
            'external_reference' => $data['payment_reference'] ?: null,
            'received_at' => $data['payment_received_at'],
            'recorded_by' => auth()->id(),
            'notes' => 'Sponsorship of '.$sponsorship->beneficiary->public_display_name,
        ]);

        Mail::queue(new DonationReceipt($donation->fresh('event')));

        $this->payingId = null;
        $this->flashMessage = "Recorded payment from {$sponsorship->sponsor_name}. Receipt emailed.";
    }

    public function sendReminder(int $id): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'update'), 403);

        $sponsorship = Sponsorship::query()->active()->with('beneficiary')->findOrFail($id);

        if (! $sponsorship->reminders_enabled) {
            $this->errorMessage = "{$sponsorship->sponsor_name} has turned off reminder emails.";

            return;
        }

        Mail::to($sponsorship->sponsor_email)->queue(new SponsorshipReminder($sponsorship));
        $sponsorship->update(['last_reminded_at' => now()]);
        $this->flashMessage = "Reminder sent to {$sponsorship->sponsor_name}.";
    }

    public function askDelete(int $id): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'delete'), 403);

        $this->confirmDeleteId = $id;
    }

    /** Linked donations stay on record; they just lose their pledge link. */
    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->canDo('sponsorships', 'delete'), 403);

        Sponsorship::query()->whereKey($id)->delete();
        $this->confirmDeleteId = null;
        $this->flashMessage = 'Sponsorship record deleted. Its donations are still on record.';
    }

    public function showDetails(int $id): void
    {
        $this->viewingId = Sponsorship::query()->findOrFail($id)->id;
    }

    private function hasActiveSponsor(int $beneficiaryId): bool
    {
        return Sponsorship::query()->active()->where('beneficiary_id', $beneficiaryId)->exists();
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'beneficiary_id', 'sponsor_name', 'sponsor_email', 'sponsor_phone', 'monthly_amount', 'started_on', 'notes']);
        $this->reminders_enabled = true;
        $this->resetErrorBag();
    }

    #[Computed]
    public function sponsorships()
    {
        $query = Sponsorship::query()->with('beneficiary');

        match ($this->filter) {
            'overdue' => $query->overdue(),
            'all' => null,
            default => $query->where('status', $this->filter),
        };

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(fn (Builder $q) => $q->where('sponsor_name', 'like', $needle)
                ->orWhere('sponsor_email', 'like', $needle)
                ->orWhereHas('beneficiary', fn (Builder $child) => $child->where('public_display_name', 'like', $needle)->orWhere('full_name', 'like', $needle)));
        }

        return $query->orderByRaw("case status when 'pending' then 0 when 'active' then 1 else 2 end")->orderBy('next_due_on')->latest('id')->paginate(20);
    }

    /** @return array{pending: int, active: int, overdue: int, monthly_cents: int} */
    #[Computed]
    public function totals(): array
    {
        return [
            'pending' => Sponsorship::query()->where('status', SponsorshipStatus::Pending->value)->count(),
            'active' => Sponsorship::query()->active()->count(),
            'overdue' => Sponsorship::query()->overdue()->count(),
            'monthly_cents' => (int) Sponsorship::query()->active()->sum('monthly_amount_cents'),
        ];
    }

    /** Children with a public profile and no active sponsor. */
    #[Computed]
    public function availableChildren()
    {
        return Beneficiary::query()->publicSponsorship()
            ->whereDoesntHave('sponsorships', fn ($sponsorships) => $sponsorships->active())
            ->orderBy('public_display_name')
            ->get(['id', 'public_display_name', 'full_name']);
    }

    #[Computed]
    public function viewing(): ?Sponsorship
    {
        return $this->viewingId
            ? Sponsorship::query()->with(['beneficiary', 'payments' => fn ($payments) => $payments->latest('received_at')])->find($this->viewingId)
            : null;
    }
}; ?>

@php
    $fieldClass = 'mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30';
    $labelClass = 'block text-xs font-semibold uppercase tracking-[0.22em] text-label';
    $menuItem = 'flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-heading';
    $canUpdate = auth()->user()?->canDo('sponsorships', 'update');
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['pending', 'Waiting for review', (string) $this->totals['pending'], 'sparkles', 'amber'],
            ['active', 'Active sponsors', (string) $this->totals['active'], 'heart', 'green'],
            ['overdue', 'Overdue', (string) $this->totals['overdue'], 'shield', 'red'],
            ['all', 'Pledged per month', \App\Support\Money::fromCents($this->totals['monthly_cents']), 'heart', 'brand'],
        ] as [$value, $label, $count, $icon, $palette])
            <x-admin.stat-card wire:click="$set('filter', '{{ $value }}')" :icon="$icon" :label="$label" :value="$count" :active="$filter === $value" :palette="$palette" />
        @endforeach
    </div>

    <x-admin.section-header title="Sponsors" :subtitle="$this->sponsorships->total().' in view'">
        <x-slot:actions>
            <select wire:model.live="filter" aria-label="Filter by status" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs font-semibold text-ink-700 focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
                <option value="all">All</option>
                <option value="overdue">Overdue</option>
                @foreach (\App\Enums\SponsorshipStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search sponsor or child" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
            @if (auth()->user()?->canDo('sponsorships', 'create'))
                <button type="button" wire:click="startCreate" class="btn btn-primary gap-2 px-4 py-2 text-xs">Add sponsor</button>
            @endif
        </x-slot:actions>
    </x-admin.section-header>

    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700" aria-label="Dismiss">×</button>
        </div>
    @endif

    @if ($errorMessage)
        <div role="alert" class="flex items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <span>{{ $errorMessage }}</span>
            <button type="button" wire:click="$set('errorMessage', null)" class="text-red-700" aria-label="Dismiss">×</button>
        </div>
    @endif

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit sponsor' : 'Add sponsor'" subtitle="Use this for arrangements already agreed outside the website." size="lg" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            @unless ($editingId)
                <div>
                    <label for="sp_child" class="{{ $labelClass }}">Child</label>
                    <select id="sp_child" wire:model="beneficiary_id" class="{{ $fieldClass }}">
                        <option value="">Choose a child…</option>
                        @foreach ($this->availableChildren as $child)
                            <option value="{{ $child->id }}">{{ $child->public_display_name }} ({{ $child->full_name }})</option>
                        @endforeach
                    </select>
                    @error('beneficiary_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endunless
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="sp_name" class="{{ $labelClass }}">Sponsor name</label>
                    <input id="sp_name" type="text" wire:model="sponsor_name" class="{{ $fieldClass }}">
                    @error('sponsor_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sp_email" class="{{ $labelClass }}">Email</label>
                    <input id="sp_email" type="email" wire:model="sponsor_email" class="{{ $fieldClass }}">
                    @error('sponsor_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sp_phone" class="{{ $labelClass }}">Phone</label>
                    <input id="sp_phone" type="text" wire:model="sponsor_phone" class="{{ $fieldClass }}">
                    @error('sponsor_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sp_amount" class="{{ $labelClass }}">Monthly pledge ({{ \App\Support\Money::baseCurrency() }})</label>
                    <input id="sp_amount" type="number" step="0.01" min="1" wire:model="monthly_amount" class="{{ $fieldClass }}">
                    @error('monthly_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sp_start" class="{{ $labelClass }}">Start date</label>
                    <input id="sp_start" type="date" wire:model="started_on" class="{{ $fieldClass }}">
                    <p class="mt-1 text-xs text-ink-500">The first monthly gift is due on this date.</p>
                    @error('started_on') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex cursor-pointer items-center gap-3 self-center text-sm text-ink-700">
                    <input type="checkbox" wire:model="reminders_enabled" class="size-4 rounded border-cream-300 text-gold-500 focus:ring-gold-500">
                    <span>Send reminder emails</span>
                </label>
            </div>
            <div>
                <label for="sp_notes" class="{{ $labelClass }}">Staff notes</label>
                <textarea id="sp_notes" wire:model="notes" rows="3" class="{{ $fieldClass }}"></textarea>
                @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" class="btn btn-primary px-6 py-3 text-sm disabled:opacity-60">{{ $editingId ? 'Save changes' : 'Add sponsor' }}</button>
                <button type="button" wire:click="cancel" class="btn btn-ghost px-6 py-3 text-sm">Cancel</button>
            </div>
        </form>
    </x-admin.modal>

    <x-admin.modal :show="$activatingId !== null" title="Approve sponsorship" subtitle="The sponsor gets a confirmation email, and the child shows as sponsored on the website." size="sm" onClose="cancel">
        <form wire:submit="activate" class="space-y-5">
            <div>
                <label for="sp_activate" class="{{ $labelClass }}">Start date</label>
                <input id="sp_activate" type="date" wire:model="activate_on" class="{{ $fieldClass }}">
                <p class="mt-1 text-xs text-ink-500">The first monthly gift is due on this date.</p>
                @error('activate_on') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled" class="btn btn-primary px-6 py-3 text-sm disabled:opacity-60">Approve and email sponsor</button>
                <button type="button" wire:click="cancel" class="btn btn-ghost px-6 py-3 text-sm">Cancel</button>
            </div>
        </form>
    </x-admin.modal>

    <x-admin.modal :show="$payingId !== null" title="Record payment" subtitle="Saved as a donation, with a receipt emailed to the sponsor." size="md" onClose="cancel">
        <form wire:submit="recordPayment" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="pay_amount" class="{{ $labelClass }}">Amount ({{ \App\Support\Money::baseCurrency() }})</label>
                    <input id="pay_amount" type="number" step="0.01" min="0.01" wire:model="payment_amount" class="{{ $fieldClass }}">
                    @error('payment_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="pay_months" class="{{ $labelClass }}">Months covered</label>
                    <select id="pay_months" wire:model.number="payment_months" class="{{ $fieldClass }}">
                        <option value="0">None (one-time gift)</option>
                        @foreach ([1, 2, 3, 6, 12] as $months)
                            <option value="{{ $months }}">{{ $months }}</option>
                        @endforeach
                    </select>
                    @error('payment_months') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="pay_method" class="{{ $labelClass }}">Method</label>
                    <select id="pay_method" wire:model="payment_method" class="{{ $fieldClass }}">
                        @foreach (\App\Enums\PaymentMethod::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="pay_date" class="{{ $labelClass }}">Received on</label>
                    <input id="pay_date" type="date" wire:model="payment_received_at" class="{{ $fieldClass }}">
                    @error('payment_received_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="pay_ref" class="{{ $labelClass }}">Reference</label>
                <input id="pay_ref" type="text" wire:model="payment_reference" class="{{ $fieldClass }}">
                @error('payment_reference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled" class="btn btn-primary px-6 py-3 text-sm disabled:opacity-60">Record payment</button>
                <button type="button" wire:click="cancel" class="btn btn-ghost px-6 py-3 text-sm">Cancel</button>
            </div>
        </form>
    </x-admin.modal>

    @if ($this->viewing)
        <x-admin.modal :show="true" :title="$this->viewing->sponsor_name" :subtitle="'Sponsoring '.$this->viewing->beneficiary?->public_display_name" size="lg" onClose="cancel">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-ink-500">Email</dt><dd class="break-all text-ink-900">{{ $this->viewing->sponsor_email }}</dd></div>
                <div><dt class="text-xs text-ink-500">Phone</dt><dd class="text-ink-900">{{ $this->viewing->sponsor_phone ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-500">Country</dt><dd class="text-ink-900">{{ $this->viewing->country ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-500">Mailing address</dt><dd class="text-ink-900">{{ $this->viewing->mailing_address ?: '—' }}</dd></div>
                <div><dt class="text-xs text-ink-500">One-time gift offered</dt><dd class="text-ink-900">{{ $this->viewing->one_time_gift_cents ? \App\Support\Money::fromCents($this->viewing->one_time_gift_cents, 2) : '—' }}</dd></div>
                <div><dt class="text-xs text-ink-500">Last reminder</dt><dd class="text-ink-900">{{ $this->viewing->last_reminded_at?->format('M j, Y') ?? '—' }}{{ $this->viewing->reminders_enabled ? '' : ' (reminders off)' }}</dd></div>
            </dl>
            @if (filled($this->viewing->notes))
                <p class="mt-4 whitespace-pre-line rounded-lg bg-cream-100 p-3 text-sm text-ink-700">{{ $this->viewing->notes }}</p>
            @endif
            <h3 class="mt-6 text-xs font-semibold uppercase tracking-[0.22em] text-label">Payments</h3>
            <ul class="mt-2 divide-y divide-cream-200 text-sm">
                @forelse ($this->viewing->payments as $payment)
                    <li class="flex flex-wrap justify-between gap-2 py-2">
                        <span class="text-ink-700">{{ $payment->received_at->format('M j, Y') }} · {{ $payment->months_covered ? \Illuminate\Support\Str::plural('month', $payment->months_covered, true) : 'One-time gift' }}</span>
                        <span class="font-semibold text-ink-900">{{ \App\Support\Money::fromCents($payment->amount_cents, 2) }}</span>
                    </li>
                @empty
                    <li class="py-2 text-ink-500">No payments recorded yet.</li>
                @endforelse
            </ul>
        </x-admin.modal>
    @endif

    <div class="overflow-x-auto rounded-3xl border border-cream-300 bg-surface shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->sponsorships->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">No sponsorships match this view yet.</div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr>
                        <th class="px-6 py-3">Sponsor</th>
                        <th class="px-6 py-3">Child</th>
                        <th class="px-6 py-3">Monthly</th>
                        <th class="px-6 py-3">Next due</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200">
                    @foreach ($this->sponsorships as $sponsorship)
                        <tr class="transition hover:bg-cream-100/40" wire:key="sponsorship-{{ $sponsorship->id }}">
                            <td class="px-6 py-3">
                                <p class="font-semibold text-ink-900">{{ $sponsorship->sponsor_name }}</p>
                                <p class="break-all text-xs text-ink-500">{{ $sponsorship->sponsor_email }}</p>
                            </td>
                            <td class="px-6 py-3 text-ink-700">
                                @if ($sponsorship->beneficiary && ! $sponsorship->beneficiary->trashed())
                                    <a href="{{ route('admin.beneficiaries.show', $sponsorship->beneficiary) }}" class="hover:text-heading hover:underline" wire:navigate>{{ $sponsorship->beneficiary->public_display_name ?: $sponsorship->beneficiary->full_name }}</a>
                                @else
                                    {{ $sponsorship->beneficiary?->public_display_name ?? '—' }}
                                @endif
                            </td>
                            <td class="px-6 py-3 text-ink-700">{{ \App\Support\Money::fromCents($sponsorship->monthly_amount_cents, 2) }}</td>
                            <td class="px-6 py-3 text-xs {{ $sponsorship->isOverdue() ? 'font-semibold text-red-600' : 'text-ink-500' }}">
                                {{ $sponsorship->status === \App\Enums\SponsorshipStatus::Active ? $sponsorship->next_due_on?->format('M j, Y') : '—' }}
                            </td>
                            <td class="px-6 py-3">
                                @if ($sponsorship->isOverdue())
                                    <x-admin.status-pill palette="red" label="Overdue" />
                                @else
                                    <x-admin.status-pill :palette="$sponsorship->status->palette()" :label="$sponsorship->status->label()" />
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end">
                                    @if ($confirmDeleteId === $sponsorship->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="delete({{ $sponsorship->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                            <button type="button" wire:click="cancel" class="btn btn-ghost px-3 py-1.5 text-xs">Cancel</button>
                                        </div>
                                    @else
                                        <x-admin.actions-menu>
                                            <button type="button" wire:click="showDetails({{ $sponsorship->id }})" @click="open = false" class="{{ $menuItem }}">View details</button>
                                            @if ($canUpdate)
                                                @if ($sponsorship->status === \App\Enums\SponsorshipStatus::Pending)
                                                    <button type="button" wire:click="startActivate({{ $sponsorship->id }})" @click="open = false" class="{{ $menuItem }}">Approve</button>
                                                    <button type="button" wire:click="decline({{ $sponsorship->id }})" @click="open = false" wire:confirm="Decline this request?" class="{{ $menuItem }}">Decline</button>
                                                @elseif ($sponsorship->status === \App\Enums\SponsorshipStatus::Active)
                                                    @if (auth()->user()->canDo('donations', 'create'))
                                                        <button type="button" wire:click="startPayment({{ $sponsorship->id }})" @click="open = false" class="{{ $menuItem }}">Record payment</button>
                                                    @endif
                                                    <button type="button" wire:click="sendReminder({{ $sponsorship->id }})" @click="open = false" class="{{ $menuItem }}">Send reminder</button>
                                                    <button type="button" wire:click="end({{ $sponsorship->id }})" @click="open = false" wire:confirm="End this sponsorship? The child will show as available again." class="{{ $menuItem }}">End sponsorship</button>
                                                @endif
                                                <button type="button" wire:click="startEdit({{ $sponsorship->id }})" @click="open = false" class="{{ $menuItem }}">Edit</button>
                                            @endif
                                            @if (auth()->user()?->canDo('sponsorships', 'delete'))
                                                <button type="button" wire:click="askDelete({{ $sponsorship->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">Delete</button>
                                            @endif
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

    <div>{{ $this->sponsorships->links() }}</div>
</div>
