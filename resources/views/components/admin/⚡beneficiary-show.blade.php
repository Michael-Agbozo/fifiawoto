<?php

use App\Enums\SupportStatus;
use App\Enums\TimelineEntryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDocument;
use App\Models\BeneficiaryFolder;
use App\Models\BeneficiaryTimelineEntry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Beneficiary $beneficiary;

    public ?int $currentFolderId = null;

    public ?int $confirmDeleteFolderId = null;

    public ?int $confirmDeleteDocumentId = null;

    public ?int $renameFolderId = null;

    public string $renameFolderName = '';

    public ?string $flashMessage = null;

    #[Validate('required|string|max:120')]
    public string $newFolderName = '';

    public bool $showFolderForm = false;

    #[Validate('required|string')]
    public string $timelineType = 'note';

    #[Validate('nullable|string|max:1000')]
    public string $timelineDescription = '';

    public bool $showTimelineForm = false;

    public bool $showUploadForm = false;

    /** Files being staged for upload — supports multi-select. */
    public array $documentUploads = [];

    public string $documentDescription = '';

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary->load(['assignedTo', 'folders', 'timeline.recordedBy', 'documents']);
    }

    // ---------- Status / quick actions ----------
    public function setStatus(string $status): void
    {
        if (! in_array($status, array_column(SupportStatus::cases(), 'value'), true)) {
            return;
        }

        $this->beneficiary->update(['status' => $status]);
        $this->beneficiary->refresh();

        // Auto timeline entry
        $type = match ($status) {
            SupportStatus::Approved->value => TimelineEntryType::SupportApproved,
            SupportStatus::Active->value => TimelineEntryType::AidDelivered,
            SupportStatus::Completed->value => TimelineEntryType::FollowupVisit,
            default => TimelineEntryType::CaseReviewed,
        };

        BeneficiaryTimelineEntry::query()->create([
            'beneficiary_id' => $this->beneficiary->id,
            'type' => $type->value,
            'description' => 'Status set to '.SupportStatus::from($status)->label().' by '.auth()->user()?->name,
            'occurred_at' => now(),
            'recorded_by' => auth()->id(),
        ]);

        $this->flashMessage = "Status updated to {$this->beneficiary->status->label()}.";
    }

    // ---------- Folder navigation ----------
    public function openFolder(int $folderId): void
    {
        $exists = BeneficiaryFolder::query()
            ->where('beneficiary_id', $this->beneficiary->id)
            ->whereKey($folderId)
            ->exists();
        if ($exists) {
            $this->currentFolderId = $folderId;
        }
        $this->closeAllForms();
    }

    public function goToRoot(): void
    {
        $this->currentFolderId = null;
        $this->closeAllForms();
    }

    public function goUp(): void
    {
        $current = $this->currentFolder;
        $this->currentFolderId = $current?->parent_id;
        $this->closeAllForms();
    }

    protected function closeAllForms(): void
    {
        $this->showFolderForm = false;
        $this->showUploadForm = false;
        $this->newFolderName = '';
        $this->documentUploads = [];
        $this->documentDescription = '';
        $this->renameFolderId = null;
        $this->confirmDeleteFolderId = null;
        $this->confirmDeleteDocumentId = null;
        $this->resetErrorBag();
    }

    // ---------- Folders ----------
    public function startCreateFolder(): void
    {
        $this->newFolderName = '';
        $this->showFolderForm = true;
        $this->showUploadForm = false;
        $this->resetErrorBag();
    }

    public function cancelFolder(): void
    {
        $this->showFolderForm = false;
        $this->newFolderName = '';
    }

    public function createFolder(): void
    {
        $this->validateOnly('newFolderName');

        BeneficiaryFolder::query()->create([
            'beneficiary_id' => $this->beneficiary->id,
            'parent_id' => $this->currentFolderId,
            'name' => $this->newFolderName,
            'slug' => Str::slug($this->newFolderName).'-'.Str::lower(Str::random(4)),
            'created_by' => auth()->id(),
        ]);

        $this->showFolderForm = false;
        $this->newFolderName = '';
        $this->beneficiary->load('folders');
        $this->flashMessage = 'Folder created.';
    }

    public function startRenameFolder(int $folderId): void
    {
        $folder = BeneficiaryFolder::query()->findOrFail($folderId);
        $this->renameFolderId = $folder->id;
        $this->renameFolderName = $folder->name;
    }

    public function saveRename(int $folderId): void
    {
        if (! Str::length($this->renameFolderName)) {
            return;
        }
        BeneficiaryFolder::query()->whereKey($folderId)->update(['name' => $this->renameFolderName]);
        $this->renameFolderId = null;
        $this->renameFolderName = '';
        $this->beneficiary->load('folders');
        $this->flashMessage = 'Folder renamed.';
    }

    public function cancelRename(): void
    {
        $this->renameFolderId = null;
        $this->renameFolderName = '';
    }

    public function askDeleteFolder(int $folderId): void
    {
        $this->confirmDeleteFolderId = $folderId;
    }

    public function cancelDeleteFolder(): void
    {
        $this->confirmDeleteFolderId = null;
    }

    public function deleteFolder(int $folderId): void
    {
        $folder = BeneficiaryFolder::query()->findOrFail($folderId);

        // If the deleted folder is the one we're viewing, jump back to its parent.
        if ($this->currentFolderId === $folder->id) {
            $this->currentFolderId = $folder->parent_id;
        }

        // Recursively gather all descendant folder + document IDs so we can clean up files.
        $folderIds = collect([$folder->id]);
        $stack = [$folder->id];
        while ($stack) {
            $id = array_pop($stack);
            $childIds = BeneficiaryFolder::query()->where('parent_id', $id)->pluck('id')->all();
            foreach ($childIds as $cid) {
                $folderIds->push($cid);
                $stack[] = $cid;
            }
        }

        $docs = BeneficiaryDocument::query()->whereIn('folder_id', $folderIds)->get();
        foreach ($docs as $doc) {
            if ($doc->path && Storage::disk($doc->disk ?: 'public')->exists($doc->path)) {
                Storage::disk($doc->disk ?: 'public')->delete($doc->path);
            }
        }
        BeneficiaryDocument::query()->whereIn('folder_id', $folderIds)->delete();
        BeneficiaryFolder::query()->whereIn('id', $folderIds)->delete();

        $this->confirmDeleteFolderId = null;
        $this->beneficiary->load(['folders', 'documents']);
        $this->flashMessage = 'Folder and its contents removed.';
    }

    // ---------- Documents (real file upload) ----------
    public function startUpload(): void
    {
        $this->documentUploads = [];
        $this->documentDescription = '';
        $this->showUploadForm = true;
        $this->showFolderForm = false;
        $this->resetErrorBag();
    }

    public function cancelUpload(): void
    {
        $this->showUploadForm = false;
        $this->documentUploads = [];
        $this->documentDescription = '';
    }

    public function uploadDocuments(): void
    {
        $this->validate([
            'documentUploads' => 'required|array|min:1',
            'documentUploads.*' => 'file|max:20480',
            'documentDescription' => 'nullable|string|max:255',
        ]);

        $saved = 0;
        foreach ($this->documentUploads as $upload) {
            $path = $upload->store("beneficiaries/{$this->beneficiary->id}/documents", 'public');

            BeneficiaryDocument::query()->create([
                'beneficiary_id' => $this->beneficiary->id,
                'folder_id' => $this->currentFolderId,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $upload->getClientOriginalName(),
                'mime_type' => $upload->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $upload->getSize() ?: 0,
                'description' => $this->documentDescription ?: null,
                'uploaded_by' => auth()->id(),
                'scan_status' => 'pending',
            ]);
            $saved++;
        }

        $this->showUploadForm = false;
        $this->documentUploads = [];
        $this->documentDescription = '';
        $this->beneficiary->load('documents');
        $this->flashMessage = $saved === 1 ? 'File uploaded.' : "{$saved} files uploaded.";
    }

    public function askDeleteDocument(int $documentId): void
    {
        $this->confirmDeleteDocumentId = $documentId;
    }

    public function cancelDeleteDocument(): void
    {
        $this->confirmDeleteDocumentId = null;
    }

    public function deleteDocument(int $documentId): void
    {
        $doc = BeneficiaryDocument::query()->findOrFail($documentId);
        if ($doc->path && Storage::disk($doc->disk ?: 'public')->exists($doc->path)) {
            Storage::disk($doc->disk ?: 'public')->delete($doc->path);
        }
        $doc->delete();

        $this->confirmDeleteDocumentId = null;
        $this->beneficiary->load('documents');
        $this->flashMessage = 'Document removed.';
    }

    // ---------- Timeline ----------
    public function startTimelineEntry(): void
    {
        $this->timelineType = TimelineEntryType::Note->value;
        $this->timelineDescription = '';
        $this->showTimelineForm = true;
        $this->resetErrorBag();
    }

    public function cancelTimelineEntry(): void
    {
        $this->showTimelineForm = false;
    }

    public function saveTimelineEntry(): void
    {
        $this->validate([
            'timelineType' => 'required|string',
            'timelineDescription' => 'nullable|string|max:1000',
        ]);

        BeneficiaryTimelineEntry::query()->create([
            'beneficiary_id' => $this->beneficiary->id,
            'type' => $this->timelineType,
            'description' => $this->timelineDescription ?: null,
            'occurred_at' => now(),
            'recorded_by' => auth()->id(),
        ]);

        $this->showTimelineForm = false;
        $this->timelineDescription = '';
        $this->beneficiary->load('timeline.recordedBy');
        $this->flashMessage = 'Timeline entry added.';
    }

    #[Computed]
    public function currentFolder(): ?BeneficiaryFolder
    {
        return $this->currentFolderId
            ? $this->beneficiary->folders->firstWhere('id', $this->currentFolderId)
            : null;
    }

    #[Computed]
    public function visibleFolders()
    {
        return $this->beneficiary->folders
            ->where('parent_id', $this->currentFolderId)
            ->sortBy('name')
            ->values();
    }

    #[Computed]
    public function visibleDocuments()
    {
        return $this->beneficiary->documents
            ->where('folder_id', $this->currentFolderId)
            ->sortByDesc('id')
            ->values();
    }

    #[Computed]
    public function breadcrumbs(): array
    {
        $current = $this->currentFolder;
        if (! $current) {
            return [];
        }

        return array_merge($current->ancestors(), [$current]);
    }

    public function totalChildCount(BeneficiaryFolder $folder): int
    {
        $folderChildren = $this->beneficiary->folders->where('parent_id', $folder->id)->count();
        $docs = $this->beneficiary->documents->where('folder_id', $folder->id)->count();

        return $folderChildren + $docs;
    }

    public function with(): array
    {
        return [
            'statusOptions' => SupportStatus::options(),
            'timelineTypes' => TimelineEntryType::options(),
        ];
    }
}; ?>

<div class="space-y-6">
    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700">×</button>
        </div>
    @endif

    <a href="{{ route('admin.beneficiaries.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500 hover:text-gold-500" wire:navigate>
        ← Back to beneficiaries
    </a>

    <div class="grid gap-6 lg:grid-cols-[2fr_3fr]">
        {{-- LEFT: profile card --}}
        <article class="space-y-4 rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <div class="flex items-center gap-4">
                <x-admin.avatar :src="$beneficiary->photo_path" :name="$beneficiary->full_name" size="lg" />
                <div class="min-w-0">
                    <h2 class="font-serif text-2xl font-bold text-ink-900">{{ $beneficiary->full_name }}</h2>
                    <p class="text-xs text-ink-500">{{ $beneficiary->region }}{{ $beneficiary->region ? ', ' : '' }}{{ $beneficiary->country }}</p>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700">Category</dt>
                    <dd class="mt-1 text-ink-900">{{ $beneficiary->category->label() }}</dd>
                </div>
                <div>
                    <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700">Status</dt>
                    <dd class="mt-1"><x-admin.status-pill :palette="$beneficiary->status->palette()" :label="$beneficiary->status->label()" /></dd>
                </div>
                @if ($beneficiary->date_of_birth)
                    <div>
                        <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700">Born</dt>
                        <dd class="mt-1 text-ink-900">{{ $beneficiary->date_of_birth->format('M j, Y') }}</dd>
                    </div>
                @endif
                @if ($beneficiary->phone)
                    <div>
                        <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700">Phone</dt>
                        <dd class="mt-1 text-ink-900">{{ $beneficiary->phone }}</dd>
                    </div>
                @endif
                @if ($beneficiary->email)
                    <div class="col-span-2">
                        <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700">Email</dt>
                        <dd class="mt-1 text-ink-900">{{ $beneficiary->email }}</dd>
                    </div>
                @endif
                @if ($beneficiary->assignedTo)
                    <div class="col-span-2">
                        <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-700">Assigned to</dt>
                        <dd class="mt-1 text-ink-900">{{ $beneficiary->assignedTo->name }}</dd>
                    </div>
                @endif
            </dl>

            <div>
                <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Description</p>
                <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $beneficiary->description }}</p>
            </div>

            @if ($beneficiary->notes)
                <div>
                    <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Internal notes</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $beneficiary->notes }}</p>
                </div>
            @endif

            <div class="border-t border-cream-200 pt-4">
                <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Quick status</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($statusOptions as $value => $label)
                        <button type="button" wire:click="setStatus('{{ $value }}')"
                            @class([
                                'inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                'bg-gold-500 text-white' => $beneficiary->status->value === $value,
                                'border border-cream-300 text-ink-700 hover:border-gold-500 hover:text-gold-500' => $beneficiary->status->value !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </article>

        {{-- RIGHT: folders + timeline --}}
        <div class="space-y-6">
            {{-- Folders + documents --}}
            <section class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-serif text-lg font-bold text-ink-900">Folders &amp; documents</h3>
                        <p class="text-xs text-ink-500">Drag-style explorer · click any folder to open it</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="startCreateFolder" class="inline-flex items-center gap-1.5 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v6m-3-3h6"/></svg>
                            New folder
                        </button>
                        <button type="button" wire:click="startUpload" class="inline-flex items-center gap-1.5 rounded-xl bg-gold-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0 4 4m-4-4-4 4M4 20h16"/></svg>
                            Upload files
                        </button>
                    </div>
                </div>

                {{-- Breadcrumb trail --}}
                <nav class="mt-4 flex flex-wrap items-center gap-1 text-xs text-ink-500" aria-label="Breadcrumb">
                    <button type="button" wire:click="goToRoot" @class([
                        'inline-flex items-center gap-1 rounded-md px-2 py-1 font-semibold transition',
                        'bg-cream-100 text-brand-900' => empty($this->breadcrumbs),
                        'hover:bg-cream-100 hover:text-brand-900' => ! empty($this->breadcrumbs),
                    ])>
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 12 2.25 21.75 12M4.5 9.75v10.125a1.125 1.125 0 0 0 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125a1.125 1.125 0 0 0 1.125-1.125V9.75"/></svg>
                        Root
                    </button>
                    @foreach ($this->breadcrumbs as $crumb)
                        <span class="text-ink-400">/</span>
                        @if ($loop->last)
                            <span class="rounded-md bg-cream-100 px-2 py-1 font-semibold text-brand-900">{{ $crumb->name }}</span>
                        @else
                            <button type="button" wire:click="openFolder({{ $crumb->id }})" class="rounded-md px-2 py-1 font-semibold transition hover:bg-cream-100 hover:text-brand-900">{{ $crumb->name }}</button>
                        @endif
                    @endforeach
                    @if ($this->currentFolder)
                        <button type="button" wire:click="goUp" class="ml-auto inline-flex items-center gap-1 rounded-md border border-cream-300 px-2 py-1 font-semibold transition hover:border-brand-900 hover:text-brand-900">
                            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                            Up
                        </button>
                    @endif
                </nav>

                {{-- New folder form --}}
                @if ($showFolderForm)
                    <form wire:submit="createFolder" class="mt-4 flex flex-wrap gap-2 rounded-2xl border border-dashed border-brand-200 bg-brand-50/40 p-3">
                        <input type="text" wire:model="newFolderName" placeholder="{{ $this->currentFolder ? 'New subfolder in '.$this->currentFolder->name : 'New top-level folder' }}" required class="flex-1 rounded-lg border border-cream-300 bg-white px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-gold-500 px-3 py-2 text-xs font-bold text-white transition hover:bg-brand-900">Create folder</button>
                        <button type="button" wire:click="cancelFolder" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-2 text-xs font-semibold text-ink-700">Cancel</button>
                        @error('newFolderName') <p class="basis-full text-xs text-red-600">{{ $message }}</p> @enderror
                    </form>
                @endif

                {{-- Upload form --}}
                @if ($showUploadForm)
                    <form wire:submit="uploadDocuments" class="mt-4 space-y-3 rounded-2xl border border-dashed border-gold-300 bg-cream-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">
                            Upload to {{ $this->currentFolder?->name ?? 'Root' }}
                        </p>
                        <label class="block">
                            <input
                                type="file"
                                wire:model="documentUploads"
                                multiple
                                class="block w-full rounded-lg border border-cream-300 bg-white text-sm file:mr-3 file:rounded-l-lg file:border-0 file:bg-brand-900 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-[0.18em] file:text-white hover:file:bg-brand-700"
                            >
                        </label>
                        <div wire:loading wire:target="documentUploads" class="text-xs text-brand-700">Uploading…</div>
                        @if (! empty($documentUploads))
                            <ul class="space-y-1 text-xs text-ink-700">
                                @foreach ($documentUploads as $upload)
                                    <li class="flex items-center gap-2">
                                        <svg class="size-3.5 text-brand-700" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                                        {{ $upload->getClientOriginalName() }}
                                        <span class="text-ink-500">· {{ number_format($upload->getSize() / 1024, 1) }} KB</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <input type="text" wire:model="documentDescription" placeholder="Optional description applied to all uploaded files" class="w-full rounded-lg border border-cream-300 bg-white px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @error('documentUploads') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('documentUploads.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center rounded-lg bg-gold-500 px-4 py-2 text-xs font-bold text-white transition hover:bg-brand-900 disabled:opacity-60">
                                <span wire:loading.remove>Upload</span>
                                <span wire:loading>Uploading…</span>
                            </button>
                            <button type="button" wire:click="cancelUpload" class="inline-flex items-center rounded-lg border border-cream-300 px-4 py-2 text-xs font-semibold text-ink-700">Cancel</button>
                            <p class="text-xs text-ink-500">Up to 20 MB per file.</p>
                        </div>
                    </form>
                @endif

                {{-- Folder grid --}}
                @php
                    $folders = $this->visibleFolders;
                    $docs = $this->visibleDocuments;
                @endphp
                @if ($folders->isEmpty() && $docs->isEmpty() && ! $showFolderForm && ! $showUploadForm)
                    <div class="mt-6 rounded-2xl border border-dashed border-cream-300 bg-cream-50 p-8 text-center text-sm text-ink-500">
                        This folder is empty. Use <strong class="font-semibold text-ink-900">New folder</strong> or <strong class="font-semibold text-ink-900">Upload files</strong> to add content.
                    </div>
                @endif

                @if ($folders->isNotEmpty())
                    <div class="mt-5">
                        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.22em] text-brand-700">Folders</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($folders as $folder)
                                <div class="group rounded-2xl border border-cream-200 bg-cream-50 transition hover:border-brand-200 hover:bg-cream-100">
                                    @if ($renameFolderId === $folder->id)
                                        <form wire:submit="saveRename({{ $folder->id }})" class="flex items-center gap-2 px-3 py-2.5">
                                            <input type="text" wire:model="renameFolderName" autofocus class="flex-1 rounded-lg border border-cream-300 bg-white px-3 py-1.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                                            <button type="submit" class="inline-flex items-center rounded-lg bg-gold-500 px-3 py-1.5 text-xs font-bold text-white">Save</button>
                                            <button type="button" wire:click="cancelRename" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700">Cancel</button>
                                        </form>
                                    @else
                                        <div class="flex items-center justify-between gap-2 px-3 py-2.5">
                                            <button type="button" wire:click="openFolder({{ $folder->id }})" class="flex min-w-0 flex-1 items-center gap-2 text-left">
                                                <svg class="size-5 shrink-0 text-gold-500" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z"/></svg>
                                                <span class="min-w-0">
                                                    <span class="truncate block font-semibold text-ink-900 group-hover:text-brand-900">{{ $folder->name }}</span>
                                                    <span class="block text-xs text-ink-500">{{ $this->totalChildCount($folder) }} item(s)</span>
                                                </span>
                                            </button>
                                            @if ($confirmDeleteFolderId === $folder->id)
                                                <div class="flex items-center gap-1">
                                                    <button type="button" wire:click="deleteFolder({{ $folder->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-2 py-1 text-[10px] font-bold text-white">Delete</button>
                                                    <button type="button" wire:click="cancelDeleteFolder" class="inline-flex items-center rounded-lg border border-cream-300 px-2 py-1 text-[10px] font-semibold text-ink-700">Cancel</button>
                                                </div>
                                            @else
                                                <x-admin.actions-menu>
                                                    <button type="button" wire:click="openFolder({{ $folder->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                        <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                        Open folder
                                                    </button>
                                                    <button type="button" wire:click="startRenameFolder({{ $folder->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                                        Rename
                                                    </button>
                                                    <button type="button" wire:click="askDeleteFolder({{ $folder->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                                        Delete folder
                                                    </button>
                                                </x-admin.actions-menu>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($docs->isNotEmpty())
                    <div class="mt-5">
                        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.22em] text-brand-700">Files</p>
                        <ul class="mt-2 divide-y divide-cream-200 rounded-2xl border border-cream-200 bg-white">
                            @foreach ($docs as $doc)
                                @php
                                    $url = $doc->path ? asset('storage/'.$doc->path) : null;
                                    $mime = (string) $doc->mime_type;
                                    $iconColor = match (true) {
                                        str_starts_with($mime, 'image/') => 'text-emerald-600',
                                        str_starts_with($mime, 'video/') => 'text-purple-600',
                                        str_contains($mime, 'pdf') => 'text-red-600',
                                        str_contains($mime, 'word') || str_contains($mime, 'document') => 'text-blue-600',
                                        str_contains($mime, 'sheet') || str_contains($mime, 'excel') || str_contains($mime, 'csv') => 'text-green-600',
                                        default => 'text-ink-500',
                                    };
                                @endphp
                                <li class="flex items-center justify-between gap-2 px-4 py-2.5">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <svg class="size-5 shrink-0 {{ $iconColor }}" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm-1 7V3.5L18.5 9H13Z"/></svg>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-ink-900">{{ $doc->original_name }}</p>
                                            <p class="truncate text-xs text-ink-500">
                                                {{ $doc->size_bytes ? number_format($doc->size_bytes / 1024, 1).' KB' : '—' }}
                                                @if ($doc->description) · {{ $doc->description }} @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @if ($url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-8 place-items-center rounded-full text-ink-500 transition hover:bg-cream-100 hover:text-brand-900" title="Open file">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7v7M21 3l-9 9M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                                            </a>
                                            <a href="{{ $url }}" download class="grid size-8 place-items-center rounded-full text-ink-500 transition hover:bg-cream-100 hover:text-brand-900" title="Download">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5 5 5-5M12 4v12"/></svg>
                                            </a>
                                        @endif
                                        @if ($confirmDeleteDocumentId === $doc->id)
                                            <button type="button" wire:click="deleteDocument({{ $doc->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-2 py-1 text-[10px] font-bold text-white">Delete</button>
                                            <button type="button" wire:click="cancelDeleteDocument" class="inline-flex items-center rounded-lg border border-cream-300 px-2 py-1 text-[10px] font-semibold text-ink-700">Cancel</button>
                                        @else
                                            <x-admin.actions-menu>
                                                <button type="button" wire:click="askDeleteDocument({{ $doc->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                                    Delete file
                                                </button>
                                            </x-admin.actions-menu>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            {{-- Timeline --}}
            <section class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                <div class="flex items-center justify-between">
                    <h3 class="font-serif text-lg font-bold text-ink-900">Case timeline</h3>
                    <button type="button" wire:click="startTimelineEntry" class="inline-flex items-center gap-1 rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-gold-500 hover:text-gold-500">+ Add entry</button>
                </div>

                @if ($showTimelineForm)
                    <form wire:submit="saveTimelineEntry" class="mt-4 space-y-2 rounded-2xl border border-dashed border-cream-300 bg-cream-50 p-4">
                        <select wire:model="timelineType" class="block w-full rounded-lg border border-cream-300 bg-white px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                            @foreach ($timelineTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea wire:model="timelineDescription" rows="3" placeholder="What happened? (optional)" class="block w-full rounded-lg border border-cream-300 bg-white px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="inline-flex items-center rounded-lg bg-gold-500 px-3 py-1.5 text-xs font-bold text-white">Add entry</button>
                            <button type="button" wire:click="cancelTimelineEntry" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700">Cancel</button>
                        </div>
                    </form>
                @endif

                <ol class="mt-4 space-y-3">
                    @forelse ($beneficiary->timeline as $entry)
                        <li class="flex gap-3 rounded-2xl border border-cream-200 bg-cream-50 px-4 py-3">
                            <span class="mt-1 inline-flex aspect-square size-2.5 shrink-0 rounded-full bg-gold-500"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink-900">{{ $entry->type->label() }}</p>
                                @if ($entry->description)
                                    <p class="mt-1 whitespace-pre-line text-sm text-ink-700">{{ $entry->description }}</p>
                                @endif
                                <p class="mt-1 text-xs text-ink-500">
                                    {{ $entry->occurred_at?->diffForHumans() }}
                                    @if ($entry->recordedBy) · {{ $entry->recordedBy->name }} @endif
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-ink-500">No timeline entries yet.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</div>
