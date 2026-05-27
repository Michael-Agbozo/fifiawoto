<?php

use App\Enums\MediaCategory;
use App\Enums\MediaType;
use App\Models\Event;
use App\Models\MediaItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public bool $showForm = false;

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public string $categoryFilter = '';

    public string $typeFilter = '';

    public string $search = '';

    #[Validate('required|string')]
    public string $type = 'image';

    #[Validate('required|string')]
    public string $category = '';

    #[Validate('nullable|integer|exists:events,id')]
    public ?int $event_id = null;

    #[Validate(['required', 'string', 'max:255', 'regex:/^(https?:\/\/|\/|[\w\-]+\/)/i'])]
    public string $path = '';

    #[Validate(['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/|[\w\-]+\/)/i'])]
    public string $poster_path = '';

    #[Validate('nullable|string|max:200')]
    public string $caption = '';

    #[Validate('integer|min:0|max:9999')]
    public int $sort = 0;

    public function startCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->category = MediaCategory::CommunityOutreach->value;
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $m = MediaItem::query()->findOrFail($id);
        $this->editingId = $m->id;
        $this->type = $m->type->value;
        $this->category = $m->category->value;
        $this->event_id = $m->event_id;
        $this->path = $m->path;
        $this->poster_path = (string) $m->poster_path;
        $this->caption = (string) $m->caption;
        $this->sort = $m->sort;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('media', $this->editingId ? 'update' : 'create'), 403);

        $data = $this->validate();

        $payload = [
            'type' => $data['type'],
            'category' => $data['category'],
            'event_id' => $data['event_id'] ?: null,
            'disk' => 'public',
            'path' => $data['path'],
            'poster_path' => $data['poster_path'] ?: null,
            'caption' => $data['caption'] ?: null,
            'sort' => $data['sort'],
        ];

        if ($this->editingId) {
            MediaItem::query()->whereKey($this->editingId)->update($payload);
            $this->flashMessage = 'Media item updated.';
        } else {
            MediaItem::query()->create($payload);
            $this->flashMessage = 'Media item added.';
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
        abort_unless(auth()->user()?->canDo('media', 'delete'), 403);

        MediaItem::query()->whereKey($id)->delete();
        $this->confirmDeleteId = null;
        $this->flashMessage = 'Media item removed.';
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
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
            'editingId', 'showForm', 'event_id', 'path',
            'poster_path', 'caption',
        ]);
        $this->type = 'image';
        $this->category = MediaCategory::CommunityOutreach->value;
        $this->sort = 0;
        $this->resetErrorBag();
    }

    #[Computed]
    public function items()
    {
        $query = MediaItem::query()->with('event');

        if (filled($this->categoryFilter)) {
            $query->where('category', $this->categoryFilter);
        }

        if (filled($this->typeFilter)) {
            $query->where('type', $this->typeFilter);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(fn ($q) => $q->where('caption', 'like', $needle)->orWhere('path', 'like', $needle));
        }

        return $query->orderBy('sort')->orderByDesc('id')->paginate(12);
    }

    #[Computed]
    public function categoryCounts(): array
    {
        return MediaItem::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->all();
    }

    public function with(): array
    {
        return [
            'categoryOptions' => collect(MediaCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
            'typeOptions' => collect(MediaType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all(),
            'eventOptions' => Event::query()->orderBy('starts_at', 'desc')->pluck('title', 'id')->all(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (\App\Enums\MediaCategory::cases() as $c)
            <x-admin.stat-card
                wire:click="$set('categoryFilter', '{{ $c->value }}')"
                icon="image"
                :label="$c->label()"
                :value="(string) ($this->categoryCounts[$c->value] ?? 0)"
                :active="$categoryFilter === $c->value"
                palette="brand"
            />
        @endforeach
    </div>

    <x-admin.section-header
        title="Media gallery"
        :subtitle="$this->items->total().' asset(s) in view'"
    >
        <x-slot:actions>
            <select wire:model.live="typeFilter" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
                <option value="">All types</option>
                @foreach ($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search caption or path" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <button type="button" wire:click="startCreate" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add media
            </button>
            @if (filled($categoryFilter) || filled($typeFilter) || filled($search))
                <x-admin.actions-menu>
                    <button type="button" wire:click="$reset('categoryFilter','typeFilter','search')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
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

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit media item' : 'Add media item'" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Type</label>
                    <select wire:model="type" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Category</label>
                    <select wire:model="category" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @foreach ($categoryOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Event <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                    <select wire:model="event_id" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        <option value="">Unallocated</option>
                        @foreach ($eventOptions as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">File path</label>
                    <input type="text" wire:model="path" required placeholder="media/event-12/photo.jpg" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('path') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Poster path <span class="font-normal normal-case tracking-normal text-ink-500">(for videos)</span></label>
                    <input type="text" wire:model="poster_path" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Caption</label>
                    <input type="text" wire:model="caption" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Sort order</label>
                    <input type="number" min="0" max="9999" wire:model="sort" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
            </div>

            <p class="text-xs text-ink-500">File upload UI ships with the storage layer. For now, paste a path that already lives under <code>public/storage/</code> or an external URL.</p>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">{{ $editingId ? 'Save changes' : 'Add media' }}</button>
                <button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
            </div>
        </form>
    </x-admin.modal>

    @if ($this->items->isEmpty())
        <div class="rounded-3xl border border-dashed border-cream-300 bg-white p-10 text-center text-sm text-ink-500">No media yet — click <strong class="font-semibold text-ink-900">Add media</strong>.</div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($this->items as $m)
                <article class="rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                    <div class="aspect-[4/3] overflow-hidden rounded-t-3xl bg-cream-200">
                        <img src="{{ str_starts_with($m->path, 'http') ? $m->path : asset($m->path) }}" alt="{{ $m->caption ?? $m->category->label() }}" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div class="p-4">
                        <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ $m->category->label() }} · {{ $m->type->label() }}</p>
                        @if ($m->caption)
                            <p class="mt-2 text-sm text-ink-900">{{ $m->caption }}</p>
                        @endif
                        @if ($m->event)
                            <p class="mt-1 text-xs text-ink-500">{{ $m->event->title }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs text-ink-500">Sort #{{ $m->sort }}</p>
                            @if ($confirmDeleteId === $m->id)
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" wire:click="delete({{ $m->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                    <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                </div>
                            @else
                                <x-admin.actions-menu>
                                    <button type="button" wire:click="startEdit({{ $m->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                        Edit
                                    </button>
                                    <button type="button" wire:click="askDelete({{ $m->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                        Delete
                                    </button>
                                </x-admin.actions-menu>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div>{{ $this->items->links() }}</div>
    @endif
</div>
