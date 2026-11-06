<?php

use App\Models\Event;
use App\Support\SitePopup;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $enabled = false;

    public string $type = 'newsletter';

    public string $trigger = 'delay';

    public int $trigger_delay_seconds = 5;

    public int $trigger_scroll_percent = 40;

    public string $frequency = 'session';

    public string $pages = 'all';

    public ?int $event_id = null;

    public string $announcement_heading = '';

    public string $announcement_body = '';

    public string $announcement_cta_label = '';

    public string $announcement_cta_url = '';

    public ?string $flashMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canDo('site_popup', 'view'), 403);

        $this->fill(SitePopup::config());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'type' => ['required', Rule::in(array_keys(SitePopup::TYPES))],
            'trigger' => ['required', Rule::in(array_keys(SitePopup::TRIGGERS))],
            'trigger_delay_seconds' => ['required_if:trigger,delay', 'integer', 'between:0,120'],
            'trigger_scroll_percent' => ['required_if:trigger,scroll', 'integer', 'between:1,100'],
            'frequency' => ['required', Rule::in(array_keys(SitePopup::FREQUENCIES))],
            'pages' => ['required', Rule::in(array_keys(SitePopup::PAGES))],
            'event_id' => [
                'nullable',
                'required_if:type,event',
                Rule::when($this->type === 'event', [
                    Rule::in(Event::query()->published()->upcoming()->pluck('id')->all()),
                ]),
            ],
            'announcement_heading' => ['nullable', 'required_if:type,announcement', 'string', 'max:120'],
            'announcement_body' => ['nullable', 'string', 'max:600'],
            'announcement_cta_label' => ['nullable', 'required_with:announcement_cta_url', 'string', 'max:40'],
            // Absolute http(s) URL or a site-relative path — never javascript: or other schemes.
            'announcement_cta_url' => ['nullable', 'required_with:announcement_cta_label', 'string', 'max:255', 'regex:#^(https?://|/(?!/))#i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_id.required_if' => 'Pick the event to promote.',
            'event_id.in' => 'Pick a published event that has not started yet.',
            'announcement_heading.required_if' => 'An announcement needs a heading.',
            'announcement_cta_url.regex' => 'Use a full https:// link or a site path starting with /.',
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('site_popup', 'update'), 403);

        $data = $this->validate();

        if ($data['type'] !== 'event') {
            $data['event_id'] = null;
        }

        SitePopup::save($data);

        $this->flashMessage = $data['enabled'] ? 'Popup saved and live on the site.' : 'Popup saved (currently switched off).';
    }

    /**
     * @return \Illuminate\Support\Collection<int, Event>
     */
    #[Computed]
    public function upcomingEvents()
    {
        return Event::query()->published()->upcoming()->limit(50)->get(['id', 'title', 'starts_at']);
    }

    public function with(): array
    {
        return [
            'typeOptions' => SitePopup::TYPES,
            'triggerOptions' => SitePopup::TRIGGERS,
            'frequencyOptions' => SitePopup::FREQUENCIES,
            'pageOptions' => SitePopup::PAGES,
            'canUpdate' => auth()->user()?->canDo('site_popup', 'update') ?? false,
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

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-3xl border border-cream-300 bg-surface p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <label class="flex items-start justify-between gap-6">
                <span>
                    <span class="block font-serif text-xl font-bold text-ink-900">Show the popup</span>
                    <span class="mt-1 block text-sm text-ink-500">Master switch. When off, no popup appears anywhere on the public site.</span>
                </span>
                <input type="checkbox" wire:model.live="enabled" class="mt-1 size-5 rounded border-cream-300 text-gold-500 focus:ring-gold-500">
            </label>
        </section>

        <section class="rounded-3xl border border-cream-300 bg-surface p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <h2 class="font-serif text-xl font-bold text-ink-900">Content</h2>

            <fieldset class="mt-4">
                <legend class="text-xs font-semibold uppercase tracking-[0.22em] text-label">Type</legend>
                <div class="mt-3 grid gap-2 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($typeOptions as $value => $label)
                        <label class="cursor-pointer rounded-2xl border border-cream-300 px-4 py-3 text-sm font-semibold text-ink-700 transition hover:border-heading has-checked:border-gold-500 has-checked:bg-cream-100 has-checked:text-heading">
                            <input type="radio" wire:model.live="type" value="{{ $value }}" class="sr-only">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            @if ($type === 'event')
                <div class="mt-5">
                    <label for="popup-event" class="text-xs font-semibold uppercase tracking-[0.22em] text-label">Event to promote</label>
                    <select id="popup-event" wire:model="event_id" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        <option value="">Choose an upcoming event…</option>
                        @foreach ($this->upcomingEvents as $event)
                            <option value="{{ $event->id }}">{{ $event->title }} — {{ $event->starts_at?->format('M j, Y') }}</option>
                        @endforeach
                    </select>
                    @if ($this->upcomingEvents->isEmpty())
                        <p class="mt-1 text-xs text-ink-500">There are no published upcoming events yet.</p>
                    @endif
                    <p class="mt-1 text-xs text-ink-500">The popup hides itself automatically once the event has passed.</p>
                    @error('event_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($type === 'announcement')
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="popup-heading" class="text-xs font-semibold uppercase tracking-[0.22em] text-label">Heading</label>
                        <input id="popup-heading" type="text" wire:model="announcement_heading" maxlength="120" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @error('announcement_heading') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="popup-body" class="text-xs font-semibold uppercase tracking-[0.22em] text-label">Body</label>
                        <textarea id="popup-body" wire:model="announcement_body" rows="4" maxlength="600" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"></textarea>
                        @error('announcement_body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="popup-cta-label" class="text-xs font-semibold uppercase tracking-[0.22em] text-label">Button label <span class="normal-case tracking-normal text-ink-500">(optional)</span></label>
                        <input id="popup-cta-label" type="text" wire:model="announcement_cta_label" maxlength="40" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @error('announcement_cta_label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="popup-cta-url" class="text-xs font-semibold uppercase tracking-[0.22em] text-label">Button link</label>
                        <input id="popup-cta-url" type="text" wire:model="announcement_cta_url" placeholder="/events or https://…" class="mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @error('announcement_cta_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif
        </section>

        <section class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-cream-300 bg-surface p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                <h2 class="font-serif text-xl font-bold text-ink-900">Trigger</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($triggerOptions as $value => $label)
                        <label class="flex items-center gap-3 text-sm text-ink-700">
                            <input type="radio" wire:model.live="trigger" value="{{ $value }}" class="size-4 border-cream-300 text-gold-500 focus:ring-gold-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @if ($trigger === 'delay')
                    <label for="popup-delay" class="mt-4 block text-xs font-semibold uppercase tracking-[0.22em] text-label">Seconds after page load</label>
                    <input id="popup-delay" type="number" min="0" max="120" wire:model="trigger_delay_seconds" class="mt-2 block w-28 rounded-lg border border-cream-300 bg-surface px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('trigger_delay_seconds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @elseif ($trigger === 'scroll')
                    <label for="popup-scroll" class="mt-4 block text-xs font-semibold uppercase tracking-[0.22em] text-label">Percent of page scrolled</label>
                    <input id="popup-scroll" type="number" min="1" max="100" wire:model="trigger_scroll_percent" class="mt-2 block w-28 rounded-lg border border-cream-300 bg-surface px-3 py-2 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('trigger_scroll_percent') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @else
                    <p class="mt-4 text-xs text-ink-500">Opens when the visitor moves to leave the page (desktop only).</p>
                @endif
            </div>

            <div class="rounded-3xl border border-cream-300 bg-surface p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                <h2 class="font-serif text-xl font-bold text-ink-900">Frequency</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($frequencyOptions as $value => $label)
                        <label class="flex items-center gap-3 text-sm text-ink-700">
                            <input type="radio" wire:model="frequency" value="{{ $value }}" class="size-4 border-cream-300 text-gold-500 focus:ring-gold-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-ink-500">Saving changes resets this, so visitors see the updated popup.</p>
            </div>

            <div class="rounded-3xl border border-cream-300 bg-surface p-6 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                <h2 class="font-serif text-xl font-bold text-ink-900">Pages</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($pageOptions as $value => $label)
                        <label class="flex items-center gap-3 text-sm text-ink-700">
                            <input type="radio" wire:model="pages" value="{{ $value }}" class="size-4 border-cream-300 text-gold-500 focus:ring-gold-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($canUpdate)
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary px-6 py-3 text-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save popup</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        @endif
    </form>
</div>
