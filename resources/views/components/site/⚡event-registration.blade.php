<?php

use App\Concerns\DetectsSpamSubmissions;
use App\Mail\EventRegistrationConfirmation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\SiteAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    use DetectsSpamSubmissions;

    #[Locked]
    public int $eventId;

    #[Validate('required|string|max:120')]
    public string $full_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:40')]
    public string $phone = '';

    #[Validate('required|integer|min:1|max:10')]
    public int $party_size = 1;

    #[Validate('accepted')]
    public bool $consent = false;

    public bool $submitted = false;

    public function mount(Event $event): void
    {
        $this->eventId = $event->id;
    }

    public function submit(): void
    {
        if ($this->submitted) {
            return;
        }

        $key = 'public-form:event-registration:'.(request()->ip() ?? 'anon');

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('rate', __('Too many attempts. Please wait a minute and try again.'));

            return;
        }

        if ($this->isLikelySpam()) {
            RateLimiter::hit($key, 60);
            $this->submitted = true;

            return;
        }

        $data = $this->validate();
        RateLimiter::hit($key, 60);
        $email = Str::lower(trim($data['email']));

        // Locking the event means two people can't both take the last place.
        $outcome = DB::transaction(function () use ($data, $email): ?EventRegistration {
            $event = Event::query()->published()->lockForUpdate()->findOrFail($this->eventId);

            if (! $event->isOpenForRegistration() || $event->registrations()->where('email', $email)->exists()) {
                return null;
            }

            if ($event->capacity !== null && $event->seatsTaken() + $data['party_size'] > $event->capacity) {
                $this->addError('party_size', __('Sorry, there aren\'t enough places left for that many people.'));

                return null;
            }

            return $event->registrations()->create([
                'full_name' => $data['full_name'],
                'email' => $email,
                'phone' => $data['phone'] ?: null,
                'party_size' => $data['party_size'],
                'consented_at' => now(),
            ]);
        });

        if ($this->getErrorBag()->isNotEmpty()) {
            unset($this->placesLeft);

            return;
        }

        // Someone already signed up sees the same message, so the form doesn't reveal who is attending.
        if ($outcome) {
            Mail::to($outcome->email)->queue(new EventRegistrationConfirmation($outcome));
            SiteAnalytics::conversion('event_registration');
        }

        $this->submitted = true;
    }

    /** Null means there is no limit. */
    #[Computed]
    public function placesLeft(): ?int
    {
        $event = Event::query()->findOrFail($this->eventId);

        return $event->capacity === null ? null : max(0, $event->capacity - $event->seatsTaken());
    }
}; ?>

<div id="event-signup" class="rounded-2xl border border-brand-100 bg-surface p-6 shadow-sm">
    <p class="eyebrow">{{ __('Attend') }}</p>
    <h2 class="mt-2 font-serif text-xl text-heading">{{ __('Sign up for this event') }}</h2>

    @if ($submitted)
        <div role="status" class="mt-4 rounded-xl bg-brand-50 p-4 text-sm text-heading-soft">
            <p class="font-semibold text-heading">{{ __('Thank you. You\'re on the list.') }}</p>
            <p class="mt-1 text-ink-500">{{ __('We\'ve emailed you the details. If you signed up before, your place is still saved.') }}</p>
        </div>
    @elseif ($this->placesLeft === 0)
        <p class="mt-4 text-sm text-ink-500">{{ __('This event is full. Please contact us if you\'d still like to take part.') }}</p>
    @else
        @if ($this->placesLeft !== null)
            <p class="mt-1 text-xs text-ink-500">{{ trans_choice(':count place left|:count places left', $this->placesLeft) }}</p>
        @endif
        <form wire:submit="submit" class="mt-4 space-y-4">
            @include('components.site.partials.honeypot')
            @error('rate') <p role="alert" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p> @enderror
            <div>
                <label for="reg-name" class="block text-sm font-semibold text-heading">{{ __('Full name') }}</label>
                <input id="reg-name" type="text" wire:model="full_name" required autocomplete="name" class="mt-1.5 w-full rounded-xl border border-cream-300 bg-cream-50 px-3 py-2.5 text-base text-heading focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="reg-email" class="block text-sm font-semibold text-heading">{{ __('Email address') }}</label>
                <input id="reg-email" type="email" wire:model="email" required autocomplete="email" class="mt-1.5 w-full rounded-xl border border-cream-300 bg-cream-50 px-3 py-2.5 text-base text-heading focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-[1fr_6rem] gap-3">
                <div>
                    <label for="reg-phone" class="block text-sm font-semibold text-heading">{{ __('Phone (optional)') }}</label>
                    <input id="reg-phone" type="tel" wire:model="phone" autocomplete="tel" class="mt-1.5 w-full rounded-xl border border-cream-300 bg-cream-50 px-3 py-2.5 text-base text-heading focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reg-party" class="block text-sm font-semibold text-heading">{{ __('People') }}</label>
                    <input id="reg-party" type="number" min="1" max="10" wire:model="party_size" required class="mt-1.5 w-full rounded-xl border border-cream-300 bg-cream-50 px-3 py-2.5 text-base text-heading focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
            </div>
            @error('party_size') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <label class="flex items-start gap-2 text-xs text-ink-500">
                <input type="checkbox" wire:model="consent" class="mt-0.5 size-4 rounded border-cream-300 text-gold-500 focus:ring-gold-500">
                <span>{{ __('I agree that the foundation can use these details to organise this event.') }}</span>
            </label>
            @error('consent') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full disabled:opacity-60">{{ __('Sign me up') }}</button>
        </form>
    @endif
</div>
