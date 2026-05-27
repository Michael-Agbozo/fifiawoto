<?php

use App\Enums\AssistanceType;
use App\Enums\BeneficiaryApplicationStatus;
use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Enums\TimelineEntryType;
use App\Mail\BeneficiaryApplicationConverted;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryFolder;
use App\Models\BeneficiaryTimelineEntry;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $statusFilter = 'new';

    public string $search = '';

    public ?int $confirmDeleteId = null;

    public ?int $viewingId = null;

    public ?string $flashMessage = null;

    public function viewApplication(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(auth()->user()?->canDo('beneficiary_applications', 'update'), 403);

        if (! in_array($status, array_column(BeneficiaryApplicationStatus::cases(), 'value'), true)) {
            return;
        }

        $app = BeneficiaryApplication::query()->findOrFail($id);
        $app->update([
            'status' => $status,
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->flashMessage = "Application from {$app->full_name} set to {$app->status->label()}.";
    }

    public function convertToBeneficiary(int $id): void
    {
        abort_unless(
            auth()->user()?->canDo('beneficiary_applications', 'update')
            && auth()->user()?->canDo('beneficiaries', 'create'),
            403,
        );

        $app = BeneficiaryApplication::query()->findOrFail($id);

        if ($app->converted_beneficiary_id) {
            $this->flashMessage = 'Application already converted.';

            return;
        }

        $category = match ($app->assistance_type) {
            AssistanceType::Widow => SupportCategory::WidowSupport,
            AssistanceType::Education => SupportCategory::ChildEducation,
            AssistanceType::Medical => SupportCategory::Medical,
            AssistanceType::Disability => SupportCategory::Disability,
            AssistanceType::Community => SupportCategory::Community,
            default => SupportCategory::Other,
        };

        $beneficiary = Beneficiary::query()->create([
            'full_name' => $app->full_name,
            'phone' => $app->phone,
            'email' => $app->email,
            'country' => $app->country,
            'region' => $app->region,
            'category' => $category->value,
            'description' => $app->situation,
            'status' => SupportStatus::Approved->value,
            'assigned_to_user_id' => auth()->id(),
            'source_application_id' => $app->id,
        ]);

        // Default folders
        foreach (['Medical Records', 'School Documents', 'Photos', 'Support Reports', 'Identification Documents'] as $name) {
            BeneficiaryFolder::query()->create([
                'beneficiary_id' => $beneficiary->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'created_by' => auth()->id(),
            ]);
        }

        // Timeline entries
        BeneficiaryTimelineEntry::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'type' => TimelineEntryType::ApplicationReceived->value,
            'description' => 'Public application received '.($app->created_at?->format('M j, Y') ?? 'recently'),
            'occurred_at' => $app->created_at ?? now(),
            'recorded_by' => auth()->id(),
        ]);

        BeneficiaryTimelineEntry::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'type' => TimelineEntryType::SupportApproved->value,
            'description' => 'Converted from application by '.auth()->user()?->name,
            'occurred_at' => now(),
            'recorded_by' => auth()->id(),
        ]);

        $app->update([
            'status' => BeneficiaryApplicationStatus::Approved->value,
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
            'converted_beneficiary_id' => $beneficiary->id,
        ]);

        if (filled($app->email)) {
            Mail::send(new BeneficiaryApplicationConverted($app));
        }

        $this->flashMessage = "Created beneficiary record for {$app->full_name}.";

        $this->redirectRoute('admin.beneficiaries.show', $beneficiary, navigate: true);
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
        abort_unless(auth()->user()?->canDo('beneficiary_applications', 'delete'), 403);

        BeneficiaryApplication::query()->whereKey($id)->delete();
        $this->confirmDeleteId = null;
        $this->flashMessage = 'Application removed.';
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function applications()
    {
        $query = BeneficiaryApplication::query()->with('convertedBeneficiary');

        if (filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('full_name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('country', 'like', $needle);
            });
        }

        return $query->latest()->paginate(10);
    }

    #[Computed]
    public function viewingApplication(): ?BeneficiaryApplication
    {
        return $this->viewingId
            ? BeneficiaryApplication::query()->with('convertedBeneficiary', 'reviewer')->find($this->viewingId)
            : null;
    }

    #[Computed]
    public function statusCounts(): array
    {
        return BeneficiaryApplication::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function with(): array
    {
        return [
            'statusOptions' => collect(BeneficiaryApplicationStatus::cases())
                ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                ->all(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (\App\Enums\BeneficiaryApplicationStatus::cases() as $s)
            <x-admin.stat-card
                wire:click="$set('statusFilter', '{{ $s->value }}')"
                :icon="$s->icon()"
                :label="$s->label()"
                :value="(string) ($this->statusCounts[$s->value] ?? 0)"
                :active="$statusFilter === $s->value"
                :palette="$s->palette()"
            />
        @endforeach
    </div>

    <x-admin.section-header
        title="Inbox"
        :subtitle="$this->applications->total().' application(s) in view'"
    >
        <x-slot:actions>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email, phone, country" class="w-72 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <x-admin.actions-menu>
                @if (filled($statusFilter))
                    <button type="button" wire:click="$set('statusFilter', '')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>
                        Show all statuses
                    </button>
                @endif
                @if (filled($search))
                    <button type="button" wire:click="$set('search', '')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Clear search
                    </button>
                @endif
                @if (! filled($statusFilter) && ! filled($search))
                    <p class="px-4 py-2.5 text-xs text-ink-500">No actions available — pick a filter or search term to surface options.</p>
                @endif
            </x-admin.actions-menu>
        </x-slot:actions>
    </x-admin.section-header>

    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700">×</button>
        </div>
    @endif

    <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->applications->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">No beneficiary applications match these filters.</div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr class="transition hover:bg-cream-100/40">
                        <th class="px-6 py-3">Applicant</th>
                        <th class="px-6 py-3">Country</th>
                        <th class="px-6 py-3">Assistance</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200 text-sm">
                    @foreach ($this->applications as $app)
                        <tr class="align-top transition hover:bg-cream-100/40">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-ink-900">{{ $app->full_name }}</p>
                                <p class="text-xs text-ink-500">{{ $app->phone }}@if ($app->email) · {{ $app->email }}@endif</p>
                                <p class="mt-2 max-w-md text-xs text-ink-500">{{ \Illuminate\Support\Str::limit($app->situation, 200) }}</p>
                                <p class="mt-2 text-[10px] uppercase tracking-[0.18em] text-ink-500">Submitted {{ $app->created_at?->diffForHumans() }}</p>
                            </td>
                            <td class="px-6 py-4 text-ink-700">{{ $app->country }}@if ($app->region), {{ $app->region }}@endif</td>
                            <td class="px-6 py-4 text-ink-700">{{ $app->assistance_type->label() }}</td>
                            <td class="px-6 py-4">
                                <x-admin.status-pill :palette="$app->status->palette()" :label="$app->status->label()" />
                                @if ($app->convertedBeneficiary)
                                    <a href="{{ route('admin.beneficiaries.show', $app->convertedBeneficiary) }}" class="mt-2 inline-flex text-[10px] font-semibold uppercase tracking-[0.18em] text-gold-500 hover:text-brand-900" wire:navigate>Open beneficiary →</a>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end">
                                    @if ($confirmDeleteId === $app->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="delete({{ $app->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                            <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                        </div>
                                    @else
                                        <x-admin.actions-menu>
                                            <button type="button" wire:click="viewApplication({{ $app->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                View details
                                            </button>
                                            @if (! $app->converted_beneficiary_id)
                                                <button type="button" wire:click="convertToBeneficiary({{ $app->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                    <svg class="size-4 text-gold-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                                    Convert to beneficiary
                                                </button>
                                            @endif
                                            <button type="button" wire:click="setStatus({{ $app->id }}, 'under_review')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                                                Mark in review
                                            </button>
                                            <button type="button" wire:click="setStatus({{ $app->id }}, 'rejected')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                                Reject
                                            </button>
                                            <button type="button" wire:click="askDelete({{ $app->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
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

    <div>{{ $this->applications->links() }}</div>

    <x-admin.modal :show="(bool) $this->viewingApplication" :title="$this->viewingApplication?->full_name ?? 'Beneficiary application'" :subtitle="'Beneficiary application · '.($this->viewingApplication?->status->label() ?? '')" size="xl" onClose="closeView">
        @if ($app = $this->viewingApplication)
            <div class="grid gap-6 lg:grid-cols-2">
                <section>
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Contact</h3>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-ink-500">Phone</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->phone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Email</dt>
                            <dd class="mt-0.5 break-all text-sm text-ink-900">{{ $app->email ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Country</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->country }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Region</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->region ?: '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section>
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Workflow</h3>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-ink-500">Type of assistance</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->assistance_type->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Submitted</dt>
                            <dd class="mt-0.5 text-sm text-ink-900">{{ $app->created_at?->format('M j, Y') }}</dd>
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
                <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Situation described</h3>
                <p class="mt-2 whitespace-pre-line rounded-2xl bg-cream-50 px-4 py-3 text-sm leading-relaxed text-ink-900">{{ $app->situation }}</p>
            </section>

            <section class="mt-5">
                <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Converted record</h3>
                <p class="mt-2 text-sm text-ink-900">
                    @if ($app->convertedBeneficiary)
                        <a href="{{ route('admin.beneficiaries.show', $app->convertedBeneficiary) }}" wire:navigate class="font-semibold text-gold-500 hover:text-brand-900">Open beneficiary profile →</a>
                    @else
                        Not converted yet
                    @endif
                </p>
            </section>

            @if (! $app->converted_beneficiary_id)
                <section class="mt-6 rounded-2xl border border-cream-300 bg-cream-50 p-4">
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Quick actions</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" wire:click="convertToBeneficiary({{ $app->id }})" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-bold text-white transition hover:bg-brand-900">Convert to beneficiary</button>
                        <button type="button" wire:click="setStatus({{ $app->id }}, 'under_review')" class="inline-flex items-center gap-2 rounded-xl border border-cream-300 px-4 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Mark in review</button>
                        <button type="button" wire:click="setStatus({{ $app->id }}, 'rejected')" class="inline-flex items-center gap-2 rounded-xl border border-cream-300 px-4 py-2 text-xs font-semibold text-ink-700 transition hover:border-red-500 hover:text-red-600">Reject</button>
                    </div>
                </section>
            @endif
        @endif
    </x-admin.modal>
</div>
