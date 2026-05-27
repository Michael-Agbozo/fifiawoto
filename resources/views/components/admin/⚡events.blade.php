<?php

use App\Enums\EventStatus;
use App\Mail\EventVolunteerInvitation;
use App\Models\Event;
use App\Models\Volunteer;
use Illuminate\Support\Facades\Mail;
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

    public string $search = '';

    #[Validate('required|string|max:200')]
    public string $title = '';

    #[Validate('required|date')]
    public string $starts_at = '';

    #[Validate('nullable|date|after_or_equal:starts_at')]
    public string $ends_at = '';

    #[Validate('required|string|max:120')]
    public string $location = '';

    #[Validate('required|string|max:80')]
    public string $country = '';

    #[Validate('required|string|min:20')]
    public string $description = '';

    #[Validate('nullable|string|max:4000')]
    public string $activities = '';

    #[Validate('nullable|string|max:4000')]
    public string $expected_impact = '';

    #[Validate('nullable|string|max:4000')]
    public string $volunteer_opportunities = '';

    #[Validate('nullable|numeric|min:0|max:10000000')]
    public string $goal_amount = '';

    #[Validate('required|string')]
    public string $status = 'draft';

    public string $hero_image_path = '';

    public $hero_image_upload = null;

    public bool $remove_hero_image = false;

    public function startCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->starts_at = now()->addDays(14)->toDateString();
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $e = Event::query()->findOrFail($id);
        $this->editingId = $e->id;
        $this->title = $e->title;
        $this->starts_at = $e->starts_at?->toDateString() ?? '';
        $this->ends_at = $e->ends_at?->toDateString() ?? '';
        $this->location = $e->location;
        $this->country = $e->country;
        $this->description = $e->description;
        $this->activities = (string) $e->activities;
        $this->expected_impact = (string) $e->expected_impact;
        $this->volunteer_opportunities = (string) $e->volunteer_opportunities;
        $this->goal_amount = $e->goal_cents ? number_format($e->goal_cents / 100, 2, '.', '') : '';
        $this->status = $e->status->value;
        $this->hero_image_path = (string) $e->hero_image_path;
        $this->hero_image_upload = null;
        $this->remove_hero_image = false;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('events', $this->editingId ? 'update' : 'create'), 403);

        $data = $this->validate();

        $heroRequired = ! $this->editingId && ! filled($this->hero_image_path);

        $this->validate([
            'hero_image_upload' => [
                $heroRequired ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'dimensions:width=1080,height=1080',
                'max:5120',
            ],
        ], [
            'hero_image_upload.required' => 'A program image is required.',
            'hero_image_upload.dimensions' => 'The program image must be exactly 1080×1080 pixels.',
        ]);

        $heroPath = $this->hero_image_path ?: null;

        if ($this->remove_hero_image) {
            if ($heroPath && Storage::disk('public')->exists($heroPath)) {
                Storage::disk('public')->delete($heroPath);
            }
            $heroPath = null;
        }

        if ($this->hero_image_upload) {
            if ($heroPath && Storage::disk('public')->exists($heroPath)) {
                Storage::disk('public')->delete($heroPath);
            }
            $heroPath = $this->hero_image_upload->store('events', 'public');
        }

        $payload = [
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?: null,
            'location' => $data['location'],
            'country' => $data['country'],
            'description' => $data['description'],
            'activities' => $data['activities'] ?: null,
            'expected_impact' => $data['expected_impact'] ?: null,
            'volunteer_opportunities' => $data['volunteer_opportunities'] ?: null,
            'goal_cents' => $data['goal_amount'] !== '' ? (int) round(((float) $data['goal_amount']) * 100) : null,
            'status' => $data['status'],
            'hero_image_path' => $heroPath,
            'published_at' => $data['status'] === EventStatus::Published->value ? now() : null,
        ];

        if ($this->editingId) {
            $event = Event::query()->findOrFail($this->editingId);
            // Keep existing slug on edit so public URLs don't break
            $payload['slug'] = $event->slug;
            $event->update($payload);
            $this->flashMessage = 'Event updated.';
        } else {
            $event = Event::query()->create($payload);
            $this->flashMessage = 'Event created.';

            $queued = 0;
            Volunteer::query()->whereNotNull('email')->chunkById(200, function ($volunteers) use ($event, &$queued) {
                foreach ($volunteers as $volunteer) {
                    Mail::queue(new EventVolunteerInvitation($event, $volunteer));
                    $queued++;
                }
            });

            if ($queued > 0) {
                $this->flashMessage .= " Invitation queued for {$queued} volunteer(s).";
            }
        }

        $this->resetForm();
    }

    public function publish(int $id): void
    {
        abort_unless(auth()->user()?->canDo('events', 'update'), 403);

        $e = Event::query()->findOrFail($id);
        $e->update([
            'status' => EventStatus::Published->value,
            'published_at' => now(),
        ]);
        $this->flashMessage = "{$e->title} is now published.";
    }

    public function unpublish(int $id): void
    {
        abort_unless(auth()->user()?->canDo('events', 'update'), 403);

        $e = Event::query()->findOrFail($id);
        $e->update(['status' => EventStatus::Draft->value]);
        $this->flashMessage = "{$e->title} moved back to draft.";
    }

    public function archive(int $id): void
    {
        abort_unless(auth()->user()?->canDo('events', 'update'), 403);

        $e = Event::query()->findOrFail($id);
        $e->update(['status' => EventStatus::Archived->value]);
        $this->flashMessage = "{$e->title} archived.";
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
        abort_unless(auth()->user()?->canDo('events', 'delete'), 403);

        $e = Event::query()->findOrFail($id);
        $title = $e->title;
        $e->delete();

        $this->confirmDeleteId = null;
        $this->flashMessage = "Event '{$title}' deleted.";
    }

    public function updatedStatusFilter(): void
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
            'editingId', 'showForm', 'title', 'starts_at', 'ends_at', 'location',
            'country', 'description', 'activities', 'expected_impact',
            'volunteer_opportunities', 'goal_amount',
            'hero_image_path', 'hero_image_upload', 'remove_hero_image',
        ]);
        $this->status = 'draft';
        $this->resetErrorBag();
    }

    #[Computed]
    public function events()
    {
        $query = Event::query()->withSum('donations as raised_cents', 'amount_cents');

        if (filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('title', 'like', $needle)
                    ->orWhere('location', 'like', $needle)
                    ->orWhere('country', 'like', $needle);
            });
        }

        return $query->orderBy('starts_at', 'desc')->paginate(10);
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'all' => Event::query()->count(),
            'published' => Event::query()->where('status', EventStatus::Published->value)->count(),
            'draft' => Event::query()->where('status', EventStatus::Draft->value)->count(),
            'archived' => Event::query()->where('status', EventStatus::Archived->value)->count(),
        ];
    }

    public function with(): array
    {
        return [
            'statusOptions' => [
                EventStatus::Draft->value => EventStatus::Draft->label(),
                EventStatus::Published->value => EventStatus::Published->label(),
                EventStatus::Archived->value => EventStatus::Archived->label(),
            ],
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['All events',  $this->counts['all'],       '',                                'calendar', 'brand'],
            ['Published',   $this->counts['published'], EventStatus::Published->value,    'calendar', 'green'],
            ['Drafts',      $this->counts['draft'],     EventStatus::Draft->value,        'inbox',    'amber'],
            ['Archived',    $this->counts['archived'],  EventStatus::Archived->value,     'shield',   'gray'],
        ] as [$label, $count, $value, $icon, $palette])
            <x-admin.stat-card
                wire:click="$set('statusFilter', '{{ $value }}')"
                :icon="$icon"
                :label="$label"
                :value="(string) $count"
                :active="$statusFilter === $value"
                :palette="$palette"
            />
        @endforeach
    </div>

    <x-admin.section-header
        title="All events"
        :subtitle="$this->events->total().' programme(s) in view'"
    >
        <x-slot:actions>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search title, location, country" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <button type="button" wire:click="startCreate" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New event
            </button>
            @if (filled($statusFilter) || filled($search))
                <x-admin.actions-menu>
                    <button type="button" wire:click="$reset('statusFilter','search')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
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

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit event' : 'New event'" size="xl" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Title</label>
                <input type="text" wire:model="title" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Start date</label>
                    <input type="date" wire:model="starts_at" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('starts_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">End date <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                    <input type="date" wire:model="ends_at" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('ends_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Location</label>
                    <input type="text" wire:model="location" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Country</label>
                    <input type="text" wire:model="country" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Description</label>
                <textarea wire:model="description" rows="4" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Program image <span class="font-normal normal-case tracking-normal text-red-600">required · 1080×1080</span></label>
                <div class="mt-2 flex flex-wrap items-center gap-4">
                    @php
                        $previewUrl = null;
                        if ($hero_image_upload) {
                            $previewUrl = $hero_image_upload->temporaryUrl();
                        } elseif (filled($hero_image_path) && ! $remove_hero_image) {
                            $previewUrl = str_starts_with($hero_image_path, 'http')
                                ? $hero_image_path
                                : asset('storage/'.ltrim($hero_image_path, '/'));
                        }
                    @endphp
                    <div class="grid size-32 place-items-center overflow-hidden rounded-2xl border border-dashed border-cream-300 bg-cream-100 text-ink-500">
                        @if ($previewUrl)
                            <img src="{{ $previewUrl }}" alt="Program image preview" class="size-full object-cover">
                        @else
                            <div class="flex flex-col items-center gap-1 px-2 text-center text-[10px] font-semibold uppercase tracking-[0.18em]">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4 17 5-5 4 4 3-3 4 4M4 5h16v14H4z"/></svg>
                                1080×1080
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 self-start rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0 4 4m-4-4-4 4M4 20h16"/></svg>
                            {{ $hero_image_upload || filled($hero_image_path) ? 'Replace image' : 'Upload image' }}
                            <input type="file" wire:model="hero_image_upload" accept="image/png,image/jpeg,image/webp" class="hidden">
                        </label>
                        @if (filled($hero_image_path) && ! $hero_image_upload && ! $remove_hero_image)
                            <button type="button" wire:click="$set('remove_hero_image', true)" class="text-left text-xs font-semibold text-red-600 hover:text-red-700">Remove current image</button>
                        @endif
                        @if ($remove_hero_image)
                            <p class="text-xs text-red-600">Image will be removed on save. <button type="button" wire:click="$set('remove_hero_image', false)" class="font-semibold text-brand-700 hover:text-brand-900">Undo</button></p>
                        @endif
                        <p class="text-xs text-ink-500">PNG, JPEG, or WebP. Must be exactly 1080×1080 pixels.</p>
                        <div wire:loading wire:target="hero_image_upload" class="text-xs text-brand-700">Uploading…</div>
                    </div>
                </div>
                @error('hero_image_upload') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Activities <span class="font-normal normal-case tracking-normal text-ink-500">(one per line)</span></label>
                    <textarea wire:model="activities" rows="4" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Volunteer opportunities <span class="font-normal normal-case tracking-normal text-ink-500">(one per line)</span></label>
                    <textarea wire:model="volunteer_opportunities" rows="4" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Expected impact</label>
                <textarea wire:model="expected_impact" rows="3" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Fundraising goal (USD)</label>
                    <input type="number" step="0.01" min="0" wire:model="goal_amount" placeholder="10000" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Status</label>
                    <select wire:model="status" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">
                    {{ $editingId ? 'Save changes' : 'Create event' }}
                </button>
                <button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                    Cancel
                </button>
            </div>
        </form>
    </x-admin.modal>

    <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->events->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">No events match these filters yet.</div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr class="transition hover:bg-cream-100/40">
                        <th class="px-6 py-3">Event</th>
                        <th class="px-6 py-3">Starts</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Raised</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200 text-sm">
                    @foreach ($this->events as $event)
                        @php
                            $raised = (int) ($event->raised_cents ?? 0);
                            $goal = (int) ($event->goal_cents ?? 0);
                            $percent = $goal > 0 ? min(100, (int) round($raised / $goal * 100)) : 0;
                        @endphp
                        <tr class="transition hover:bg-cream-100/40">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if (filled($event->hero_image_path))
                                        <img src="{{ asset('storage/'.ltrim($event->hero_image_path, '/')) }}" alt="" class="size-12 rounded-xl object-cover ring-1 ring-cream-200" loading="lazy">
                                    @else
                                        <div class="grid size-12 place-items-center rounded-xl bg-cream-100 text-[9px] font-semibold uppercase tracking-[0.18em] text-ink-500">No image</div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-ink-900">{{ $event->title }}</p>
                                        <p class="text-xs text-ink-500">{{ $event->location }}, {{ $event->country }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-ink-700">{{ $event->starts_at?->format('M j, Y') }}</td>
                            <td class="px-6 py-4">
                                <x-admin.status-pill :palette="$event->status->palette()" :label="$event->status->label()" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <p class="font-semibold text-ink-900">${{ number_format($raised / 100) }}</p>
                                @if ($goal > 0)
                                    <p class="text-xs text-ink-500">{{ $percent }}% of ${{ number_format($goal / 100) }}</p>
                                    <div class="mt-1 h-1 w-24 rounded-full bg-cream-200">
                                        <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end">
                                    @if ($confirmDeleteId === $event->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="delete({{ $event->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                            <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                        </div>
                                    @else
                                        <x-admin.actions-menu>
                                            @if ($event->status !== EventStatus::Published)
                                                <button type="button" wire:click="publish({{ $event->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                    <svg class="size-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                                                    Publish
                                                </button>
                                            @else
                                                <button type="button" wire:click="unpublish({{ $event->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                    <svg class="size-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9m0 0 4 4m-4-4-4 4M5 3h14"/></svg>
                                                    Unpublish
                                                </button>
                                            @endif
                                            <button type="button" wire:click="startEdit({{ $event->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                                Edit
                                            </button>
                                            <button type="button" wire:click="askDelete({{ $event->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
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

    <div>{{ $this->events->links() }}</div>
</div>
