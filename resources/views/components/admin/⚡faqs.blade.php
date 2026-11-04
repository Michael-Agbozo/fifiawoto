<?php

use App\Models\Faq;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public string $placement = 'contact';

    public string $question = '';

    public string $answer = '';

    public bool $is_published = true;

    public int $sort = 0;

    public bool $showForm = false;

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public string $search = '';

    public string $filter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canDo('faqs', 'view'), 403);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'placement' => ['required', Rule::in(array_keys(Faq::PAGES))],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:3000'],
            'is_published' => ['boolean'],
            'sort' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->placement = in_array($this->filter, array_keys(Faq::PAGES), true) ? $this->filter : 'contact';
        $this->sort = (int) Faq::query()->onPage($this->placement)->max('sort') + 1;
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $faq = Faq::query()->findOrFail($id);
        $this->editingId = $faq->id;
        $this->placement = $faq->page;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->is_published = $faq->is_published;
        $this->sort = $faq->sort;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('faqs', $this->editingId ? 'update' : 'create'), 403);

        $validated = $this->validate();
        $data = [
            'page' => $validated['placement'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'is_published' => $validated['is_published'],
            'sort' => $validated['sort'],
        ];

        if ($this->editingId) {
            Faq::query()->findOrFail($this->editingId)->update($data);
            $this->flashMessage = 'Question updated.';
        } else {
            Faq::query()->create($data);
            $this->flashMessage = 'Question added to the '.Faq::PAGES[$data['page']].'.';
        }

        $this->resetForm();
    }

    public function togglePublished(int $id): void
    {
        abort_unless(auth()->user()?->canDo('faqs', 'update'), 403);

        $faq = Faq::query()->findOrFail($id);
        $faq->update(['is_published' => ! $faq->is_published]);

        $this->flashMessage = $faq->is_published ? 'Question is now visible on the site.' : 'Question is hidden from the site.';
    }

    /** Swap a question with its neighbour on the same page. */
    public function move(int $id, int $direction): void
    {
        abort_unless(auth()->user()?->canDo('faqs', 'update'), 403);

        $siblings = Faq::query()->onPage(Faq::query()->findOrFail($id)->page)->ordered()->get()->values();
        $index = $siblings->search(fn (Faq $faq) => $faq->id === $id);
        $target = $index + ($direction < 0 ? -1 : 1);

        if (! $siblings->has($target)) {
            return;
        }

        $reordered = $siblings->all();
        [$reordered[$index], $reordered[$target]] = [$reordered[$target], $reordered[$index]];

        foreach ($reordered as $sort => $faq) {
            $faq->update(['sort' => $sort]);
        }
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
        abort_unless(auth()->user()?->canDo('faqs', 'delete'), 403);

        Faq::query()->findOrFail($id)->delete();

        $this->confirmDeleteId = null;
        $this->flashMessage = 'Question deleted.';
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
        $this->reset(['placement', 'question', 'answer', 'is_published', 'sort', 'showForm']);
        $this->resetErrorBag();
    }

    #[Computed]
    public function faqs()
    {
        $query = Faq::query()->orderBy('page')->ordered();

        if (array_key_exists($this->filter, Faq::PAGES)) {
            $query->onPage($this->filter);
        } elseif ($this->filter === 'hidden') {
            $query->where('is_published', false);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(fn ($q) => $q->where('question', 'like', $needle)->orWhere('answer', 'like', $needle));
        }

        return $query->paginate(20);
    }

    /** @return array<string, int> */
    #[Computed]
    public function totals(): array
    {
        return [
            'all' => Faq::query()->count(),
            'published' => Faq::query()->published()->count(),
            ...collect(Faq::PAGES)->map(fn ($label, $page) => Faq::query()->onPage($page)->count())->all(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-admin.section-header
        title="Frequently asked questions"
        :subtitle="$this->totals['all'].' total · '.$this->totals['published'].' published'"
    >
        <x-slot:actions>
            <select wire:model.live="filter" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-heading hover:text-heading focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
                <option value="">All pages ({{ $this->totals['all'] }})</option>
                @foreach (\App\Models\Faq::PAGES as $value => $label)
                    <option value="{{ $value }}">{{ $label }} ({{ $this->totals[$value] }})</option>
                @endforeach
                <option value="hidden">Hidden</option>
            </select>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search questions" class="rounded-xl border border-cream-300 bg-surface px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-heading focus:outline-none focus:ring-2 focus:ring-heading/15">
            @if (auth()->user()?->canDo('faqs', 'create'))
                <button type="button" wire:click="startCreate" class="btn btn-primary gap-2 px-4 py-2 text-xs">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add question
                </button>
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

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit question' : 'Add question'" size="xl" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label for="faq_page" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Show on</label>
                <select id="faq_page" wire:model="placement" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @foreach (\App\Models\Faq::PAGES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('placement') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="faq_question" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Question</label>
                <input id="faq_question" type="text" wire:model="question" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                @error('question') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="faq_answer" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Answer</label>
                <textarea id="faq_answer" wire:model="answer" rows="5" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                @error('answer') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="faq_sort" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Sort order</label>
                    <input id="faq_sort" type="number" min="0" max="9999" wire:model="sort" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    <p class="mt-1 text-xs text-ink-500">Lower numbers appear first.</p>
                </div>
                <div class="flex items-center">
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-ink-700">
                        <input type="checkbox" wire:model="is_published" class="size-4 rounded border-cream-300 text-gold-500 focus:ring-gold-500">
                        <span>Show on the website</span>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" class="btn btn-primary disabled:opacity-60 px-6 py-3 text-sm">
                    <span wire:loading.remove>{{ $editingId ? 'Save changes' : 'Add question' }}</span>
                    <span wire:loading>Saving…</span>
                </button>
                <button type="button" wire:click="cancel" class="btn btn-ghost px-6 py-3 text-sm">Cancel</button>
            </div>
        </form>
    </x-admin.modal>

    <div class="overflow-x-auto rounded-3xl border border-cream-300 bg-surface shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <table class="w-full text-sm">
            <thead class="bg-cream-100/60 text-xs uppercase tracking-[0.18em] text-ink-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold">Question</th>
                    <th class="px-5 py-3 text-left font-semibold">Page</th>
                    <th class="px-5 py-3 text-left font-semibold">Order</th>
                    <th class="px-5 py-3 text-left font-semibold">Status</th>
                    <th class="px-5 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->faqs as $faq)
                    <tr wire:key="faq-{{ $faq->id }}" class="border-t border-cream-200 align-top transition hover:bg-cream-100/40">
                        <td class="max-w-xl px-5 py-3">
                            <p class="font-semibold text-ink-900">{{ $faq->question }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-ink-500">{{ $faq->answer }}</p>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-ink-700">{{ \App\Models\Faq::PAGES[$faq->page] ?? $faq->page }}</td>
                        <td class="px-5 py-3">
                            @if (auth()->user()?->canDo('faqs', 'update'))
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" wire:click="move({{ $faq->id }}, -1)" class="grid size-7 place-items-center rounded-lg text-ink-500 hover:bg-cream-100 hover:text-heading" aria-label="Move up">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/></svg>
                                    </button>
                                    <button type="button" wire:click="move({{ $faq->id }}, 1)" class="grid size-7 place-items-center rounded-lg text-ink-500 hover:bg-cream-100 hover:text-heading" aria-label="Move down">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                                    </button>
                                </div>
                            @else
                                <span class="text-ink-500">#{{ $faq->sort }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if ($faq->is_published)
                                <x-admin.status-pill palette="green" label="Published" />
                            @else
                                <x-admin.status-pill palette="gray" label="Hidden" />
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if ($confirmDeleteId === $faq->id)
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" wire:click="delete({{ $faq->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                    <button type="button" wire:click="cancelDelete" class="btn btn-ghost px-3 py-1.5 text-xs">Cancel</button>
                                </div>
                            @else
                                <x-admin.actions-menu>
                                    @if (auth()->user()?->canDo('faqs', 'update'))
                                        <button type="button" wire:click="togglePublished({{ $faq->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-heading">
                                            <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            {{ $faq->is_published ? 'Hide from site' : 'Publish on site' }}
                                        </button>
                                        <button type="button" wire:click="startEdit({{ $faq->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-heading">
                                            <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                            Edit
                                        </button>
                                    @endif
                                    @if (auth()->user()?->canDo('faqs', 'delete'))
                                        <button type="button" wire:click="askDelete({{ $faq->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
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
                            No questions yet. Click <strong class="font-semibold text-ink-900">Add question</strong> to create the first one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->faqs->links() }}</div>
</div>
