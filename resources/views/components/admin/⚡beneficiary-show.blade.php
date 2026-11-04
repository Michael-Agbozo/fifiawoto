<?php

use App\Enums\SupportStatus;
use App\Enums\TimelineEntryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDocument;
use App\Models\BeneficiaryFolder;
use App\Models\BeneficiaryTimelineEntry;
use App\Models\SponsorshipPhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Support\SecureDocumentStore;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Beneficiary $beneficiary;

    public string $publicDisplayName = '';

    public string $publicStory = '';
    public string $publicCommunityStory = '';
    public string $publicCountryStory = '';

    public string $publicCountry = '';

    public string $guardianConsentReference = '';

    public bool $guardianConsent = false;

    public bool $sponsorshipPublished = false;

    public $sponsorshipPhoto = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $galleryUploads = [];

    public bool $galleryUploadsSensitive = false;

    public string $driveFolderUrl = '';

    /** Only changed server-side (openFolder/goUp/…) so it can't be pointed at another beneficiary's folder. */
    #[Locked]
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

    /** Set by the "upload folder" picker: the name of the folder being uploaded. */
    public string $uploadFolderName = '';

    public string $documentDescription = '';

    public function mount(Beneficiary $beneficiary): void
    {
        abort_unless(auth()->user()?->canDo('beneficiaries', 'view'), 403);

        $this->beneficiary = $beneficiary->load(['assignedTo', 'folders', 'timeline.recordedBy', 'documents']);
        $this->publicDisplayName = $beneficiary->public_display_name ?? '';
        $this->publicStory = $beneficiary->public_story ?? '';
        $this->publicCommunityStory = $beneficiary->public_community_story ?? '';
        $this->publicCountryStory = $beneficiary->public_country_story ?? '';
        $this->publicCountry = $beneficiary->public_country ?? '';
        $this->guardianConsentReference = $beneficiary->guardian_consent_reference ?? '';
        $this->guardianConsent = $beneficiary->guardian_consent_at !== null;
        $this->sponsorshipPublished = (bool) $beneficiary->sponsorship_published;
        $this->driveFolderUrl = (string) $beneficiary->drive_folder_url;
    }

    /** Link this beneficiary to the Google Drive folder that holds their documents and photos. */
    public function saveDriveFolder(): void
    {
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

        $this->driveFolderUrl = trim($this->driveFolderUrl);
        $this->validate(['driveFolderUrl' => Beneficiary::DRIVE_FOLDER_URL_RULES], [
            'driveFolderUrl.regex' => 'Use a Google Drive link that starts with https://drive.google.com/.',
            'driveFolderUrl.url' => 'Use a Google Drive link that starts with https://drive.google.com/.',
        ]);

        $this->beneficiary->update(['drive_folder_url' => $this->driveFolderUrl ?: null]);
        $this->flashMessage = $this->driveFolderUrl ? 'Google Drive folder linked.' : 'Google Drive folder link removed.';
        $this->dispatch('drive-folder-saved');
    }

    /**
     * Add photos to the child's public sponsorship gallery. Each upload can be
     * marked sensitive so the public page shows it blurred until a visitor opts in.
     */
    public function uploadGalleryPhotos(): void
    {
        abort_unless(auth()->user()?->refresh()->canDo('beneficiaries', 'update'), 403);

        $remaining = SponsorshipPhoto::MAX_PER_CHILD - $this->beneficiary->sponsorshipPhotos()->count();

        $this->validate([
            'galleryUploads' => ['required', 'array', 'min:1', 'max:'.max(1, $remaining)],
            'galleryUploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'galleryUploads.max' => 'A gallery can hold up to '.SponsorshipPhoto::MAX_PER_CHILD.' photos. Remove some before adding more.',
        ]);

        if ($remaining < 1) {
            $this->addError('galleryUploads', 'A gallery can hold up to '.SponsorshipPhoto::MAX_PER_CHILD.' photos. Remove some before adding more.');

            return;
        }

        $sort = (int) $this->beneficiary->sponsorshipPhotos()->max('sort');

        foreach ($this->galleryUploads as $upload) {
            $this->beneficiary->sponsorshipPhotos()->create([
                'path' => $upload->store('sponsorship/gallery', 'public'),
                'is_sensitive' => $this->galleryUploadsSensitive,
                'sort' => ++$sort,
            ]);
        }

        $count = count($this->galleryUploads);
        $this->reset('galleryUploads', 'galleryUploadsSensitive');
        $this->flashMessage = $count === 1 ? 'Gallery photo added.' : $count.' gallery photos added.';
    }

    public function toggleGallerySensitive(int $photoId): void
    {
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

        $photo = $this->beneficiary->sponsorshipPhotos()->findOrFail($photoId);
        $photo->update(['is_sensitive' => ! $photo->is_sensitive]);
        $this->flashMessage = $photo->is_sensitive ? 'Photo marked as sensitive.' : 'Photo no longer marked as sensitive.';
    }

    public function saveGalleryCaption(int $photoId, string $caption): void
    {
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

        $caption = trim($caption);
        validator(['caption' => $caption], ['caption' => 'nullable|string|max:160'])->validate();

        $this->beneficiary->sponsorshipPhotos()->findOrFail($photoId)->update(['caption' => $caption ?: null]);
    }

    public function deleteGalleryPhoto(int $photoId): void
    {
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

        $photo = $this->beneficiary->sponsorshipPhotos()->findOrFail($photoId);
        Storage::disk('public')->delete($photo->path);
        $photo->delete();
        $this->flashMessage = 'Gallery photo removed.';
    }

    public function saveSponsorship(): void
    {
        abort_unless(auth()->user()?->refresh()->canDo('beneficiaries', 'update'), 403);
        $this->beneficiary->refresh();
        $this->validate([
            'publicDisplayName' => 'nullable|string|max:60',
            'publicStory' => 'nullable|string|max:12000',
            'publicCommunityStory' => 'nullable|string|max:12000',
            'publicCountryStory' => 'nullable|string|max:12000',
            'publicCountry' => 'nullable|string|max:80',
            'guardianConsentReference' => 'nullable|string|max:255',
            'sponsorshipPhoto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        if (! $this->guardianConsent) {
            $this->sponsorshipPublished = false;
        }
        if ($this->sponsorshipPublished) {
            $this->validate([
                'publicDisplayName' => 'required|string|max:60',
                'publicStory' => 'required|string|max:12000',
                'publicCountry' => 'required|string|max:80',
                'guardianConsentReference' => 'required|string|max:255',
                'guardianConsent' => 'accepted',
            ]);
            if (! $this->beneficiary->date_of_birth || $this->beneficiary->date_of_birth->isFuture() || $this->beneficiary->date_of_birth->age >= 18 || ! in_array($this->beneficiary->status, [SupportStatus::Active, SupportStatus::Approved], true)) {
                $this->addError('sponsorshipPublished', 'Only active or approved children under 18 can be published.');

                return;
            }
            if (! $this->sponsorshipPhoto && ! $this->beneficiary->public_photo_path) {
                $this->addError('sponsorshipPhoto', 'Upload a separate photo approved for public use.');

                return;
            }
        }
        $oldPhoto = $this->beneficiary->public_photo_path;
        $photo = $oldPhoto;
        if ($this->sponsorshipPhoto && $this->sponsorshipPublished) {
            $photo = $this->sponsorshipPhoto->store('sponsorship', 'public');
        }
        if (! $this->guardianConsent) {
            $this->sponsorshipPublished = false;
        }
        if (! $this->sponsorshipPublished) {
            $photo = null;
        }
        $this->beneficiary->update([
            'public_display_name' => trim($this->publicDisplayName),
            'public_story' => trim($this->publicStory),
            'public_community_story' => trim($this->publicCommunityStory),
            'public_country_story' => trim($this->publicCountryStory),
            'public_country' => trim($this->publicCountry),
            'public_photo_path' => $photo,
            'sponsorship_published' => $this->sponsorshipPublished,
            'guardian_consent_at' => $this->guardianConsent ? ($this->beneficiary->guardian_consent_at ?? now()) : null,
            'guardian_consent_reference' => $this->guardianConsent ? trim($this->guardianConsentReference) : null,
        ]);
        if ($oldPhoto && $oldPhoto !== $photo && preg_match('~^sponsorship/[A-Za-z0-9_-]+\.[a-zA-Z]+$~', $oldPhoto)) {
            Storage::disk('public')->delete($oldPhoto);
        }
        if (! $this->guardianConsent) {
            $this->beneficiary->sponsorshipPhotos->each(function (SponsorshipPhoto $galleryPhoto): void {
                Storage::disk('public')->delete($galleryPhoto->path);
                $galleryPhoto->delete();
            });
            $this->beneficiary->unsetRelation('sponsorshipPhotos');
        }
        $this->reset('sponsorshipPhoto');
        $this->flashMessage = 'Public sponsorship settings saved.';
    }

    // ---------- Status / quick actions ----------
    public function setStatus(string $status): void
    {
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

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
        $this->uploadFolderName = '';
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
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

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
        $folder = $this->beneficiary->folders()->findOrFail($folderId);
        $this->renameFolderId = $folder->id;
        $this->renameFolderName = $folder->name;
    }

    public function saveRename(int $folderId): void
    {
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

        $this->validate(['renameFolderName' => 'required|string|max:120']);

        $this->beneficiary->folders()->whereKey($folderId)->update(['name' => $this->renameFolderName]);
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
        abort_unless(auth()->user()?->canDo('beneficiaries', 'delete'), 403);

        $folder = $this->beneficiary->folders()->findOrFail($folderId);

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
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

        $this->validate([
            'documentUploads' => 'required|array|min:1',
            'documentUploads.*' => 'file|max:20480|mimes:'.implode(',', BeneficiaryDocument::ALLOWED_EXTENSIONS),
            'documentDescription' => 'nullable|string|max:255',
            'uploadFolderName' => 'nullable|string|max:120',
        ]);

        // Scan everything first, so one bad file stops the upload before anything is stored.
        $scanResults = app(SecureDocumentStore::class)->scanAll($this->documentUploads, 'documentUploads');

        // A whole-folder upload lands in a new folder of the same name, inside the current one.
        $folderId = $this->currentFolderId;
        if (filled($this->uploadFolderName)) {
            $folderId = BeneficiaryFolder::query()->create([
                'beneficiary_id' => $this->beneficiary->id,
                'parent_id' => $this->currentFolderId,
                'name' => $this->uploadFolderName,
                'slug' => Str::slug($this->uploadFolderName).'-'.Str::lower(Str::random(4)),
                'created_by' => auth()->id(),
            ])->id;
        }

        $saved = 0;
        foreach ($this->documentUploads as $index => $upload) {
            $path = app(SecureDocumentStore::class)->store($upload, "{$this->beneficiary->id}/documents");

            BeneficiaryDocument::query()->create([
                'beneficiary_id' => $this->beneficiary->id,
                'folder_id' => $folderId,
                'disk' => 'beneficiaries',
                'path' => $path,
                'encrypted_at' => now(),
                'original_name' => $upload->getClientOriginalName(),
                'mime_type' => $upload->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $upload->getSize() ?: 0,
                'description' => $this->documentDescription ?: null,
                'uploaded_by' => auth()->id(),
                'scan_status' => $scanResults[$index],
                'scan_checked_at' => now(),
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
        abort_unless(auth()->user()?->canDo('beneficiaries', 'delete'), 403);

        $doc = $this->beneficiary->documents()->findOrFail($documentId);
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
        abort_unless(auth()->user()?->canDo('beneficiaries', 'update'), 403);

        $this->validate([
            'timelineType' => ['required', Rule::enum(TimelineEntryType::class)],
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
    @if (auth()->user()?->canDo('beneficiaries', 'update'))
    <section class="mb-6 rounded-3xl border border-brand-100 bg-surface p-6">
        <h2 class="text-2xl text-heading">Public child sponsorship</h2>
        <p class="mt-2 text-sm text-ink-500">Opt in only with recorded guardian consent. Use an alias or safe first name, an approved story without identifying details, and a separate public photo. Private case details are never copied. Unpublishing removes the public photo; upload it again to republish.</p>
        <form wire:submit="saveSponsorship" class="mt-5 space-y-4">
            @foreach (['publicDisplayName' => 'Safe public display name', 'publicCountry' => 'Public country', 'guardianConsentReference' => 'Private guardian consent record reference'] as $field => $label)
            <label class="block text-sm text-heading">{{ $label }}<input wire:model="{{ $field }}" class="mt-2 block w-full rounded-xl border border-brand-200 bg-surface p-3">@error($field)<span class="text-red-600">{{ $message }}</span>@enderror</label>
            @endforeach
            @foreach (['publicStory' => 'Overview', 'publicCommunityStory' => 'Community', 'publicCountryStory' => 'Country'] as $field => $label)
                <label class="block text-sm text-heading">{{ $label }} — approved public content<textarea wire:model="{{ $field }}" rows="8" maxlength="12000" class="mt-2 block w-full rounded-xl border border-brand-200 bg-surface p-3"></textarea>@error($field)<span class="text-red-600">{{ $message }}</span>@enderror</label>
            @endforeach
            <label class="block text-sm text-heading">Separate approved public photo<input type="file" wire:model="sponsorshipPhoto" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full">@error('sponsorshipPhoto')<span class="text-red-600">{{ $message }}</span>@enderror</label>
            <label class="flex items-start gap-3 text-sm text-heading"><input type="checkbox" wire:model="guardianConsent">I have verified and recorded guardian consent to publish this name, story, country, age, gender and photo.</label>@error('guardianConsent')<p class="text-red-600">{{ $message }}</p>@enderror
            <label class="flex gap-3 text-sm text-heading"><input type="checkbox" wire:model="sponsorshipPublished">Publish approved sponsorship profile</label>@error('sponsorshipPublished')<p class="text-red-600">{{ $message }}</p>@enderror
            <button wire:loading.attr="disabled" class="btn btn-primary disabled:opacity-50 px-5 py-3">Save public profile</button>
        </form>

        {{-- Gallery --}}
        @php $galleryPhotos = $beneficiary->sponsorshipPhotos()->get(); @endphp
        <div class="mt-8 border-t border-cream-300 pt-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h3 class="text-xl text-heading">Gallery photos</h3>
                    <p class="mt-1 max-w-2xl text-sm text-ink-500">Shown on the public profile under the story. Only upload photos the guardian has approved. Mark anything distressing to see, such as wounds or medical conditions, as sensitive: it will appear blurred until a visitor chooses to view it.</p>
                </div>
                <span class="text-xs font-semibold text-ink-500">{{ $galleryPhotos->count() }} / {{ \App\Models\SponsorshipPhoto::MAX_PER_CHILD }}</span>
            </div>

            @if ($galleryPhotos->isNotEmpty())
                <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($galleryPhotos as $photo)
                        <li wire:key="gallery-photo-{{ $photo->id }}" class="overflow-hidden rounded-2xl border border-cream-300 bg-cream-50">
                            <div class="relative aspect-[4/3] bg-cream-200">
                                <img src="{{ Storage::disk('public')->url($photo->path) }}" alt="{{ $photo->caption ?: 'Gallery photo' }}" class="h-full w-full object-cover {{ $photo->is_sensitive ? 'blur-md' : '' }}" loading="lazy">
                                @if ($photo->is_sensitive)
                                    <span class="absolute left-3 top-3 rounded-full bg-amber-500 px-2.5 py-1 text-xs font-semibold text-white">Sensitive</span>
                                @endif
                            </div>
                            <div class="space-y-3 p-3">
                                <label class="block text-xs font-semibold text-ink-700">Caption
                                    <input type="text" maxlength="160" value="{{ $photo->caption }}" placeholder="Optional caption"
                                        x-on:change="$wire.saveGalleryCaption({{ $photo->id }}, $event.target.value)"
                                        class="mt-1 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2 text-sm text-heading">
                                </label>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <button type="button" wire:click="toggleGallerySensitive({{ $photo->id }})" class="btn btn-ghost px-3 py-1.5 text-xs">
                                        {{ $photo->is_sensitive ? 'Unmark sensitive' : 'Mark sensitive' }}
                                    </button>
                                    <button type="button" wire:click="deleteGalleryPhoto({{ $photo->id }})" wire:confirm="Remove this photo from the gallery?" class="text-xs font-semibold text-red-600 hover:underline">Remove</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($galleryPhotos->count() < \App\Models\SponsorshipPhoto::MAX_PER_CHILD)
                <form wire:submit="uploadGalleryPhotos" class="mt-5 flex flex-col gap-3 rounded-2xl border border-dashed border-cream-300 p-4 sm:flex-row sm:items-center">
                    <label class="flex-1 text-sm text-heading">Add photos
                        <input type="file" multiple wire:model="galleryUploads" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-heading"><input type="checkbox" wire:model="galleryUploadsSensitive"> Mark these as sensitive</label>
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary px-5 py-2.5 text-sm disabled:opacity-50">Upload</button>
                </form>
                @error('galleryUploads')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('galleryUploads.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            @endif
        </div>
    </section>
    @endif

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
        <article class="space-y-4 rounded-3xl border border-cream-300 bg-surface p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <div class="flex items-center gap-4">
                <x-admin.avatar :src="$beneficiary->photo_path" :name="$beneficiary->full_name" size="lg" />
                <div class="min-w-0">
                    <h2 class="font-serif text-2xl font-bold text-ink-900">{{ $beneficiary->full_name }}</h2>
                    <p class="text-xs text-ink-500">{{ $beneficiary->region }}{{ $beneficiary->region ? ', ' : '' }}{{ $beneficiary->country }}</p>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-label">Category</dt>
                    <dd class="mt-1 text-ink-900">{{ $beneficiary->category->label() }}</dd>
                </div>
                <div>
                    <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-label">Status</dt>
                    <dd class="mt-1"><x-admin.status-pill :palette="$beneficiary->status->palette()" :label="$beneficiary->status->label()" /></dd>
                </div>
                @if ($beneficiary->date_of_birth)
                    <div>
                        <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-label">Born</dt>
                        <dd class="mt-1 text-ink-900">{{ $beneficiary->date_of_birth->format('M j, Y') }}</dd>
                    </div>
                @endif
                @if ($beneficiary->phone)
                    <div>
                        <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-label">Phone</dt>
                        <dd class="mt-1 text-ink-900">{{ $beneficiary->phone }}</dd>
                    </div>
                @endif
                @if ($beneficiary->email)
                    <div class="col-span-2">
                        <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-label">Email</dt>
                        <dd class="mt-1 text-ink-900">{{ $beneficiary->email }}</dd>
                    </div>
                @endif
                @if ($beneficiary->assignedTo)
                    <div class="col-span-2">
                        <dt class="font-sans text-[10px] font-semibold uppercase tracking-[0.18em] text-label">Assigned to</dt>
                        <dd class="mt-1 text-ink-900">{{ $beneficiary->assignedTo->name }}</dd>
                    </div>
                @endif
            </dl>

            {{-- Google Drive folder --}}
            <div class="rounded-2xl border border-cream-300 bg-cream-50 p-4" x-data="{ editing: {{ $beneficiary->drive_folder_url ? 'false' : 'true' }} }" @drive-folder-saved.window="editing = ! $wire.driveFolderUrl">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-surface text-heading ring-1 ring-cream-300" aria-hidden="true">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-heading">Google Drive folder</p>
                        <p class="text-xs text-ink-500">{{ $beneficiary->drive_folder_url ? 'Documents and photos are kept here.' : 'Not linked yet.' }}</p>
                    </div>
                </div>

                @if ($beneficiary->drive_folder_url)
                    <div class="mt-3 flex flex-wrap items-center gap-2" x-show="! editing">
                        <a href="{{ $beneficiary->drive_folder_url }}" target="_blank" rel="noopener" class="btn btn-primary px-4 py-2 text-sm">
                            Open Drive folder
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                        </a>
                        @if (auth()->user()?->canDo('beneficiaries', 'update'))
                            <button type="button" class="btn btn-ghost px-4 py-2 text-sm" @click="editing = true">Change link</button>
                        @endif
                    </div>
                @endif

                @if (auth()->user()?->canDo('beneficiaries', 'update'))
                    <form wire:submit="saveDriveFolder" class="mt-3 space-y-2" x-show="editing" @if ($beneficiary->drive_folder_url) x-cloak @endif>
                        <label for="drive-folder-url" class="sr-only">Google Drive folder link</label>
                        <input id="drive-folder-url" type="url" wire:model="driveFolderUrl" placeholder="https://drive.google.com/drive/folders/…" class="block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2 text-sm">
                        @error('driveFolderUrl') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary px-4 py-2 text-sm">Save link</button>
                            @if ($beneficiary->drive_folder_url)
                                <button type="button" class="btn btn-ghost px-4 py-2 text-sm" @click="editing = false">Cancel</button>
                            @endif
                        </div>
                    </form>
                @endif
            </div>

            <div>
                <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-label">Description</p>
                <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $beneficiary->description }}</p>
            </div>

            @if ($beneficiary->notes)
                <div>
                    <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-label">Internal notes</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $beneficiary->notes }}</p>
                </div>
            @endif

            <div class="border-t border-cream-200 pt-4">
                <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-label">Quick status</p>
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
            <section class="rounded-3xl border border-cream-300 bg-surface p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-serif text-lg font-bold text-ink-900">Folders &amp; documents</h3>
                        <p class="text-xs text-ink-500">Drag-style explorer · click any folder to open it</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="startCreateFolder" class="btn btn-ghost gap-1.5 px-3 py-2 text-xs">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v6m-3-3h6"/></svg>
                            New folder
                        </button>
                        <button type="button" wire:click="startUpload" class="btn btn-primary gap-1.5 px-3 py-2 text-xs">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0 4 4m-4-4-4 4M4 20h16"/></svg>
                            Upload files
                        </button>
                    </div>
                </div>

                {{-- Breadcrumb trail --}}
                <nav class="mt-4 flex flex-wrap items-center gap-1 text-xs text-ink-500" aria-label="Breadcrumb">
                    <button type="button" wire:click="goToRoot" @class([
                        'inline-flex items-center gap-1 rounded-md px-2 py-1 font-semibold transition',
                        'bg-cream-100 text-heading' => empty($this->breadcrumbs),
                        'hover:bg-cream-100 hover:text-heading' => ! empty($this->breadcrumbs),
                    ])>
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 12 2.25 21.75 12M4.5 9.75v10.125a1.125 1.125 0 0 0 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125a1.125 1.125 0 0 0 1.125-1.125V9.75"/></svg>
                        Root
                    </button>
                    @foreach ($this->breadcrumbs as $crumb)
                        <span class="text-ink-400">/</span>
                        @if ($loop->last)
                            <span class="rounded-md bg-cream-100 px-2 py-1 font-semibold text-heading">{{ $crumb->name }}</span>
                        @else
                            <button type="button" wire:click="openFolder({{ $crumb->id }})" class="rounded-md px-2 py-1 font-semibold transition hover:bg-cream-100 hover:text-heading">{{ $crumb->name }}</button>
                        @endif
                    @endforeach
                    @if ($this->currentFolder)
                        <button type="button" wire:click="goUp" class="btn btn-ghost ml-auto gap-1 px-2 py-1">
                            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                            Up
                        </button>
                    @endif
                </nav>

                {{-- New folder form --}}
                @if ($showFolderForm)
                    <form wire:submit="createFolder" class="mt-4 flex flex-wrap gap-2 rounded-2xl border border-dashed border-brand-200 bg-brand-50/40 p-3">
                        <input type="text" wire:model="newFolderName" placeholder="{{ $this->currentFolder ? 'New subfolder in '.$this->currentFolder->name : 'New top-level folder' }}" required class="flex-1 rounded-lg border border-cream-300 bg-surface px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        <button type="submit" class="btn btn-primary px-3 py-2 text-xs">Create folder</button>
                        <button type="button" wire:click="cancelFolder" class="btn btn-ghost px-3 py-2 text-xs">Cancel</button>
                        @error('newFolderName') <p class="basis-full text-xs text-red-600">{{ $message }}</p> @enderror
                    </form>
                @endif

                {{-- Upload form --}}
                @if ($showUploadForm)
                    <form wire:submit="uploadDocuments" class="mt-4 space-y-3 rounded-2xl border border-dashed border-gold-300 bg-cream-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-label">
                            Upload to {{ $this->currentFolder?->name ?? 'Root' }}
                        </p>
                        <div
                            x-data="{
                                allowed: @js(\App\Models\BeneficiaryDocument::ALLOWED_EXTENSIONS),
                                skipped: 0,
                                pickFolder(event) {
                                    const all = Array.from(event.target.files);
                                    if (all.length === 0) return;
                                    const files = all.filter((file) => this.allowed.includes(file.name.split('.').pop().toLowerCase()));
                                    this.skipped = all.length - files.length;
                                    if (files.length === 0) return;
                                    $wire.set('uploadFolderName', all[0].webkitRelativePath.split('/')[0]);
                                    $wire.uploadMultiple('documentUploads', files);
                                },
                            }"
                            class="flex flex-wrap items-center gap-3 text-xs text-ink-500"
                        >
                            <label class="btn btn-ghost cursor-pointer px-3 py-2 text-xs">
                                Upload a whole folder
                                <input type="file" webkitdirectory multiple class="sr-only" @change="pickFolder($event)">
                            </label>
                            @if (filled($uploadFolderName))
                                <span>Files go into a new folder named <strong>{{ $uploadFolderName }}</strong>.</span>
                            @endif
                            <span x-show="skipped > 0" x-cloak x-text="skipped + ' file(s) skipped: this type isn\'t allowed.'"></span>
                        </div>
                        <label class="block">
                            <input
                                type="file"
                                wire:model="documentUploads"
                                multiple
                                class="block w-full rounded-lg border border-cream-300 bg-surface text-sm file:mr-3 file:rounded-l-lg file:border-0 file:bg-brand-900 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-[0.18em] file:text-white hover:file:bg-brand-700"
                            >
                        </label>
                        <div wire:loading wire:target="documentUploads" class="text-xs text-label">Uploading…</div>
                        @if (! empty($documentUploads))
                            <ul class="space-y-1 text-xs text-ink-700">
                                @foreach ($documentUploads as $upload)
                                    <li class="flex items-center gap-2">
                                        <svg class="size-3.5 text-label" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                                        {{ $upload->getClientOriginalName() }}
                                        <span class="text-ink-500">· {{ number_format($upload->getSize() / 1024, 1) }} KB</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <input type="text" wire:model="documentDescription" placeholder="Optional description applied to all uploaded files" class="w-full rounded-lg border border-cream-300 bg-surface px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @error('documentUploads') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('documentUploads.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary disabled:opacity-60 px-4 py-2 text-xs">
                                <span wire:loading.remove>Upload</span>
                                <span wire:loading>Uploading…</span>
                            </button>
                            <button type="button" wire:click="cancelUpload" class="btn btn-ghost px-4 py-2 text-xs">Cancel</button>
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
                        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.22em] text-label">Folders</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($folders as $folder)
                                <div class="group rounded-2xl border border-cream-200 bg-cream-50 transition hover:border-brand-200 hover:bg-cream-100">
                                    @if ($renameFolderId === $folder->id)
                                        <form wire:submit="saveRename({{ $folder->id }})" class="flex items-center gap-2 px-3 py-2.5">
                                            <input type="text" wire:model="renameFolderName" autofocus class="flex-1 rounded-lg border border-cream-300 bg-surface px-3 py-1.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                                            <button type="submit" class="btn btn-primary px-3 py-1.5 text-xs">Save</button>
                                            <button type="button" wire:click="cancelRename" class="btn btn-ghost px-3 py-1.5 text-xs">Cancel</button>
                                        </form>
                                    @else
                                        <div class="flex items-center justify-between gap-2 px-3 py-2.5">
                                            <button type="button" wire:click="openFolder({{ $folder->id }})" class="flex min-w-0 flex-1 items-center gap-2 text-left">
                                                <svg class="size-5 shrink-0 text-gold-500" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z"/></svg>
                                                <span class="min-w-0">
                                                    <span class="truncate block font-semibold text-ink-900 group-hover:text-heading">{{ $folder->name }}</span>
                                                    <span class="block text-xs text-ink-500">{{ $this->totalChildCount($folder) }} item(s)</span>
                                                </span>
                                            </button>
                                            @if ($confirmDeleteFolderId === $folder->id)
                                                <div class="flex items-center gap-1">
                                                    <button type="button" wire:click="deleteFolder({{ $folder->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-2 py-1 text-[10px] font-bold text-white">Delete</button>
                                                    <button type="button" wire:click="cancelDeleteFolder" class="btn btn-ghost px-2 py-1 text-[10px]">Cancel</button>
                                                </div>
                                            @else
                                                <x-admin.actions-menu>
                                                    <button type="button" wire:click="openFolder({{ $folder->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-heading">
                                                        <svg class="size-4 text-heading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                        Open folder
                                                    </button>
                                                    <button type="button" wire:click="startRenameFolder({{ $folder->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-heading">
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
                        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.22em] text-label">Files</p>
                        <ul class="mt-2 divide-y divide-cream-200 rounded-2xl border border-cream-200 bg-surface">
                            @foreach ($docs as $doc)
                                @php
                                    $url = $doc->path ? \App\Http\Controllers\Admin\DocumentDownloadController::beneficiaryDocumentUrl($doc) : null;
                                    $downloadUrl = $doc->path ? \App\Http\Controllers\Admin\DocumentDownloadController::beneficiaryDocumentUrl($doc, download: true) : null;
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
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-8 place-items-center rounded-full text-ink-500 transition hover:bg-cream-100 hover:text-heading" title="Open file">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7v7M21 3l-9 9M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                                            </a>
                                            <a href="{{ $downloadUrl }}" class="grid size-8 place-items-center rounded-full text-ink-500 transition hover:bg-cream-100 hover:text-heading" title="Download">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5 5 5-5M12 4v12"/></svg>
                                            </a>
                                        @endif
                                        @if ($confirmDeleteDocumentId === $doc->id)
                                            <button type="button" wire:click="deleteDocument({{ $doc->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-2 py-1 text-[10px] font-bold text-white">Delete</button>
                                            <button type="button" wire:click="cancelDeleteDocument" class="btn btn-ghost px-2 py-1 text-[10px]">Cancel</button>
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
            <section class="rounded-3xl border border-cream-300 bg-surface p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                <div class="flex items-center justify-between">
                    <h3 class="font-serif text-lg font-bold text-ink-900">Case timeline</h3>
                    <button type="button" wire:click="startTimelineEntry" class="btn btn-ghost gap-1 px-3 py-1.5 text-xs">+ Add entry</button>
                </div>

                @if ($showTimelineForm)
                    <form wire:submit="saveTimelineEntry" class="mt-4 space-y-2 rounded-2xl border border-dashed border-cream-300 bg-cream-50 p-4">
                        <select wire:model="timelineType" class="block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                            @foreach ($timelineTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea wire:model="timelineDescription" rows="3" placeholder="What happened? (optional)" class="block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary px-3 py-1.5 text-xs">Add entry</button>
                            <button type="button" wire:click="cancelTimelineEntry" class="btn btn-ghost px-3 py-1.5 text-xs">Cancel</button>
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
