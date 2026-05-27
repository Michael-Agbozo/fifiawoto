<?php

use App\Enums\InstagramSource;
use App\Models\InstagramPost;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public string $filter = '';

    #[Validate(['required', 'url', 'max:255', 'regex:/^https:\/\/(www\.)?instagram\.com\//i'])]
    public string $permalink = '';

    #[Validate('nullable|string|max:500')]
    public string $caption = '';

    #[Validate('nullable|url|max:500')]
    public string $thumbnail_url = '';

    #[Validate('in:IMAGE,VIDEO')]
    public string $media_type = 'IMAGE';

    public function startCreate(): void
    {
        $this->reset(['permalink', 'caption', 'thumbnail_url', 'media_type']);
        $this->media_type = 'IMAGE';
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->reset(['permalink', 'caption', 'thumbnail_url', 'media_type', 'showForm']);
        $this->media_type = 'IMAGE';
        $this->resetErrorBag();
    }

    public function add(): void
    {
        abort_unless(auth()->user()?->canDo('instagram', 'create'), 403);

        $data = $this->validate();

        InstagramPost::query()->updateOrCreate(
            ['external_id' => sha1($data['permalink'])],
            [
                'permalink' => $data['permalink'],
                'caption' => $data['caption'] ?: null,
                'thumbnail_url' => $data['thumbnail_url'] ?: null,
                'media_type' => $data['media_type'],
                'posted_at' => now(),
                'is_approved' => true,
                'is_hidden' => false,
                'source' => InstagramSource::Manual->value,
            ],
        );

        $this->flashMessage = 'Instagram post added.';
        $this->cancel();
    }

    public function toggleApprove(int $id): void
    {
        abort_unless(auth()->user()?->canDo('instagram', 'update'), 403);

        $post = InstagramPost::query()->findOrFail($id);
        $post->is_approved = ! $post->is_approved;
        $post->save();
        $this->flashMessage = $post->is_approved ? 'Post approved.' : 'Post unapproved.';
    }

    public function toggleHide(int $id): void
    {
        abort_unless(auth()->user()?->canDo('instagram', 'update'), 403);

        $post = InstagramPost::query()->findOrFail($id);
        $post->is_hidden = ! $post->is_hidden;
        $post->save();
        $this->flashMessage = $post->is_hidden ? 'Post hidden from the public site.' : 'Post visible again.';
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
        abort_unless(auth()->user()?->canDo('instagram', 'delete'), 403);

        InstagramPost::query()->whereKey($id)->delete();
        $this->confirmDeleteId = null;
        $this->flashMessage = 'Post removed.';
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function posts()
    {
        $query = InstagramPost::query();

        if ($this->filter === 'visible') {
            $query->where('is_approved', true)->where('is_hidden', false);
        } elseif ($this->filter === 'hidden') {
            $query->where('is_hidden', true);
        } elseif ($this->filter === 'pending') {
            $query->where('is_approved', false);
        }

        return $query->orderByDesc('posted_at')->paginate(12);
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'all' => InstagramPost::query()->count(),
            'visible' => InstagramPost::query()->where('is_approved', true)->where('is_hidden', false)->count(),
            'hidden' => InstagramPost::query()->where('is_hidden', true)->count(),
            'pending' => InstagramPost::query()->where('is_approved', false)->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['All',       '',         $this->counts['all'],     'instagram', 'brand'],
            ['Visible',   'visible',  $this->counts['visible'], 'instagram', 'green'],
            ['Hidden',    'hidden',   $this->counts['hidden'],  'shield',    'red'],
            ['Pending',   'pending',  $this->counts['pending'], 'inbox',     'amber'],
        ] as [$label, $value, $count, $icon, $palette])
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
        title="Instagram feed"
        :subtitle="$this->posts->total().' post(s) in view · automatic API pulls land in a later phase'"
    >
        <x-slot:actions>
            <button type="button" wire:click="startCreate" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Paste a link
            </button>
            @if (filled($filter))
                <x-admin.actions-menu>
                    <button type="button" wire:click="$set('filter', '')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Clear filter
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

    <x-admin.modal :show="$showForm" title="Add an Instagram post" onClose="cancel">
        <form wire:submit="add" class="space-y-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Post URL</label>
                <input type="url" wire:model="permalink" required placeholder="https://www.instagram.com/p/…" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                <p class="mt-1 text-xs text-ink-500">Paste the full URL of the Instagram post (e.g. <code class="rounded bg-cream-100 px-1">https://www.instagram.com/p/ABC123/</code>) so clicking the thumbnail opens the real post.</p>
                @error('permalink') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Media type</label>
                <div class="mt-2 flex gap-2">
                    <label class="inline-flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-cream-300 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 transition has-checked:border-gold-500 has-checked:bg-gold-500/5 has-checked:text-brand-900">
                        <input type="radio" name="media_type" value="IMAGE" wire:model="media_type" class="sr-only">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159M3 19.5h18a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3A1.5 1.5 0 0 0 1.5 6v12A1.5 1.5 0 0 0 3 19.5Z"/></svg>
                        Image
                    </label>
                    <label class="inline-flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-cream-300 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 transition has-checked:border-gold-500 has-checked:bg-gold-500/5 has-checked:text-brand-900">
                        <input type="radio" name="media_type" value="VIDEO" wire:model="media_type" class="sr-only">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        Reel / Video
                    </label>
                </div>
                @error('media_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Thumbnail URL <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                <input type="url" wire:model="thumbnail_url" placeholder="https://…/image.jpg" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                <p class="mt-1 text-xs text-ink-500">Leave blank to use a default placeholder while the Instagram Graph API isn't connected.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Caption <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                <textarea wire:model="caption" rows="3" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">Add post</button>
                <button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
            </div>
        </form>
    </x-admin.modal>

    @if ($this->posts->isEmpty())
        <div class="rounded-3xl border border-dashed border-cream-300 bg-white p-10 text-center text-sm text-ink-500">No Instagram posts yet — paste a link to get started.</div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($this->posts as $post)
                <article class="rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                    <div class="aspect-square overflow-hidden rounded-t-3xl bg-cream-200">
                        @if ($post->thumbnail_url)
                            <img src="{{ $post->thumbnail_url }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        @else
                            <div class="grid h-full place-items-center text-xs text-ink-500">No thumbnail</div>
                        @endif
                    </div>
                    <div class="p-4">
                        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700">
                            {{ $post->source?->label() ?? 'Manual' }} ·
                            @if ($post->is_hidden)
                                <span class="text-red-600">Hidden</span>
                            @elseif ($post->is_approved)
                                <span class="text-green-600">Visible</span>
                            @else
                                <span class="text-ink-500">Pending</span>
                            @endif
                        </p>
                        @if ($post->caption)
                            <p class="mt-2 text-xs text-ink-700">{{ \Illuminate\Support\Str::limit($post->caption, 100) }}</p>
                        @endif
                        <a href="{{ $post->permalink }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-xs font-semibold text-gold-500 hover:text-brand-900">View on Instagram →</a>

                        <div class="mt-3 flex items-center justify-end">
                            @if ($confirmDeleteId === $post->id)
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" wire:click="delete({{ $post->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                    <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                </div>
                            @else
                                <x-admin.actions-menu>
                                    <button type="button" wire:click="toggleApprove({{ $post->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                        <svg class="size-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                        {{ $post->is_approved ? 'Unapprove' : 'Approve' }}
                                    </button>
                                    <button type="button" wire:click="toggleHide({{ $post->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58M9.88 5.09A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a13.16 13.16 0 0 1-1.67 2.68M6.61 6.61A13.526 13.526 0 0 0 1 12s4 7 11 7a10.94 10.94 0 0 0 5.39-1.39"/></svg>
                                        {{ $post->is_hidden ? 'Unhide' : 'Hide' }}
                                    </button>
                                    <button type="button" wire:click="askDelete({{ $post->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
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

        <div>{{ $this->posts->links() }}</div>
    @endif
</div>
