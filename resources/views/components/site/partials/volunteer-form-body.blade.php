@if ($submitted)
    <div role="status" class="{{ $compact ? '' : 'mt-8' }} rounded-2xl border border-brand-200 bg-brand-50 p-6 text-sm text-heading-soft">
        <p class="font-semibold text-heading">Thank you for applying.</p>
        <p class="mt-1 text-ink-500">
            We've received your volunteer application. The coordinator will reach out within a few business days.
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
                <label for="vol-name" class="block text-sm font-semibold text-heading">Full name</label>
                <input id="vol-name" type="text" wire:model="full_name" required
                    class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-email" class="block text-sm font-semibold text-heading">Email address</label>
                <input id="vol-email" type="email" wire:model="email" required
                    class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-phone" class="block text-sm font-semibold text-heading">Phone number</label>
                <input id="vol-phone" type="tel" wire:model="phone" required
                    class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-country" class="block text-sm font-semibold text-heading">Country / location</label>
                <input id="vol-country" type="text" wire:model="country" required
                    class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <fieldset>
            <legend class="block text-sm font-semibold text-heading">Areas of interest</legend>
            <div @class(['mt-3 grid gap-2.5 sm:grid-cols-2', 'xl:grid-cols-3' => ! $compact])>
                @foreach ($interestOptions as $value => $label)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-sm text-heading-soft transition hover:border-heading has-checked:border-heading has-checked:bg-brand-50 has-checked:text-heading">
                        <input type="checkbox" value="{{ $value }}" wire:model.live="interests" class="mt-0.5 rounded border-brand-300 text-label focus:ring-brand-500">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('interests') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset>
            <legend class="block text-sm font-semibold text-heading">Availability</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($availabilityOptions as $value => $label)
                    <label class="cursor-pointer rounded-full border border-cream-300 bg-cream-50 px-5 py-2.5 text-sm font-medium text-heading-soft transition hover:border-heading has-checked:border-heading has-checked:bg-heading has-checked:text-surface">
                        <input type="radio" name="availability" value="{{ $value }}" wire:model.live="availability" class="sr-only">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('availability') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <div>
            <label for="vol-skills" class="block text-sm font-semibold text-heading">Skills or experience</label>
            <textarea id="vol-skills" wire:model="skills" rows="3"
                class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15"
                placeholder="Optional, anything relevant we should know about."></textarea>
            @error('skills') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="vol-motivation" class="block text-sm font-semibold text-heading">Motivation</label>
            <textarea id="vol-motivation" wire:model="motivation" rows="4" required
                class="mt-2 w-full rounded-xl border border-cream-300 bg-cream-50 px-4 py-3 text-base text-heading placeholder-ink-500/70 transition focus:border-heading focus:bg-surface focus:outline-none focus:ring-4 focus:ring-brand-500/15"
                placeholder="Why do you want to volunteer with the foundation?"></textarea>
            @error('motivation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-start gap-3 text-sm text-heading-soft">
            <input type="checkbox" wire:model.live="consent" class="mt-1 rounded border-brand-300 text-label focus:ring-brand-500" required>
            <span>
                I consent to the foundation storing this information to evaluate my application and contact me about volunteer opportunities.
            </span>
        </label>
        @error('consent') <p class="-mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

        <div class="pt-2">
            <button type="submit"
                class="btn btn-primary btn-lg w-full disabled:opacity-60 sm:w-auto"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Apply to Volunteer</span>
                <span wire:loading>Submitting…</span>
            </button>
        </div>
    </form>
@endif
