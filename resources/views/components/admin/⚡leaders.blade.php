<?php

use App\Models\Leader;
use Illuminate\Support\Facades\Storage;
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

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|string|max:120')]
    public string $role = '';

    #[Validate('nullable|string|max:2000')]
    public string $bio = '';

    public string $photo_path = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:5120')]
    public $photo_upload = null;

    public bool $remove_photo = false;

    #[Validate('boolean')]
    public bool $is_published = true;

    #[Validate('integer|min:0|max:9999')]
    public int $sort = 0;

    public bool $showForm = false;

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public string $search = '';

    public string $filter = '';

    public function startCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $leader = Leader::query()->findOrFail($id);
        $this->editingId = $leader->id;
        $this->name = $leader->name;
        $this->role = $leader->role;
        $this->bio = (string) $leader->bio;
        $this->photo_path = (string) $leader->photo_path;
        $this->photo_upload = null;
        $this->remove_photo = false;
        $this->is_published = (bool) $leader->is_published;
        $this->sort = (int) $leader->sort;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('leaders', $this->editingId ? 'update' : 'create'), 403);

        $data = $this->validate();

        $photoPath = $this->photo_path ?: null;

        // Only delete from storage if it's a managed upload (lives under leaders/),
        // not a seeded asset path like 'images/foundation/leadership/...'.
        $isStored = $photoPath && str_starts_with($photoPath, 'leaders/');

        if ($this->remove_photo) {
            if ($isStored && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = null;
        }

        if ($this->photo_upload) {
            if ($isStored && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $this->photo_upload->store('leaders', 'public');
        }

        $payload = [
            'name' => $data['name'],
            'role' => $data['role'],
            'bio' => $data['bio'] ?: null,
            'photo_path' => $photoPath,
            'is_published' => $data['is_published'],
            'sort' => $data['sort'],
        ];

        if ($this->editingId) {
            Leader::query()->whereKey($this->editingId)->update($payload);
            $this->flashMessage = "Updated {$data['name']}.";
        } else {
            Leader::query()->create($payload);
            $this->flashMessage = "Added {$data['name']} to the leadership team.";
        }

        $this->resetForm();
    }

    public function togglePublished(int $id): void
    {
        abort_unless(auth()->user()?->canDo('leaders', 'update'), 403);

        $leader = Leader::query()->findOrFail($id);
        $leader->is_published = ! $leader->is_published;
        $leader->save();

        $this->flashMessage = $leader->is_published
            ? "{$leader->name} is now visible on the about page."
            : "{$leader->name} is hidden from the about page.";
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
        abort_unless(auth()->user()?->canDo('leaders', 'delete'), 403);

        $leader = Leader::query()->findOrFail($id);
        $name = $leader->name;

        if ($leader->photo_path && str_starts_with($leader->photo_path, 'leaders/') && Storage::disk('public')->exists($leader->photo_path)) {
            Storage::disk('public')->delete($leader->photo_path);
        }

        $leader->delete();

        $this->confirmDeleteId = null;
        $this->flashMessage = "Removed {$name} from the leadership team.";
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->reset(['name', 'role', 'bio', 'photo_path', 'photo_upload', 'remove_photo', 'is_published', 'sort', 'showForm']);
        $this->is_published = true;
        $this->resetErrorBag();
    }

    #[Computed]
    public function leaders()
    {
        $query = Leader::query()->ordered();

        if ($this->filter === 'published') {
            $query->where('is_published', true);
        } elseif ($this->filter === 'hidden') {
            $query->where('is_published', false);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhere('role', 'like', $needle);
            });
        }

        return $query->paginate(12);
    }

    #[Computed]
    public function totals(): array
    {
        return [
            'all' => Leader::query()->count(),
            'published' => Leader::query()->where('is_published', true)->count(),
        ];
    }

    public function photoSrc(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Seeded assets live under public/images/...; admin uploads live under storage/app/public.
        return str_starts_with($path, 'leaders/')
            ? asset('storage/'.$path)
            : asset($path);
    }
}; ?>

<div class="space-y-6">
    <x-admin.section-header
        title="Leadership team"
        :subtitle="$this->totals['all'].' total · '.$this->totals['published'].' published on the about page'"
    >
        <x-slot:actions>
            <select wire:model.live="filter" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
                <option value="">All ({{ $this->totals['all'] }})</option>
                <option value="published">Published ({{ $this->totals['published'] }})</option>
                <option value="hidden">Hidden</option>
            </select>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name or role" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            @if (auth()->user()?->canDo('leaders', 'create'))
                <button type="button" wire:click="startCreate" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add leader
                </button>
            @endif
            @if (filled($filter) || filled($search))
                <x-admin.actions-menu>
                    <button type="button" wire:click="$reset('filter','search')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
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
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700 hover:text-green-900" aria-label="Dismiss">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit leader' : 'Add leader'" size="xl" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="leader_name" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Full name</label>
                    <input id="leader_name" type="text" wire:model="name" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="leader_role" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Role / title</label>
                    <input id="leader_role" type="text" wire:model="role" placeholder="e.g. Board of Directors" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="leader_bio" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Short bio <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                <textarea id="leader_bio" wire:model="bio" rows="3" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                @error('bio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Profile photo <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                <div class="mt-2 flex items-center gap-4">
                    @if ($photo_upload)
                        <img src="{{ $photo_upload->temporaryUrl() }}" alt="Pending upload" class="size-20 rounded-full object-cover ring-2 ring-cream-200">
                    @elseif (filled($photo_path) && ! $remove_photo)
                        <img src="{{ $this->photoSrc($photo_path) }}" alt="Current photo" class="size-20 rounded-full object-cover ring-2 ring-cream-200">
                    @else
                        <x-admin.avatar :src="null" :name="$name ?: 'New leader'" size="xl" />
                    @endif
                    <div class="flex flex-col gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0 4 4m-4-4-4 4M4 20h16"/></svg>
                            {{ $photo_upload || filled($photo_path) ? 'Replace photo' : 'Upload photo' }}
                            <input type="file" wire:model="photo_upload" accept="image/png,image/jpeg,image/webp" class="hidden">
                        </label>
                        @if (filled($photo_path) && ! $photo_upload && ! $remove_photo)
                            <button type="button" wire:click="$set('remove_photo', true)" class="text-left text-xs font-semibold text-red-600 hover:text-red-700">Remove current photo</button>
                        @endif
                        @if ($remove_photo)
                            <p class="text-xs text-red-600">Photo will be removed on save. <button type="button" wire:click="$set('remove_photo', false)" class="font-semibold text-brand-700 hover:text-brand-900">Undo</button></p>
                        @endif
                    </div>
                </div>
                <p class="mt-2 text-xs text-ink-500">Square photo recommended. PNG, JPEG, or WebP up to 5 MB.</p>
                <div wire:loading wire:target="photo_upload" class="mt-2 text-xs text-brand-700">Uploading…</div>
                @error('photo_upload') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="leader_sort" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Sort order</label>
                    <input id="leader_sort" type="number" min="0" max="9999" wire:model="sort" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    <p class="mt-1 text-xs text-ink-500">Lower numbers appear first on the public page.</p>
                </div>
                <div class="flex items-center">
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-ink-700">
                        <input type="checkbox" wire:model="is_published" class="size-4 rounded border-cream-300 text-gold-500 focus:ring-gold-500">
                        <span>Publish on the about page</span>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60">
                    <span wire:loading.remove>{{ $editingId ? 'Save changes' : 'Add leader' }}</span>
                    <span wire:loading>Saving…</span>
                </button>
                <button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                    Cancel
                </button>
            </div>
        </form>
    </x-admin.modal>

    <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <table class="w-full text-sm">
            <thead class="bg-cream-100/60 text-xs uppercase tracking-[0.18em] text-ink-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold">Leader</th>
                    <th class="px-5 py-3 text-left font-semibold">Role</th>
                    <th class="px-5 py-3 text-left font-semibold">Sort</th>
                    <th class="px-5 py-3 text-left font-semibold">Status</th>
                    <th class="px-5 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->leaders as $leader)
                    <tr class="border-t border-cream-200 transition hover:bg-cream-100/40">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                @if (filled($leader->photo_path))
                                    <img src="{{ $this->photoSrc($leader->photo_path) }}" alt="{{ $leader->name }}" class="size-10 rounded-full object-cover ring-1 ring-cream-200">
                                @else
                                    <x-admin.avatar :src="null" :name="$leader->name" size="md" />
                                @endif
                                <div>
                                    <p class="font-semibold text-ink-900">{{ $leader->name }}</p>
                                    @if (filled($leader->bio))
                                        <p class="mt-0.5 line-clamp-1 text-xs text-ink-500">{{ $leader->bio }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-ink-700">{{ $leader->role }}</td>
                        <td class="px-5 py-3 text-ink-500">#{{ $leader->sort }}</td>
                        <td class="px-5 py-3">
                            @if ($leader->is_published)
                                <x-admin.status-pill palette="green" label="Published" />
                            @else
                                <x-admin.status-pill palette="gray" label="Hidden" />
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if ($confirmDeleteId === $leader->id)
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" wire:click="delete({{ $leader->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                    <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                </div>
                            @else
                                <x-admin.actions-menu>
                                    @if (auth()->user()?->canDo('leaders', 'update'))
                                        <button type="button" wire:click="togglePublished({{ $leader->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                            <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            {{ $leader->is_published ? 'Hide from site' : 'Publish on site' }}
                                        </button>
                                        <button type="button" wire:click="startEdit({{ $leader->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                            <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                            Edit
                                        </button>
                                    @endif
                                    @if (auth()->user()?->canDo('leaders', 'delete'))
                                        <button type="button" wire:click="askDelete({{ $leader->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                            Delete
                                        </button>
                                    @endif
                                </x-admin.actions-menu>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-ink-500">
                            No leaders yet. Click <strong class="font-semibold text-ink-900">Add leader</strong> to create the first one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->leaders->links() }}</div>
</div>
