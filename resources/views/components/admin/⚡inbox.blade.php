<?php

use App\Enums\ContactMessageStatus;
use App\Mail\ContactMessageReply as ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $openMessageId = null;

    public string $statusFilter = '';

    public string $search = '';

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public bool $showReplyForm = false;

    #[Validate('required|string|max:200')]
    public string $replySubject = '';

    #[Validate('required|string|min:5|max:5000')]
    public string $replyBody = '';

    public function mount(): void
    {
        // Default to "New" so admins land on unread messages.
        $this->statusFilter = ContactMessageStatus::New->value;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->openMessageId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openMessage(int $id): void
    {
        $msg = ContactMessage::query()->findOrFail($id);
        $this->openMessageId = $msg->id;

        if ($msg->status === ContactMessageStatus::New) {
            $msg->update([
                'status' => ContactMessageStatus::InProgress->value,
                'handled_by' => auth()->id(),
            ]);
        }

        $this->showReplyForm = false;
        $this->replyBody = '';
        $this->replySubject = $this->defaultReplySubject($msg);
    }

    public function closeMessage(): void
    {
        $this->openMessageId = null;
        $this->showReplyForm = false;
    }

    public function startReply(): void
    {
        if (! auth()->user()?->canDo('inbox', 'reply')) {
            $this->addError('reply', 'You do not have permission to reply to messages.');

            return;
        }

        $this->showReplyForm = true;
        $msg = $this->message;
        if ($msg && filled($this->replySubject) === false) {
            $this->replySubject = $this->defaultReplySubject($msg);
        }
    }

    public function cancelReply(): void
    {
        $this->showReplyForm = false;
        $this->replyBody = '';
        $this->resetErrorBag();
    }

    public function sendReply(): void
    {
        if (! auth()->user()?->canDo('inbox', 'reply')) {
            $this->addError('reply', 'You do not have permission to reply to messages.');

            return;
        }

        $data = $this->validate();
        $msg = $this->message;
        if (! $msg) {
            return;
        }

        Mail::send(new ContactMessageReplyMail(
            recipientName: $msg->full_name,
            recipientEmail: $msg->email,
            subjectLine: $data['replySubject'],
            bodyText: $data['replyBody'],
            originalMessage: $msg->message,
        ));

        ContactMessageReply::query()->create([
            'contact_message_id' => $msg->id,
            'replied_by' => auth()->id(),
            'to_email' => $msg->email,
            'subject' => $data['replySubject'],
            'body' => $data['replyBody'],
            'sent_at' => now(),
        ]);

        $msg->update([
            'status' => ContactMessageStatus::Resolved->value,
            'handled_by' => auth()->id(),
        ]);

        $this->showReplyForm = false;
        $this->replyBody = '';
        $this->flashMessage = 'Reply sent to '.$msg->email.'.';
    }

    public function markResolved(int $id): void
    {
        $msg = ContactMessage::query()->findOrFail($id);
        $msg->update([
            'status' => ContactMessageStatus::Resolved->value,
            'handled_by' => auth()->id(),
        ]);
        $this->flashMessage = 'Message marked resolved.';
    }

    public function markNew(int $id): void
    {
        $msg = ContactMessage::query()->findOrFail($id);
        $msg->update(['status' => ContactMessageStatus::New->value]);
        $this->flashMessage = 'Message moved back to New.';
    }

    public function archive(int $id): void
    {
        $msg = ContactMessage::query()->findOrFail($id);
        $msg->update(['status' => ContactMessageStatus::Archived->value]);
        $this->flashMessage = 'Message archived.';
        if ($this->openMessageId === $id) {
            $this->openMessageId = null;
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
        if (! auth()->user()?->canDo('inbox', 'delete')) {
            $this->addError('delete', 'You do not have permission to delete messages.');

            return;
        }

        ContactMessage::query()->whereKey($id)->delete();
        $this->confirmDeleteId = null;
        if ($this->openMessageId === $id) {
            $this->openMessageId = null;
        }
        $this->flashMessage = 'Message deleted.';
    }

    protected function defaultReplySubject(ContactMessage $msg): string
    {
        $base = $msg->subject?->label() ?? 'your message';

        return 'Re: '.$base;
    }

    #[Computed]
    public function message(): ?ContactMessage
    {
        return $this->openMessageId
            ? ContactMessage::query()->with('replies.repliedBy', 'handler')->find($this->openMessageId)
            : null;
    }

    #[Computed]
    public function messages()
    {
        $q = ContactMessage::query()->withCount('replies');

        if (filled($this->statusFilter)) {
            $q->where('status', $this->statusFilter);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $q->where(function ($w) use ($needle) {
                $w->where('full_name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('message', 'like', $needle);
            });
        }

        return $q->latest()->paginate(20);
    }

    #[Computed]
    public function counts(): array
    {
        $base = ContactMessage::query();

        return [
            'new' => (clone $base)->where('status', ContactMessageStatus::New->value)->count(),
            'in_progress' => (clone $base)->where('status', ContactMessageStatus::InProgress->value)->count(),
            'resolved' => (clone $base)->where('status', ContactMessageStatus::Resolved->value)->count(),
            'archived' => (clone $base)->where('status', ContactMessageStatus::Archived->value)->count(),
            'all' => $base->count(),
        ];
    }
}; ?>

@php
    $tabs = [
        ['',                                   'All',          $this->counts['all']],
        [ContactMessageStatus::New->value,        'New',          $this->counts['new']],
        [ContactMessageStatus::InProgress->value, 'In progress',  $this->counts['in_progress']],
        [ContactMessageStatus::Resolved->value,   'Resolved',     $this->counts['resolved']],
        [ContactMessageStatus::Archived->value,   'Archived',     $this->counts['archived']],
    ];
    $canReply = auth()->user()?->canDo('inbox', 'reply') ?? false;
    $canDelete = auth()->user()?->canDo('inbox', 'delete') ?? false;
@endphp

<div class="space-y-4">
    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700">×</button>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex flex-wrap gap-1 border-b border-cream-200">
        @foreach ($tabs as [$value, $label, $count])
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $value }}')"
                @class([
                    'font-sans -mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] transition',
                    'border-brand-900 text-brand-900' => $statusFilter === $value,
                    'border-transparent text-ink-500 hover:text-ink-900' => $statusFilter !== $value,
                ])
            >
                {{ $label }}
                @if ($count > 0)
                    <span @class([
                        'inline-flex min-w-[1.25rem] items-center justify-center rounded-md px-1.5 py-0.5 text-[10px] font-bold transition',
                        'bg-brand-900 text-white' => $statusFilter === $value,
                        'bg-cream-200 text-ink-500' => $statusFilter !== $value,
                    ])>{{ $count }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-[2fr_3fr]">
        {{-- Message list --}}
        <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <div class="border-b border-cream-200 p-3">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email, message…" class="w-full rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            </div>
            @if ($this->messages->isEmpty())
                <div class="p-10 text-center text-sm text-ink-500">No messages in this view.</div>
            @else
                <ul class="max-h-[70vh] divide-y divide-cream-200 overflow-y-auto">
                    @foreach ($this->messages as $msg)
                        <li>
                            <button
                                type="button"
                                wire:click="openMessage({{ $msg->id }})"
                                @class([
                                    'flex w-full flex-col gap-1 px-4 py-3 text-left transition',
                                    'bg-cream-100/80' => $openMessageId === $msg->id,
                                    'hover:bg-cream-100/40' => $openMessageId !== $msg->id,
                                ])
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <p @class([
                                        'truncate font-semibold text-ink-900',
                                        'text-brand-900' => $msg->status === \App\Enums\ContactMessageStatus::New,
                                    ])>{{ $msg->full_name }}</p>
                                    <span class="shrink-0 text-[10px] uppercase tracking-[0.18em] text-ink-500">{{ $msg->created_at?->diffForHumans(short: true) }}</span>
                                </div>
                                <p class="truncate text-xs text-ink-500">{{ $msg->subject?->label() }} · {{ $msg->email }}</p>
                                <p class="truncate text-xs text-ink-700">{{ \Illuminate\Support\Str::limit($msg->message, 80) }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <x-admin.status-pill :palette="$msg->status->palette()" :label="$msg->status->label()" />
                                    @if ($msg->replies_count > 0)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">
                                            <svg class="size-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h11c.55 0 1-.45 1-1z"/></svg>
                                            {{ $msg->replies_count }}
                                        </span>
                                    @endif
                                </div>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-cream-200 px-4 py-2">{{ $this->messages->links() }}</div>
            @endif
        </div>

        {{-- Reader pane --}}
        <div class="rounded-3xl border border-cream-300 bg-white p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            @if ($msg = $this->message)
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.22em] text-brand-700">
                            {{ $msg->subject?->label() }} · received {{ $msg->created_at?->format('M j, Y · g:i A') }}
                        </p>
                        <h2 class="mt-1 font-serif text-xl font-bold text-ink-900">{{ $msg->full_name }}</h2>
                        <p class="text-xs text-ink-500">{{ $msg->email }}@if ($msg->phone) · {{ $msg->phone }} @endif</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <x-admin.status-pill :palette="$msg->status->palette()" :label="$msg->status->label()" />
                            @if ($msg->handler)
                                <span class="text-xs text-ink-500">Handled by {{ $msg->handler->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="mailto:{{ $msg->email }}" class="grid size-9 place-items-center rounded-full border border-cream-300 text-ink-500 transition hover:border-brand-900 hover:text-brand-900" title="Open in mail client">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7v7M21 3l-9 9M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
                        </a>
                        <x-admin.actions-menu>
                            @if ($msg->status !== \App\Enums\ContactMessageStatus::Resolved)
                                <button type="button" wire:click="markResolved({{ $msg->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                    <svg class="size-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                    Mark resolved
                                </button>
                            @endif
                            @if ($msg->status !== \App\Enums\ContactMessageStatus::New)
                                <button type="button" wire:click="markNew({{ $msg->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                    <svg class="size-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                                    Mark as new
                                </button>
                            @endif
                            @if ($msg->status !== \App\Enums\ContactMessageStatus::Archived)
                                <button type="button" wire:click="archive({{ $msg->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                    <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8h18M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8M9 12h6"/></svg>
                                    Archive
                                </button>
                            @endif
                            @if ($canDelete)
                                <button type="button" wire:click="askDelete({{ $msg->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                    Delete
                                </button>
                            @endif
                        </x-admin.actions-menu>
                    </div>
                </div>

                <div class="mt-5 whitespace-pre-line rounded-2xl bg-cream-50 px-5 py-4 text-sm leading-relaxed text-ink-900">{{ $msg->message }}</div>

                @if ($msg->replies->count() > 0)
                    <div class="mt-5 space-y-3">
                        <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.22em] text-brand-700">Replies sent</p>
                        @foreach ($msg->replies as $reply)
                            <article class="rounded-2xl border border-cream-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-2 text-xs text-ink-500">
                                    <span><strong class="text-ink-900">{{ $reply->repliedBy?->name ?? 'Someone' }}</strong> → {{ $reply->to_email }}</span>
                                    <span>{{ $reply->sent_at?->format('M j, Y · g:i A') }}</span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-ink-900">{{ $reply->subject }}</p>
                                <div class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $reply->body }}</div>
                            </article>
                        @endforeach
                    </div>
                @endif

                @if ($confirmDeleteId === $msg->id)
                    <div class="mt-5 flex flex-wrap items-center gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm">
                        <span class="text-red-700">Permanently delete this message and all its replies?</span>
                        <button type="button" wire:click="delete({{ $msg->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Yes, delete</button>
                        <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700">Cancel</button>
                    </div>
                @endif

                @if ($canReply)
                    @if (! $showReplyForm)
                        <div class="mt-6 border-t border-cream-200 pt-4">
                            <button type="button" wire:click="startReply" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17l-5-5 5-5M4 12h11a5 5 0 0 1 5 5v3"/></svg>
                                Reply to {{ $msg->full_name }}
                            </button>
                        </div>
                    @else
                        <form wire:submit="sendReply" class="mt-6 space-y-3 border-t border-cream-200 pt-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Subject</label>
                                <input type="text" wire:model="replySubject" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                                @error('replySubject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Your reply to {{ $msg->email }}</label>
                                <textarea wire:model="replyBody" rows="6" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm leading-relaxed focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                                @error('replyBody') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60">
                                    <span wire:loading.remove>Send reply</span>
                                    <span wire:loading>Sending…</span>
                                </button>
                                <button type="button" wire:click="cancelReply" class="inline-flex items-center rounded-xl border border-cream-300 px-4 py-2 text-xs font-semibold text-ink-700">Cancel</button>
                                <p class="text-xs text-ink-500">Marked resolved automatically once sent.</p>
                            </div>
                        </form>
                    @endif
                @else
                    <p class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                        You don't have permission to reply from here. Use the external mail link instead.
                    </p>
                @endif
            @else
                <div class="grid place-items-center py-20 text-center">
                    <svg class="size-12 text-ink-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8.5m18 0L12 14 3 8.5m18 0V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2.5"/></svg>
                    <p class="mt-3 text-sm font-semibold text-ink-700">Select a message to read</p>
                    <p class="mt-1 max-w-xs text-xs text-ink-500">Messages from the public contact form land here. Click any thread on the left to read and reply.</p>
                </div>
            @endif
        </div>
    </div>
</div>
