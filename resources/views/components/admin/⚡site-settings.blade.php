<?php

use App\Services\ImpactMetricService;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    private const HERO_SLOTS = [
        'hero_image_main' => 'Main photo',
        'hero_image_left' => 'Small photo, left',
        'hero_image_right' => 'Small photo, right',
    ];

    /** @var array<string, string|int> */
    public array $form = [];

    /** @var array<string, mixed> */
    public array $heroUploads = [];

    public ?string $flashMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canDo('site_settings', 'view'), 403);

        $this->form = SiteSettings::all();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canDo('site_settings', 'update'), 403);

        $socialRule = ['nullable', 'string', 'max:255', 'url:https'];

        $this->validate([
            'form.summary' => ['required', 'string', 'max:400'],
            'form.contact_email' => ['required', 'email', 'max:255'],
            'form.media_email' => ['required', 'email', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:40'],
            'form.phone_hours' => ['nullable', 'string', 'max:120'],
            'form.mobile_money_number' => ['nullable', 'string', 'max:40'],
            'form.instagram_url' => $socialRule,
            'form.facebook_url' => $socialRule,
            'form.youtube_url' => $socialRule,
            'form.hero_text' => ['required', 'string', 'max:300'],
            'form.baseline_children' => ['required', 'integer', 'min:0', 'max:10000000'],
            'form.baseline_communities' => ['required', 'integer', 'min:0', 'max:100000'],
            'form.baseline_countries' => ['required', 'integer', 'min:0', 'max:300'],
            'form.baseline_volunteer_hours' => ['required', 'integer', 'min:0', 'max:100000000'],
            'heroUploads.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // Image paths only ever come from the saved settings or a new upload, never the client.
        $saved = SiteSettings::all();
        $values = array_merge($this->form, array_intersect_key($saved, self::HERO_SLOTS));
        $replaced = [];

        foreach (array_keys(self::HERO_SLOTS) as $slot) {
            if (isset($this->heroUploads[$slot])) {
                $replaced[] = $saved[$slot];
                $values[$slot] = $this->heroUploads[$slot]->store('site', 'public');
            }
        }

        SiteSettings::save($values);

        foreach ($replaced as $oldPath) {
            if (str_starts_with((string) $oldPath, 'site/')) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $this->form = SiteSettings::all();
        $this->heroUploads = [];
        unset($this->impact);
        $this->flashMessage = 'Settings saved.';
    }

    public function resetHeroImage(string $slot): void
    {
        abort_unless(auth()->user()?->canDo('site_settings', 'update') && array_key_exists($slot, self::HERO_SLOTS), 403);

        $saved = SiteSettings::all();
        $oldPath = (string) $saved[$slot];
        $saved[$slot] = SiteSettings::defaults()[$slot];
        SiteSettings::save($saved);

        if (str_starts_with($oldPath, 'site/')) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->form = SiteSettings::all();
        $this->flashMessage = 'Photo reset to the default.';
    }

    /** @return array<string, string> */
    public function heroSlots(): array
    {
        return self::HERO_SLOTS;
    }

    /** @return array{children: int, communities: int, countries: int, volunteer_hours: int} */
    #[Computed]
    public function impact(): array
    {
        return app(ImpactMetricService::class)->publicStats();
    }
}; ?>

@php
    $fieldClass = 'mt-2 block w-full rounded-lg border border-cream-300 bg-surface px-3 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30';
    $labelClass = 'block text-xs font-semibold uppercase tracking-[0.22em] text-label';
    $cardClass = 'space-y-5 rounded-3xl border border-cream-300 bg-surface p-5 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)] sm:p-7';
    $canUpdate = auth()->user()?->canDo('site_settings', 'update');
@endphp

<form wire:submit="save" class="space-y-6">
    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700" aria-label="Dismiss">×</button>
        </div>
    @endif

    <fieldset class="{{ $cardClass }}" @disabled(! $canUpdate)>
        <legend class="sr-only">Organisation details</legend>
        <h2 class="font-serif text-xl text-heading">Organisation details</h2>
        <div>
            <label for="set_summary" class="{{ $labelClass }}">Footer summary</label>
            <textarea id="set_summary" wire:model="form.summary" rows="3" class="{{ $fieldClass }}"></textarea>
            @error('form.summary') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ([['contact_email', 'General email', 'email'], ['media_email', 'Press and media email', 'email'], ['phone', 'Phone', 'text'], ['phone_hours', 'Phone hours', 'text'], ['mobile_money_number', 'Mobile Money number', 'text']] as [$field, $label, $type])
                <div>
                    <label for="set_{{ $field }}" class="{{ $labelClass }}">{{ $label }}</label>
                    <input id="set_{{ $field }}" type="{{ $type }}" wire:model="form.{{ $field }}" class="{{ $fieldClass }}">
                    @error("form.$field") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        <p class="text-sm text-ink-500">Leave the Mobile Money number empty to hide that option from the donate page.</p>
    </fieldset>

    <fieldset class="{{ $cardClass }}" @disabled(! $canUpdate)>
        <legend class="sr-only">Social links</legend>
        <h2 class="font-serif text-xl text-heading">Social links</h2>
        <p class="text-sm text-ink-500">Leave a link empty to hide that icon from the footer.</p>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([['instagram_url', 'Instagram'], ['facebook_url', 'Facebook'], ['youtube_url', 'YouTube']] as [$field, $label])
                <div>
                    <label for="set_{{ $field }}" class="{{ $labelClass }}">{{ $label }}</label>
                    <input id="set_{{ $field }}" type="url" wire:model="form.{{ $field }}" placeholder="https://" class="{{ $fieldClass }}">
                    @error("form.$field") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    </fieldset>

    <fieldset class="{{ $cardClass }}" @disabled(! $canUpdate)>
        <legend class="sr-only">Home page hero</legend>
        <h2 class="font-serif text-xl text-heading">Home page hero</h2>
        <div>
            <label for="set_hero_text" class="{{ $labelClass }}">Intro text</label>
            <textarea id="set_hero_text" wire:model="form.hero_text" rows="3" class="{{ $fieldClass }}"></textarea>
            @error('form.hero_text') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-3">
            @foreach ($this->heroSlots() as $slot => $label)
                <div>
                    <p class="{{ $labelClass }}">{{ $label }}</p>
                    <img src="{{ isset($heroUploads[$slot]) ? $heroUploads[$slot]->temporaryUrl() : \App\Support\MediaUrl::for($form[$slot]) }}" alt="" class="mt-2 aspect-[4/3] w-full rounded-xl object-cover">
                    <input type="file" wire:model="heroUploads.{{ $slot }}" accept="image/jpeg,image/png,image/webp" aria-label="Upload {{ $label }}" class="mt-2 block w-full text-xs">
                    @error("heroUploads.$slot") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if (str_starts_with((string) $form[$slot], 'site/'))
                        <button type="button" wire:click="resetHeroImage('{{ $slot }}')" class="mt-2 text-xs font-semibold text-label underline">Use default photo</button>
                    @endif
                </div>
            @endforeach
        </div>
    </fieldset>

    <fieldset class="{{ $cardClass }}" @disabled(! $canUpdate)>
        <legend class="sr-only">Impact numbers</legend>
        <h2 class="font-serif text-xl text-heading">Impact numbers</h2>
        <p class="text-sm text-ink-500">Starting figures cover work done before this system existed. New beneficiary records and logged volunteer hours are counted automatically.</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['baseline_children', 'Children supported', $this->impact['children'], 'New records are added to this.'],
                ['baseline_communities', 'Communities', $this->impact['communities'], 'Shows whichever is higher: this or the regions on record.'],
                ['baseline_countries', 'Countries', $this->impact['countries'], 'Shows whichever is higher: this or the countries on record.'],
                ['baseline_volunteer_hours', 'Volunteer hours', $this->impact['volunteer_hours'], 'Logged hours are added to this. Hidden while the total is 0.'],
            ] as [$field, $label, $live, $hint])
                <div>
                    <label for="set_{{ $field }}" class="{{ $labelClass }}">{{ $label }}</label>
                    <input id="set_{{ $field }}" type="number" min="0" wire:model="form.{{ $field }}" class="{{ $fieldClass }}">
                    <p class="mt-1 text-xs text-ink-500">{{ $hint }} Now showing: <strong>{{ number_format($live) }}</strong></p>
                    @error("form.$field") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    </fieldset>

    @if ($canUpdate)
        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary px-6 py-3 text-sm disabled:opacity-60">Save settings</button>
    @endif
</form>
