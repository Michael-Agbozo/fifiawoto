<?php

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Enums\VolunteerRole;
use App\Mail\VolunteerApplicationDecision;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $tab = 'applications';

    public string $applicationFilter = 'new';

    public string $rosterFilter = '';

    public string $rosterSearch = '';

    /** @var array<int, string> */
    public array $applicationRoleChoice = [];

    public ?int $editingVolunteerId = null;

    public string $editingRole = '';

    public ?int $confirmRemoveId = null;

    public ?int $viewingApplicationId = null;

    public ?int $viewingVolunteerId = null;

    public ?string $flashMessage = null;

    public function viewApplication(int $id): void
    {
        $this->viewingApplicationId = $id;
    }

    public function viewVolunteer(int $id): void
    {
        $this->viewingVolunteerId = $id;
    }

    public function closeView(): void
    {
        $this->viewingApplicationId = null;
        $this->viewingVolunteerId = null;
    }

    public function mount(): void
    {
        $this->tab = request()->query('tab') === 'roster' ? 'roster' : 'applications';
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab === 'roster' ? 'roster' : 'applications';
        $this->resetPage();
    }

    public function updatedApplicationFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRosterFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRosterSearch(): void
    {
        $this->resetPage();
    }

    public function approve(int $applicationId): void
    {
        abort_unless(auth()->user()?->canDo('volunteers', 'update'), 403);

        $application = VolunteerApplication::query()->findOrFail($applicationId);

        if ($application->status === VolunteerApplicationStatus::Approved) {
            $this->flashMessage = 'Application is already approved.';

            return;
        }

        $roleValue = $this->applicationRoleChoice[$applicationId] ?? VolunteerRole::Event->value;

        if (! in_array($roleValue, array_column(VolunteerRole::cases(), 'value'), true)) {
            $this->addError('approve_'.$applicationId, 'Pick a role before approving.');

            return;
        }

        $application->update([
            'status' => VolunteerApplicationStatus::Approved->value,
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        Volunteer::query()->updateOrCreate(
            ['email' => $application->email],
            [
                'application_id' => $application->id,
                'full_name' => $application->full_name,
                'phone' => $application->phone,
                'country' => $application->country,
                'role' => $roleValue,
                'assigned_at' => now()->toDateString(),
            ],
        );

        unset($this->applicationRoleChoice[$applicationId]);

        Mail::send(new VolunteerApplicationDecision(
            $application,
            approved: true,
            roleLabel: VolunteerRole::from($roleValue)->label(),
        ));

        $this->flashMessage = "{$application->full_name} added to the active roster. Welcome email sent.";
    }

    public function reject(int $applicationId): void
    {
        abort_unless(auth()->user()?->canDo('volunteers', 'update'), 403);

        $application = VolunteerApplication::query()->findOrFail($applicationId);

        $application->update([
            'status' => VolunteerApplicationStatus::Rejected->value,
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        Mail::send(new VolunteerApplicationDecision($application, approved: false));

        $this->flashMessage = "Application from {$application->full_name} marked as rejected. Notification email sent.";
    }

    public function startEditRole(int $volunteerId): void
    {
        $volunteer = Volunteer::query()->findOrFail($volunteerId);
        $this->editingVolunteerId = $volunteer->id;
        $this->editingRole = $volunteer->role->value;
    }

    public function cancelEditRole(): void
    {
        $this->editingVolunteerId = null;
        $this->editingRole = '';
    }

    public function saveRole(int $volunteerId): void
    {
        abort_unless(auth()->user()?->canDo('volunteers', 'update'), 403);

        if (! in_array($this->editingRole, array_column(VolunteerRole::cases(), 'value'), true)) {
            $this->addError('role_'.$volunteerId, 'Pick a valid role.');

            return;
        }

        $volunteer = Volunteer::query()->findOrFail($volunteerId);
        $volunteer->update(['role' => $this->editingRole]);

        $this->editingVolunteerId = null;
        $this->editingRole = '';
        $this->flashMessage = "Role updated for {$volunteer->full_name}.";
    }

    public function askRemove(int $volunteerId): void
    {
        $this->confirmRemoveId = $volunteerId;
    }

    public function cancelRemove(): void
    {
        $this->confirmRemoveId = null;
    }

    public function remove(int $volunteerId): void
    {
        abort_unless(auth()->user()?->canDo('volunteers', 'delete'), 403);

        $volunteer = Volunteer::query()->findOrFail($volunteerId);
        $name = $volunteer->full_name;
        $volunteer->delete();

        $this->confirmRemoveId = null;
        $this->flashMessage = "{$name} removed from the active roster.";
    }

    #[Computed]
    public function applications()
    {
        $query = VolunteerApplication::query()->latest();

        if (filled($this->applicationFilter)) {
            $query->where('status', $this->applicationFilter);
        }

        return $query->paginate(10, pageName: 'apps');
    }

    #[Computed]
    public function applicationCounts(): array
    {
        return VolunteerApplication::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    #[Computed]
    public function volunteers()
    {
        $query = Volunteer::query()->latest('assigned_at');

        if (filled($this->rosterFilter)) {
            $query->where('role', $this->rosterFilter);
        }

        if (filled($this->rosterSearch)) {
            $needle = '%'.$this->rosterSearch.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('full_name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('country', 'like', $needle);
            });
        }

        return $query->paginate(10, pageName: 'roster');
    }

    #[Computed]
    public function viewingApplication(): ?VolunteerApplication
    {
        return $this->viewingApplicationId
            ? VolunteerApplication::query()->with('reviewer')->find($this->viewingApplicationId)
            : null;
    }

    #[Computed]
    public function viewingVolunteer(): ?Volunteer
    {
        return $this->viewingVolunteerId
            ? Volunteer::query()->with('application')->find($this->viewingVolunteerId)
            : null;
    }

    public function with(): array
    {
        return [
            'roleOptions' => VolunteerRole::options(),
            'statusOptions' => collect(VolunteerApplicationStatus::cases())
                ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                ->all(),
        ];
    }
}; ?>

<div class="space-y-6" x-data>
    {{-- Tabs --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-cream-300">
        <button
            type="button"
            wire:click="switchTab('applications')"
            @class([
                'font-sans -mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold uppercase tracking-[0.18em] transition',
                'border-gold-500 text-gold-500' => $tab === 'applications',
                'border-transparent text-ink-500 hover:text-ink-700' => $tab !== 'applications',
            ])
        >
            Applications
            <span class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-cream-200 px-2 py-0.5 text-[10px] font-bold text-ink-700">
                {{ $this->applicationCounts[\App\Enums\VolunteerApplicationStatus::New->value] ?? 0 }}
            </span>
        </button>
        <button
            type="button"
            wire:click="switchTab('roster')"
            @class([
                'font-sans -mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold uppercase tracking-[0.18em] transition',
                'border-gold-500 text-gold-500' => $tab === 'roster',
                'border-transparent text-ink-500 hover:text-ink-700' => $tab !== 'roster',
            ])
        >
            Active roster
            <span class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-cream-200 px-2 py-0.5 text-[10px] font-bold text-ink-700">
                {{ \App\Models\Volunteer::query()->count() }}
            </span>
        </button>
    </div>

    {{-- Flash --}}
    @if ($flashMessage)
        <div
            role="status"
            class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
        >
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700 hover:text-green-900" aria-label="Dismiss">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- APPLICATIONS --}}
    @if ($tab === 'applications')
        <div class="flex flex-wrap items-center gap-3">
            <label class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Filter</label>
            <select
                wire:model.live="applicationFilter"
                class="rounded-lg border border-cream-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
            >
                <option value="">All applications</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }} ({{ $this->applicationCounts[$value] ?? 0 }})</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            @if ($this->applications->isEmpty())
                <div class="p-10 text-center text-sm text-ink-500">No applications match this filter yet.</div>
            @else
                <table class="w-full text-left text-sm">
                    <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                        <tr class="transition hover:bg-cream-100/40">
                            <th class="px-6 py-3">Applicant</th>
                            <th class="px-6 py-3">Country</th>
                            <th class="px-6 py-3">Availability</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-200 text-sm">
                        @foreach ($this->applications as $app)
                            <tr class="align-top transition hover:bg-cream-100/40">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-ink-900">{{ $app->full_name }}</p>
                                    <p class="text-xs text-ink-500">{{ $app->email }}</p>
                                    <p class="mt-1 text-xs text-ink-500">{{ $app->phone }}</p>
                                    <p class="mt-2 max-w-xs text-xs text-ink-500">
                                        {{ \Illuminate\Support\Str::limit($app->motivation, 120) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-ink-700">{{ $app->country }}</td>
                                <td class="px-6 py-4 text-ink-700">{{ $app->availability->label() }}</td>
                                <td class="px-6 py-4">
                                    <x-admin.status-pill :palette="$app->status->palette()" :label="$app->status->label()" />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.actions-menu>
                                            <button type="button" wire:click="viewApplication({{ $app->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                View details
                                            </button>
                                            @if ($app->status === \App\Enums\VolunteerApplicationStatus::New)
                                                <button type="button" wire:click="viewApplication({{ $app->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                    <svg class="size-4 text-gold-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                                    Review &amp; approve
                                                </button>
                                                <button type="button" wire:click="reject({{ $app->id }})" @click="open = false" wire:confirm="Reject this volunteer application?" wire:loading.attr="disabled" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50 disabled:opacity-60">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                                    Reject application
                                                </button>
                                            @endif
                                        </x-admin.actions-menu>
                                        @if ($app->status !== \App\Enums\VolunteerApplicationStatus::New)
                                            <p class="text-right text-xs text-ink-500">
                                                Reviewed
                                                @if ($app->reviewed_at) {{ $app->reviewed_at->diffForHumans() }} @endif
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div>{{ $this->applications->links() }}</div>
    @endif

    {{-- ROSTER --}}
    @if ($tab === 'roster')
        <div class="flex flex-wrap items-center gap-3">
            <label class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Role</label>
            <select
                wire:model.live="rosterFilter"
                class="rounded-lg border border-cream-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
            >
                <option value="">All roles</option>
                @foreach ($roleOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <label class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Search</label>
            <input
                type="search"
                wire:model.live.debounce.300ms="rosterSearch"
                placeholder="Name, email, or country"
                class="rounded-lg border border-cream-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
            >
        </div>

        <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            @if ($this->volunteers->isEmpty())
                <div class="p-10 text-center text-sm text-ink-500">No active volunteers match this filter.</div>
            @else
                <table class="w-full text-left text-sm">
                    <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                        <tr class="transition hover:bg-cream-100/40">
                            <th class="px-6 py-3">Volunteer</th>
                            <th class="px-6 py-3">Country</th>
                            <th class="px-6 py-3">Role</th>
                            <th class="px-6 py-3">Assigned</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-200 text-sm">
                        @foreach ($this->volunteers as $volunteer)
                            <tr class="transition hover:bg-cream-100/40">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-ink-900">{{ $volunteer->full_name }}</p>
                                    <p class="text-xs text-ink-500">{{ $volunteer->email }}</p>
                                    @if ($volunteer->phone)
                                        <p class="text-xs text-ink-500">{{ $volunteer->phone }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-ink-700">{{ $volunteer->country }}</td>
                                <td class="px-6 py-4">
                                    @if ($editingVolunteerId === $volunteer->id)
                                        <select
                                            wire:model="editingRole"
                                            class="rounded-lg border border-cream-300 bg-white px-2 py-1.5 text-xs text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                                        >
                                            @foreach ($roleOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('role_'.$volunteer->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    @else
                                        <x-admin.status-pill palette="brand" :label="$volunteer->role->label()" />
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-ink-500">
                                    {{ $volunteer->assigned_at?->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end">
                                        @if ($editingVolunteerId === $volunteer->id)
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" wire:click="saveRole({{ $volunteer->id }})" class="inline-flex items-center rounded-lg bg-gold-500 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-brand-900">Save role</button>
                                                <button type="button" wire:click="cancelEditRole" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                            </div>
                                        @elseif ($confirmRemoveId === $volunteer->id)
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" wire:click="remove({{ $volunteer->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm remove</button>
                                                <button type="button" wire:click="cancelRemove" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                            </div>
                                        @else
                                            <x-admin.actions-menu>
                                                <button type="button" wire:click="viewVolunteer({{ $volunteer->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                    <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    View details
                                                </button>
                                                <button type="button" wire:click="startEditRole({{ $volunteer->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                    <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                                    Edit role
                                                </button>
                                                <button type="button" wire:click="askRemove({{ $volunteer->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                                    Remove from roster
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

        <div>{{ $this->volunteers->links() }}</div>
    @endif

    <x-admin.modal :show="(bool) $this->viewingApplication" :title="$this->viewingApplication?->full_name ?? 'Volunteer application'" :subtitle="'Volunteer application · '.($this->viewingApplication?->status->label() ?? '')" size="xl" onClose="closeView">
        @if ($app = $this->viewingApplication)
            <div class="grid gap-6 lg:grid-cols-2">
                <section>
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Contact</h3>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-ink-500">Email</dt>
                            <dd class="mt-0.5 break-all text-sm text-ink-900">{{ $app->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Phone</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->phone }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-ink-500">Country</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->country }}</dd>
                        </div>
                    </dl>
                </section>

                <section>
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Workflow</h3>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-ink-500">Submitted</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->created_at?->format('M j, Y · g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Consent</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->consented_at?->format('M j, Y') ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Reviewed</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->reviewed_at?->format('M j, Y') ?: 'Not yet' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Reviewer</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->reviewer?->name ?: '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section class="mt-6">
                <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Availability &amp; interests</h3>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-ink-500">Availability</dt>
                        <dd class="mt-0.5 text-sm text-ink-900">{{ $app->availability->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-500">Interests</dt>
                        <dd class="mt-1 flex flex-wrap gap-1.5">
                            @foreach (($app->interests ?? []) as $interest)
                                @php $enum = \App\Enums\VolunteerInterest::tryFrom($interest); @endphp
                                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $enum?->label() ?? $interest }}</span>
                            @endforeach
                            @if (empty($app->interests))
                                <span class="text-sm text-ink-500">None listed</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            @if (filled($app->skills))
                <section class="mt-5">
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Skills</h3>
                    <p class="mt-2 whitespace-pre-line rounded-2xl bg-cream-50 px-4 py-3 text-sm leading-relaxed text-ink-900">{{ $app->skills }}</p>
                </section>
            @endif

            <section class="mt-5">
                <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Why they want to help</h3>
                <p class="mt-2 whitespace-pre-line rounded-2xl bg-cream-50 px-4 py-3 text-sm leading-relaxed text-ink-900">{{ $app->motivation }}</p>
            </section>

            @if ($app->status === \App\Enums\VolunteerApplicationStatus::New)
                <section class="mt-6 rounded-2xl border border-cream-300 bg-cream-50 p-4">
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Approve into roster</h3>
                    <p class="mt-1 text-xs text-ink-500">Pick a starting role for {{ $app->full_name }} and approve. A welcome email will be sent.</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <select wire:model="applicationRoleChoice.{{ $app->id }}" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                            <option value="">Assign role…</option>
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="approve({{ $app->id }})" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-bold text-white transition hover:bg-brand-900">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            Approve applicant
                        </button>
                        <button type="button" wire:click="reject({{ $app->id }})" wire:confirm="Reject this volunteer application?" class="inline-flex items-center gap-2 rounded-xl border border-cream-300 px-4 py-2 text-xs font-semibold text-ink-700 transition hover:border-red-500 hover:text-red-600">
                            Reject
                        </button>
                    </div>
                    @error('approve_'.$app->id) <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </section>
            @endif
        @endif
    </x-admin.modal>

    <x-admin.modal :show="(bool) $this->viewingVolunteer" :title="$this->viewingVolunteer?->full_name ?? 'Volunteer'" :subtitle="'Active volunteer · '.($this->viewingVolunteer?->role->label() ?? '')" size="xl" onClose="closeView">
        @if ($volunteer = $this->viewingVolunteer)
            <div class="grid gap-6 lg:grid-cols-2">
                <section>
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Contact</h3>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-ink-500">Email</dt>
                            <dd class="mt-0.5 break-all text-sm text-ink-900">{{ $volunteer->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Phone</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $volunteer->phone ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-ink-500">Country</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $volunteer->country }}</dd>
                        </div>
                    </dl>
                </section>

                <section>
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Assignment</h3>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-ink-500">Role</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $volunteer->role->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Assigned</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $volunteer->assigned_at?->format('M j, Y') ?: '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            @if (filled($volunteer->notes))
                <section class="mt-5">
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Notes</h3>
                    <p class="mt-2 whitespace-pre-line rounded-2xl bg-cream-50 px-4 py-3 text-sm leading-relaxed text-ink-900">{{ $volunteer->notes }}</p>
                </section>
            @endif

            @if ($volunteer->application)
                <section class="mt-5 border-t border-cream-200 pt-5">
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Original application</h3>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-ink-500">Availability</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $volunteer->application->availability->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Interests</dt>
                            <dd class="mt-1 flex flex-wrap gap-1.5">
                                @foreach (($volunteer->application->interests ?? []) as $interest)
                                    @php $enum = \App\Enums\VolunteerInterest::tryFrom($interest); @endphp
                                    <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $enum?->label() ?? $interest }}</span>
                                @endforeach
                                @if (empty($volunteer->application->interests))
                                    <span class="text-sm text-ink-500">None listed</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                    @if (filled($volunteer->application->skills))
                        <div class="mt-3">
                            <p class="text-xs text-ink-500">Skills</p>
                            <p class="mt-1 whitespace-pre-line rounded-2xl bg-cream-50 px-4 py-3 text-sm leading-relaxed text-ink-900">{{ $volunteer->application->skills }}</p>
                        </div>
                    @endif
                    <div class="mt-3">
                        <p class="text-xs text-ink-500">Motivation</p>
                        <p class="mt-1 whitespace-pre-line rounded-2xl bg-cream-50 px-4 py-3 text-sm leading-relaxed text-ink-900">{{ $volunteer->application->motivation }}</p>
                    </div>
                </section>
            @endif
        @endif
    </x-admin.modal>
</div>
