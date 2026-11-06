<?php

use App\Enums\NewsletterCampaignStatus;
use App\Jobs\SendNewsletterCampaign;
use App\Jobs\SendNewsletterCampaignEmail;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $subject = '';

    public string $body = '';

    public ?string $previewHtml = null;

    public ?int $confirmSendId = null;

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'view'), 403);
    }

    public function startCreate(): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'create'), 403);

        $this->cancel();
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'update'), 403);

        $campaign = $this->draft($id);
        $this->cancel();
        $this->editingId = $campaign->id;
        $this->subject = $campaign->subject;
        $this->body = $campaign->body;
        $this->showForm = true;
    }

    /** Start a new draft from an earlier newsletter. */
    public function duplicate(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'create'), 403);

        $campaign = NewsletterCampaign::query()->findOrFail($id);
        $this->cancel();
        $this->subject = $campaign->subject;
        $this->body = $campaign->body;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset(['showForm', 'editingId', 'subject', 'body', 'previewHtml', 'confirmSendId', 'confirmDeleteId']);
        $this->resetErrorBag();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', $this->editingId ? 'update' : 'create'), 403);

        $data = $this->validate($this->campaignRules());

        if ($this->editingId) {
            $this->draft($this->editingId)->update($data);
        } else {
            NewsletterCampaign::query()->create($data + ['created_by' => auth()->id()]);
        }

        $this->cancel();
        $this->flashMessage = 'Draft saved.';
    }

    public function preview(int $id): void
    {
        $this->previewHtml = (new NewsletterCampaignMail(NewsletterCampaign::query()->findOrFail($id)))->render();
    }

    public function sendTest(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'send'), 403);

        $email = auth()->user()->email;
        Mail::to($email)->send(new NewsletterCampaignMail($this->draft($id), isTest: true));
        $this->flashMessage = "Test sent to {$email}.";
    }

    public function askSend(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'send'), 403);

        $this->confirmSendId = $this->draft($id)->id;
    }

    public function send(): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'send'), 403);

        if ($this->activeSubscriberCount === 0) {
            $this->flashMessage = 'There are no active subscribers to send to.';
            $this->confirmSendId = null;

            return;
        }

        // The status check inside the update means a double click can only ever start one send.
        $started = NewsletterCampaign::query()->whereKey($this->confirmSendId)
            ->where('status', NewsletterCampaignStatus::Draft->value)
            ->update(['status' => NewsletterCampaignStatus::Sending->value, 'sent_by' => auth()->id(), 'started_at' => now()]);

        if ($started) {
            SendNewsletterCampaign::dispatch(NewsletterCampaign::query()->findOrFail($this->confirmSendId));
            $this->flashMessage = 'Sending has started. Progress shows in the list below.';
        }

        $this->confirmSendId = null;
    }

    public function retryFailed(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'send'), 403);

        $campaign = NewsletterCampaign::query()->findOrFail($id);
        $failedIds = $campaign->recipients()->where('status', NewsletterCampaignRecipient::STATUS_FAILED)->pluck('id');

        $campaign->recipients()->whereIn('id', $failedIds)->update(['status' => NewsletterCampaignRecipient::STATUS_PENDING]);
        $campaign->update(['status' => NewsletterCampaignStatus::Sending, 'finished_at' => null]);

        $failedIds->each(fn (int $recipientId) => SendNewsletterCampaignEmail::dispatch($recipientId));

        $this->flashMessage = "Retrying {$failedIds->count()} email(s).";
    }

    /**
     * Restart a campaign whose sending job stopped. Each email checks its recipient's
     * status before sending, so nobody who already received it gets it again.
     */
    public function resume(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'send'), 403);

        $campaign = NewsletterCampaign::query()->findOrFail($id);

        if (! $campaign->looksStalled()) {
            return;
        }

        SendNewsletterCampaign::dispatch($campaign);

        $this->flashMessage = 'Sending restarted.';
    }

    public function askDelete(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'delete'), 403);

        $this->confirmDeleteId = $this->draft($id)->id;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->canDo('newsletter', 'delete'), 403);

        $this->draft($id)->delete();
        $this->confirmDeleteId = null;
        $this->flashMessage = 'Draft deleted.';
    }

    /** @return array<string, array<int, string>> */
    private function campaignRules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'min:10', 'max:20000'],
        ];
    }

    /** Sent newsletters are a record of what went out, so only drafts can change. */
    private function draft(int $id): NewsletterCampaign
    {
        return NewsletterCampaign::query()->where('status', NewsletterCampaignStatus::Draft->value)->findOrFail($id);
    }

    #[Computed]
    public function activeSubscriberCount(): int
    {
        return NewsletterSubscriber::query()->whereNull('unsubscribed_at')->count();
    }

    #[Computed]
    public function campaigns()
    {
        return NewsletterCampaign::query()
            ->withCount([
                'recipients',
                'recipients as sent_count' => fn ($q) => $q->where('status', NewsletterCampaignRecipient::STATUS_SENT),
                'recipients as failed_count' => fn ($q) => $q->where('status', NewsletterCampaignRecipient::STATUS_FAILED),
            ])
            ->latest('id')
            ->paginate(15);
    }
}; ?>

@php
    $menuItem = 'flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-heading';
    $hasSending = $this->campaigns->contains(fn ($campaign) => $campaign->status === \App\Enums\NewsletterCampaignStatus::Sending);
@endphp

<div class="space-y-6" @if ($hasSending) wire:poll.10s @endif>
    <x-admin.section-header title="Newsletters" :subtitle="$this->activeSubscriberCount.' active subscriber(s)'">
        <x-slot:actions>
            <a href="{{ route('admin.newsletter.index') }}" class="btn btn-ghost px-4 py-2 text-xs" wire:navigate>Subscribers</a>
            @if (auth()->user()?->canDo('newsletter', 'create'))
                <button type="button" wire:click="startCreate" class="btn btn-primary gap-2 px-4 py-2 text-xs">Write newsletter</button>
            @endif
        </x-slot:actions>
    </x-admin.section-header>

    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700" aria-label="Dismiss">×</button>
        </div>
    @endif

    <x-admin.modal :show="$showForm" :title="$editingId ? 'Edit newsletter' : 'Write newsletter'" size="xl" onClose="cancel">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label for="nl_subject" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Subject</label>
                <input id="nl_subject" type="text" wire:model="subject" maxlength="150" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="nl_body" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Message</label>
                <textarea id="nl_body" wire:model="body" rows="12" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                <p class="mt-1 text-xs text-ink-500">Leave a blank line between paragraphs. Formatting: **bold**, *italic*, # heading, - list item, [link text](https://example.org), ![photo description](https://example.org/photo.jpg).</p>
                @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled" class="btn btn-primary px-6 py-3 text-sm disabled:opacity-60">Save draft</button>
                <button type="button" wire:click="cancel" class="btn btn-ghost px-6 py-3 text-sm">Cancel</button>
            </div>
        </form>
    </x-admin.modal>

    <x-admin.modal :show="$previewHtml !== null" title="Preview" size="xl" onClose="cancel">
        <iframe title="Newsletter preview" sandbox srcdoc="{{ $previewHtml }}" class="h-[60vh] w-full rounded-lg border border-cream-300 bg-white"></iframe>
    </x-admin.modal>

    <x-admin.modal :show="$confirmSendId !== null" title="Send this newsletter?" :subtitle="'It goes to '.$this->activeSubscriberCount.' active subscriber(s). This can\'t be undone.'" size="sm" onClose="cancel">
        <div class="flex flex-wrap gap-3">
            <button type="button" wire:click="send" wire:loading.attr="disabled" class="btn btn-primary px-6 py-3 text-sm disabled:opacity-60">Send to everyone</button>
            <button type="button" wire:click="cancel" class="btn btn-ghost px-6 py-3 text-sm">Cancel</button>
        </div>
    </x-admin.modal>

    <div class="overflow-x-auto rounded-3xl border border-cream-300 bg-surface shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->campaigns->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">No newsletters yet. Write your first one to get started.</div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr>
                        <th class="px-6 py-3">Subject</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Delivered</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200">
                    @foreach ($this->campaigns as $campaign)
                        @php $isDraft = $campaign->status === \App\Enums\NewsletterCampaignStatus::Draft; @endphp
                        <tr class="transition hover:bg-cream-100/40" wire:key="campaign-{{ $campaign->id }}">
                            <td class="px-6 py-3 font-semibold text-ink-900">{{ $campaign->subject }}</td>
                            <td class="px-6 py-3"><x-admin.status-pill :palette="$campaign->status->palette()" :label="$campaign->status->label()" /></td>
                            <td class="px-6 py-3 text-xs text-ink-500">
                                @if ($isDraft)
                                    —
                                @else
                                    {{ $campaign->sent_count }} of {{ $campaign->recipients_count }}
                                    @if ($campaign->failed_count > 0)
                                        <span class="font-semibold text-red-600">· {{ $campaign->failed_count }} failed</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-3 text-xs text-ink-500">{{ ($campaign->started_at ?? $campaign->updated_at)->format('M j, Y') }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end">
                                    @if ($confirmDeleteId === $campaign->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="delete({{ $campaign->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                            <button type="button" wire:click="cancel" class="btn btn-ghost px-3 py-1.5 text-xs">Cancel</button>
                                        </div>
                                    @else
                                        <x-admin.actions-menu>
                                            <button type="button" wire:click="preview({{ $campaign->id }})" @click="open = false" class="{{ $menuItem }}">Preview</button>
                                            @if ($isDraft)
                                                @if (auth()->user()?->canDo('newsletter', 'update'))
                                                    <button type="button" wire:click="startEdit({{ $campaign->id }})" @click="open = false" class="{{ $menuItem }}">Edit</button>
                                                @endif
                                                @if (auth()->user()?->canDo('newsletter', 'send'))
                                                    <button type="button" wire:click="sendTest({{ $campaign->id }})" @click="open = false" class="{{ $menuItem }}">Send me a test</button>
                                                    <button type="button" wire:click="askSend({{ $campaign->id }})" @click="open = false" class="{{ $menuItem }}">Send to subscribers</button>
                                                @endif
                                                @if (auth()->user()?->canDo('newsletter', 'delete'))
                                                    <button type="button" wire:click="askDelete({{ $campaign->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">Delete</button>
                                                @endif
                                            @else
                                                @if ($campaign->looksStalled() && auth()->user()?->canDo('newsletter', 'send'))
                                                    <button type="button" wire:click="resume({{ $campaign->id }})" @click="open = false" class="block w-full px-4 py-2 text-left text-sm text-ink-700 hover:bg-cream-200">Resume sending</button>
                                                @endif
                                                @if ($campaign->failed_count > 0 && auth()->user()?->canDo('newsletter', 'send'))
                                                    <button type="button" wire:click="retryFailed({{ $campaign->id }})" @click="open = false" class="{{ $menuItem }}">Retry failed</button>
                                                @endif
                                                @if (auth()->user()?->canDo('newsletter', 'create'))
                                                    <button type="button" wire:click="duplicate({{ $campaign->id }})" @click="open = false" class="{{ $menuItem }}">Use as a new draft</button>
                                                @endif
                                            @endif
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

    <div>{{ $this->campaigns->links() }}</div>
</div>
