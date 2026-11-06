@if ($submitted)
    <div role="status" class="{{ $compact ? '' : 'mt-8' }} rounded-2xl border border-brand-200 bg-brand-50 p-6 text-sm text-heading-soft">
        <p class="font-semibold text-heading">{{ __('Thank you for applying.') }}</p>
        <p class="mt-1 text-ink-500">
            {{ __('We\'ve received your volunteer application. The coordinator will reach out within a few business days.') }}
        </p>
    </div>
@else
    <form wire:submit="submit" class="{{ $compact ? '' : 'mt-8' }} space-y-6">
        @include('components.site.partials.honeypot')
        @error('rate')
            <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="vol-name-{{ $this->getId() }}" class="block text-sm font-semibold text-heading">{{ __('Full name') }}</label>
                <input id="vol-name-{{ $this->getId() }}" type="text" wire:model="full_name" required
                    class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-email-{{ $this->getId() }}" class="block text-sm font-semibold text-heading">{{ __('Email address') }}</label>
                <input id="vol-email-{{ $this->getId() }}" type="email" wire:model="email" required
                    class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-phone-{{ $this->getId() }}" class="block text-sm font-semibold text-heading">{{ __('Phone number') }}</label>
                <input id="vol-phone-{{ $this->getId() }}" type="tel" wire:model="phone" required
                    class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-country-{{ $this->getId() }}" class="block text-sm font-semibold text-heading">{{ __('Country / location') }}</label>
                <input id="vol-country-{{ $this->getId() }}" type="text" wire:model="country" required
                    class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <fieldset>
            <legend class="block text-sm font-semibold text-heading">{{ __('Areas of interest') }}</legend>
            <div @class(['mt-3 grid gap-2.5 sm:grid-cols-2', 'xl:grid-cols-3' => ! $compact])>
                @foreach ($interestOptions as $value => $label)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-sm text-heading-soft transition hover:border-heading has-checked:border-heading has-checked:bg-brand-50 has-checked:text-heading">
                        <input type="checkbox" value="{{ $value }}" wire:model.live="interests" class="mt-0.5 rounded border-brand-300 text-label focus:ring-brand-500">
                        <span>{{ __($label) }}</span>
                    </label>
                @endforeach
            </div>
            @error('interests') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset>
            <legend class="block text-sm font-semibold text-heading">{{ __('Availability') }}</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($availabilityOptions as $value => $label)
                    <label class="cursor-pointer rounded-full border border-cream-300 bg-cream-50 px-5 py-2.5 text-sm font-medium text-heading-soft transition hover:border-heading has-checked:border-heading has-checked:bg-heading has-checked:text-surface">
                        <input type="radio" name="availability" value="{{ $value }}" wire:model.live="availability" class="sr-only">
                        {{ __($label) }}
                    </label>
                @endforeach
            </div>
            @error('availability') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <div>
            <label for="vol-skills-{{ $this->getId() }}" class="block text-sm font-semibold text-heading">{{ __('Skills or experience') }}</label>
            <textarea id="vol-skills-{{ $this->getId() }}" wire:model="skills" rows="3"
                class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15"
                placeholder="{{ __('Optional, anything relevant we should know about.') }}"></textarea>
            @error('skills') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="vol-motivation-{{ $this->getId() }}" class="block text-sm font-semibold text-heading">{{ __('Motivation') }}</label>
            <textarea id="vol-motivation-{{ $this->getId() }}" wire:model="motivation" rows="4" required
                class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15"
                placeholder="{{ __('Why do you want to volunteer with the foundation?') }}"></textarea>
            @error('motivation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-start gap-3 text-sm text-heading-soft">
            <input type="checkbox" wire:model.live="consent" class="mt-1 rounded border-brand-300 text-label focus:ring-brand-500" required>
            <span>
                {{ __('I consent to the foundation storing this information to evaluate my application and contact me about volunteer opportunities.') }}
            </span>
        </label>
        @error('consent') <p class="-mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

        <div class="pt-2">
            <button type="submit"
                class="btn btn-primary btn-lg w-full disabled:opacity-60 sm:w-auto"
                wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('Apply to Volunteer') }}</span>
                <span wire:loading>{{ __('Submitting…') }}</span>
            </button>
        </div>
    </form>
@endif
