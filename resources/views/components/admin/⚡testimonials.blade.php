<?php

use App\Models\Testimonial;
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
    public string $author_name = '';

    #[Validate('required|string|max:120')]
    public string $author_role = '';

    #[Validate('required|string|min:10|max:1000')]
    public string $quote = '';

    public string $photo_path = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:5120')]
    public $photo_upload = null;

    public bool $remove_photo = false;

    #[Validate('nullable|url|max:255')]
    public string $video_url = '';

    #[Validate('boolean')]
    public bool $featured = false;

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
        $t = Testimonial::query()->findOrFail($id);
        $this->editingId = $t->id;
        $this->author_name = $t->author_name;
        $this->author_role = $t->author_role;
        $this->quote = $t->quote;
        $this->photo_path = (string) $t->photo_path;
        $this->photo_upload = null;
        $this->remove_photo = false;
        $this->video_url = (string) $t->video_url;
        $this->featured = (bool) $t->featured;
        $this->sort = (int) $t->sort;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('testimonials', $this->editingId ? 'update' : 'create'), 403);

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
            $photoPath = $this->photo_upload->store('testimonials', 'public');
        }

        $payload = [
            'author_name' => $data['author_name'],
            'author_role' => $data['author_role'],
            'quote' => $data['quote'],
            'photo_path' => $photoPath,
            'video_url' => $data['video_url'] ?: null,
            'featured' => $data['featured'],
            'sort' => $data['sort'],
        ];

        if ($this->editingId) {
            Testimonial::query()->whereKey($this->editingId)->update($payload);
            $this->flashMessage = "Updated testimonial from {$data['author_name']}.";
        } else {
            Testimonial::query()->create($payload);
            $this->flashMessage = "Added testimonial from {$data['author_name']}.";
        }

        $this->resetForm();
    }

    public function toggleFeatured(int $id): void
    {
        abort_unless(auth()->user()?->canDo('testimonials', 'update'), 403);

        $t = Testimonial::query()->findOrFail($id);
        $t->featured = ! $t->featured;
        $t->save();

        $this->flashMessage = $t->featured
            ? "{$t->author_name} is now featured on the home page."
            : "{$t->author_name} is no longer featured.";
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
        abort_unless(auth()->user()?->canDo('testimonials', 'delete'), 403);

        $t = Testimonial::query()->findOrFail($id);
        $name = $t->author_name;

        if ($t->photo_path && Storage::disk('public')->exists($t->photo_path)) {
            Storage::disk('public')->delete($t->photo_path);
        }

        $t->delete();

        $this->confirmDeleteId = null;
        $this->flashMessage = "Removed testimonial from {$name}.";
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
        $this->reset(['author_name', 'author_role', 'quote', 'photo_path', 'photo_upload', 'remove_photo', 'video_url', 'featured', 'sort', 'showForm']);
        $this->resetErrorBag();
    }

    #[Computed]
    public function testimonials()
    {
        $query = Testimonial::query()->ordered();

        if ($this->filter === 'featured') {
            $query->where('featured', true);
        } elseif ($this->filter === 'inactive') {
            $query->where('featured', false);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('author_name', 'like', $needle)
                    ->orWhere('author_role', 'like', $needle)
                    ->orWhere('quote', 'like', $needle);
            });
        }

        return $query->paginate(8);
    }

    #[Computed]
    public function totals(): array
    {
        return [
            'all' => Testimonial::query()->count(),
            'featured' => Testimonial::query()->where('featured', true)->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-admin.section-header
        title="Testimonials library"
        :subtitle="$this->totals['all'].' total · '.$this->totals['featured'].' featured on the home page'"
    >
        <x-slot:actions>
            <select wire:model.live="filter" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
                <option value="">All ({{ $this->totals['all'] }})</option>
                <option value="featured">Featured ({{ $this->totals['featured'] }})</option>
                <option value="inactive">Not featured</option>
            </select>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, role, quote" class="rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <button type="button" wire:click="startCreate" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add testimonial
            </button>
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

    {{-- Flash --}}
    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700 hover:text-green-900" aria-label="Dismiss">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit testimonial' : 'New testimonial'" size="xl" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="author_name" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Author name</label>
                    <input id="author_name" type="text" wire:model="author_name" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('author_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="author_role" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Role / relationship</label>
                    <input id="author_role" type="text" wire:model="author_role" placeholder="e.g. Volunteer, Beneficiary, Partner" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('author_role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="quote" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Quote</label>
                <textarea id="quote" wire:model="quote" rows="4" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                @error('quote') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-[auto,1fr]">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Profile photo <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                    <div class="mt-2 flex items-center gap-4">
                        @if ($photo_upload)
                            <img src="{{ $photo_upload->temporaryUrl() }}" alt="Pending upload" class="size-20 rounded-full object-cover ring-2 ring-cream-200">
                        @else
                            <x-admin.avatar :src="filled($photo_path) && ! $remove_photo ? $photo_path : null" :name="$author_name ?: 'New person'" size="xl" />
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
                <div>
                    <label for="video_url" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Video URL <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                    <input id="video_url" type="url" wire:model="video_url" placeholder="https://youtu.be/…" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('video_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="sort" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Sort order</label>
                    <input id="sort" type="number" min="0" max="9999" wire:model="sort" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    <p class="mt-1 text-xs text-ink-500">Lower numbers appear first on the home carousel.</p>
                </div>
                <div class="flex items-center">
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-ink-700">
                        <input type="checkbox" wire:model="featured" class="size-4 rounded border-cream-300 text-gold-500 focus:ring-gold-500">
                        <span>Feature on the home page</span>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60">
                    <span wire:loading.remove>{{ $editingId ? 'Save changes' : 'Add testimonial' }}</span>
                    <span wire:loading>Saving…</span>
                </button>
                <button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                    Cancel
                </button>
            </div>
        </form>
    </x-admin.modal>

    {{-- List --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($this->testimonials as $t)
            <article class="flex flex-col justify-between rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <x-admin.avatar :src="$t->photo_path" :name="$t->author_name" size="lg" />
                            <div>
                                <p class="font-semibold text-ink-900">{{ $t->author_name }}</p>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ $t->author_role }}</p>
                            </div>
                        </div>
                        @if ($t->featured)
                            <x-admin.status-pill palette="gold" label="Featured" />
                        @endif
                    </div>
                    <blockquote class="mt-4 font-serif text-lg leading-relaxed text-ink-900">
                        &ldquo;{{ \Illuminate\Support\Str::limit($t->quote, 180) }}&rdquo;
                    </blockquote>
                    @if ($t->video_url)
                        <p class="mt-3 text-xs text-ink-500">Video: <a href="{{ $t->video_url }}" class="font-semibold text-gold-500 hover:text-brand-900" target="_blank" rel="noopener">{{ $t->video_url }}</a></p>
                    @endif
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-ink-500">Sort #{{ $t->sort }}</p>
                    @if ($confirmDeleteId === $t->id)
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="delete({{ $t->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                            <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                        </div>
                    @else
                        <x-admin.actions-menu>
                            <button type="button" wire:click="toggleFeatured({{ $t->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                <svg class="size-4 text-gold-500" viewBox="0 0 24 24" fill="{{ $t->featured ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m12 17.27 6.18 3.73-1.64-7.03L22 9.24l-7.19-.62L12 2 9.19 8.62 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                {{ $t->featured ? 'Unfeature' : 'Feature' }}
                            </button>
                            <button type="button" wire:click="startEdit({{ $t->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                Edit
                            </button>
                            <button type="button" wire:click="askDelete({{ $t->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                Delete
                            </button>
                        </x-admin.actions-menu>
                    @endif
                </div>
            </article>
        @empty
            <div class="lg:col-span-2 rounded-3xl border border-dashed border-cream-300 bg-white p-10 text-center text-sm text-ink-500">
                No testimonials yet. Click <strong class="font-semibold text-ink-900">Add testimonial</strong> to create the first one.
            </div>
        @endforelse
    </div>

    <div>{{ $this->testimonials->links() }}</div>
</div>
