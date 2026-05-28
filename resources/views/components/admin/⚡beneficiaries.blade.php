<?php

use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Enums\TimelineEntryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFolder;
use App\Models\BeneficiaryTimelineEntry;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;

    public bool $showForm = false;

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public string $statusFilter = '';

    public string $categoryFilter = '';

    public string $countryFilter = '';

    public string $search = '';

    #[Validate('required|string|max:200')]
    public string $full_name = '';

    #[Validate('nullable|date')]
    public string $date_of_birth = '';

    #[Validate('nullable|in:female,male,other')]
    public string $gender = '';

    #[Validate('nullable|string|max:40')]
    public string $phone = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:80')]
    public string $country = '';

    #[Validate('nullable|string|max:120')]
    public string $region = '';

    #[Validate('required|string')]
    public string $category = '';

    #[Validate('required|string|min:20|max:4000')]
    public string $description = '';

    #[Validate('required|string')]
    public string $status = 'pending_review';

    #[Validate('nullable|exists:users,id')]
    public ?int $assigned_to_user_id = null;

    #[Validate('nullable|string|max:4000')]
    public string $notes = '';

    public string $photo_path = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:5120')]
    public $photo_upload = null;

    public bool $remove_photo = false;

    public function startCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->category = SupportCategory::Community->value;
        $this->status = SupportStatus::PendingReview->value;
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $b = Beneficiary::query()->findOrFail($id);
        $this->editingId = $b->id;
        $this->full_name = $b->full_name;
        $this->date_of_birth = $b->date_of_birth?->toDateString() ?? '';
        $this->gender = (string) $b->gender;
        $this->phone = (string) $b->phone;
        $this->email = (string) $b->email;
        $this->country = $b->country;
        $this->region = (string) $b->region;
        $this->category = $b->category->value;
        $this->description = $b->description;
        $this->status = $b->status->value;
        $this->assigned_to_user_id = $b->assigned_to_user_id;
        $this->notes = (string) $b->notes;
        $this->photo_path = (string) $b->photo_path;
        $this->photo_upload = null;
        $this->remove_photo = false;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('beneficiaries', $this->editingId ? 'update' : 'create'), 403);

        $data = $this->validate();

        $photoPath = $this->photo_path ?: null;

        if ($this->remove_photo) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = null;
        }

        if ($this->photo_upload) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $this->photo_upload->store('beneficiaries', 'public');
        }

        $payload = [
            'full_name' => $data['full_name'],
            'date_of_birth' => $data['date_of_birth'] ?: null,
            'gender' => $data['gender'] ?: null,
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'country' => $data['country'],
            'region' => $data['region'] ?: null,
            'category' => $data['category'],
            'description' => $data['description'],
            'status' => $data['status'],
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?: null,
            'notes' => $data['notes'] ?: null,
            'photo_path' => $photoPath,
        ];

        if ($this->editingId) {
            Beneficiary::query()->whereKey($this->editingId)->update($payload);
            $this->flashMessage = 'Beneficiary record updated.';
        } else {
            $b = Beneficiary::query()->create($payload);

            // Seed default folders
            foreach ([
                'Medical Records', 'School Documents', 'Photos',
                'Support Reports', 'Identification Documents',
            ] as $name) {
                BeneficiaryFolder::query()->create([
                    'beneficiary_id' => $b->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'created_by' => auth()->id(),
                ]);
            }

            // Timeline: record creation
            BeneficiaryTimelineEntry::query()->create([
                'beneficiary_id' => $b->id,
                'type' => TimelineEntryType::ApplicationReceived->value,
                'description' => 'Record created by '.auth()->user()?->name,
                'occurred_at' => now(),
                'recorded_by' => auth()->id(),
            ]);

            $this->flashMessage = 'Beneficiary record created with default folders.';
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
        abort_unless(auth()->user()?->canDo('beneficiaries', 'delete'), 403);

        $b = Beneficiary::query()->findOrFail($id);
        $name = $b->full_name;
        $b->delete();

        $this->confirmDeleteId = null;
        $this->flashMessage = "Archived beneficiary record for {$name}.";
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCountryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'showForm', 'full_name', 'date_of_birth', 'gender', 'phone',
            'email', 'country', 'region', 'description', 'assigned_to_user_id', 'notes',
            'photo_path', 'photo_upload', 'remove_photo',
        ]);
        $this->category = '';
        $this->status = 'pending_review';
        $this->resetErrorBag();
    }

    #[Computed]
    public function beneficiaries()
    {
        $query = Beneficiary::query()->with('assignedTo');

        if (filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (filled($this->categoryFilter)) {
            $query->where('category', $this->categoryFilter);
        }

        if (filled($this->countryFilter)) {
            $query->where('country', $this->countryFilter);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('full_name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('region', 'like', $needle);
            });
        }

        return $query->orderByDesc('updated_at')->paginate(15);
    }

    #[Computed]
    public function statusCounts(): array
    {
        return Beneficiary::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function with(): array
    {
        return [
            'categoryOptions' => SupportCategory::options(),
            'statusOptions' => SupportStatus::options(),
            'countryOptions' => Beneficiary::query()->distinct()->pluck('country')->filter()->values()->all(),
            'staffOptions' => User::query()
                ->whereIn('role', ['owner', 'super_admin', 'foundation_staff'])
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (\App\Enums\SupportStatus::cases() as $s)
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
        title="All beneficiaries"
        :subtitle="$this->beneficiaries->total().' record(s) in view'"
    >
        <x-slot:actions>
            <select wire:model.live="categoryFilter" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
                <option value="">All categories</option>
                @foreach ($categoryOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @if (count($countryOptions) > 0)
                <select wire:model.live="countryFilter" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
                    <option value="">All countries</option>
                    @foreach ($countryOptions as $country)
                        <option value="{{ $country }}">{{ $country }}</option>
                    @endforeach
                </select>
            @endif
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name, phone, email, region" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <button type="button" wire:click="startCreate" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add beneficiary
            </button>
            @if (filled($statusFilter) || filled($categoryFilter) || filled($countryFilter) || filled($search))
                <x-admin.actions-menu>
                    <button type="button" wire:click="$reset('statusFilter','categoryFilter','countryFilter','search')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Clear all filters
                    </button>
                </x-admin.actions-menu>
            @endif
        </x-slot:actions>
    </x-admin.section-header>

    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700">×</button>
        </div>
    @endif

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit beneficiary' : 'New beneficiary record'" size="xl" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Profile picture <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                <div class="mt-2 flex items-center gap-4">
                    @if ($photo_upload)
                        <img src="{{ $photo_upload->temporaryUrl() }}" alt="Pending upload" class="size-20 rounded-full object-cover ring-2 ring-cream-200">
                    @else
                        <x-admin.avatar :src="filled($photo_path) && ! $remove_photo ? $photo_path : null" :name="$full_name ?: 'New beneficiary'" size="xl" />
                    @endif
                    <div class="flex flex-col gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 self-start rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0 4 4m-4-4-4 4M4 20h16"/></svg>
                            {{ $photo_upload || filled($photo_path) ? 'Replace picture' : 'Upload picture' }}
                            <input type="file" wire:model="photo_upload" accept="image/png,image/jpeg,image/webp" class="hidden">
                        </label>
                        @if (filled($photo_path) && ! $photo_upload && ! $remove_photo)
                            <button type="button" wire:click="$set('remove_photo', true)" class="text-left text-xs font-semibold text-red-600 hover:text-red-700">Remove current picture</button>
                        @endif
                        @if ($remove_photo)
                            <p class="text-xs text-red-600">Picture will be removed on save. <button type="button" wire:click="$set('remove_photo', false)" class="font-semibold text-brand-700 hover:text-brand-900">Undo</button></p>
                        @endif
                        <p class="text-xs text-ink-500">Square photo recommended. PNG, JPEG, or WebP up to 5 MB.</p>
                        <div wire:loading wire:target="photo_upload" class="text-xs text-brand-700">Uploading…</div>
                    </div>
                </div>
                @error('photo_upload') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Full name</label>
                    <input type="text" wire:model="full_name" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Date of birth</label>
                    <input type="date" wire:model="date_of_birth" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Gender</label>
                    <select wire:model="gender" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        <option value="">Prefer not to say</option>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Phone</label>
                    <input type="tel" wire:model="phone" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Email</label>
                    <input type="email" wire:model="email" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Country</label>
                    <input type="text" wire:model="country" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Region / city</label>
                    <input type="text" wire:model="region" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Category of support</label>
                    <select wire:model="category" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        <option value="">Pick one…</option>
                        @foreach ($categoryOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Status</label>
                    <select wire:model="status" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Description of situation</label>
                <textarea wire:model="description" rows="5" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Assigned staff <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                <select wire:model="assigned_to_user_id" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    <option value="">Unassigned</option>
                    @foreach ($staffOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Internal notes</label>
                <textarea wire:model="notes" rows="3" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">{{ $editingId ? 'Save changes' : 'Create record' }}</button>
                <button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
            </div>
        </form>
    </x-admin.modal>

    <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->beneficiaries->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">No beneficiary records yet — click <strong class="font-semibold text-ink-900">Add beneficiary</strong>.</div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr class="transition hover:bg-cream-100/40">
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3">Country</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Assigned</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200 text-sm">
                    @foreach ($this->beneficiaries as $b)
                        <tr class="transition hover:bg-cream-100/40">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :src="$b->photo_path" :name="$b->full_name" size="sm" />
                                    <div>
                                        <a href="{{ route('admin.beneficiaries.show', $b) }}" class="font-semibold text-ink-900 hover:text-gold-500" wire:navigate>{{ $b->full_name }}</a>
                                        @if ($b->region)
                                            <p class="text-xs text-ink-500">{{ $b->region }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-ink-700">{{ $b->category->label() }}</td>
                            <td class="px-6 py-4 text-ink-700">{{ $b->country }}</td>
                            <td class="px-6 py-4">
                                <x-admin.status-pill :palette="$b->status->palette()" :label="$b->status->label()" />
                            </td>
                            <td class="px-6 py-4 text-xs text-ink-500">{{ $b->assignedTo?->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end">
                                    @if ($confirmDeleteId === $b->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="delete({{ $b->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm archive</button>
                                            <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                        </div>
                                    @else
                                        <x-admin.actions-menu>
                                            <a href="{{ route('admin.beneficiaries.show', $b) }}" @click="open = false" wire:navigate class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                Open profile
                                            </a>
                                            <button type="button" wire:click="startEdit({{ $b->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button" wire:click="askDelete({{ $b->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                                Archive
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

    <div>{{ $this->beneficiaries->links() }}</div>
</div>
