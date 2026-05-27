@if ($submitted)
    <div role="status" class="{{ $compact ? '' : 'mt-8' }} rounded-2xl border border-brand-200 bg-brand-50 p-6 text-sm text-brand-800">
        <p class="font-semibold text-brand-900">Thank you for applying.</p>
        <p class="mt-1 text-ink-500">
            We've received your volunteer application. The coordinator will reach out within a few business days.
        </p>
    </div>
@else
    <form wire:submit="submit" class="{{ $compact ? '' : 'mt-8' }} space-y-5">
        @error('rate')
            <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="vol-name" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Full name</label>
                <input id="vol-name" type="text" wire:model="full_name" required
                    class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-brand-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-email" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Email address</label>
                <input id="vol-email" type="email" wire:model="email" required
                    class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-brand-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-phone" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Phone number</label>
                <input id="vol-phone" type="tel" wire:model="phone" required
                    class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-brand-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="vol-country" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Country / location</label>
                <input id="vol-country" type="text" wire:model="country" required
                    class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-brand-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <fieldset>
            <legend class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Areas of interest</legend>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach ($interestOptions as $value => $label)
                    <label class="flex items-start gap-3 rounded-lg border border-brand-100 bg-cream-50 px-3 py-2 text-sm text-brand-800 hover:border-brand-300">
                        <input type="checkbox" value="{{ $value }}" wire:model.live="interests" class="mt-0.5 rounded border-brand-300 text-brand-700 focus:ring-brand-500">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('interests') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset>
            <legend class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Availability</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($availabilityOptions as $value => $label)
                    <label class="cursor-pointer rounded-full border border-brand-200 bg-cream-50 px-4 py-2 text-sm text-brand-800 has-checked:border-brand-700 has-checked:bg-brand-700 has-checked:text-cream-50">
                        <input type="radio" name="availability" value="{{ $value }}" wire:model.live="availability" class="sr-only">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('availability') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <div>
            <label for="vol-skills" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Skills or experience</label>
            <textarea id="vol-skills" wire:model="skills" rows="3"
                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-brand-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                placeholder="Optional, anything relevant we should know about."></textarea>
            @error('skills') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="vol-motivation" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Motivation</label>
            <textarea id="vol-motivation" wire:model="motivation" rows="4" required
                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-brand-900 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                placeholder="Why do you want to volunteer with the foundation?"></textarea>
            @error('motivation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-start gap-3 text-sm text-brand-800">
            <input type="checkbox" wire:model.live="consent" class="mt-1 rounded border-brand-300 text-brand-700 focus:ring-brand-500" required>
            <span>
                I consent to the foundation storing this information to evaluate my application and contact me about volunteer opportunities.
            </span>
        </label>
        @error('consent') <p class="-mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

        <div class="pt-2">
            <button type="submit"
                class="inline-flex items-center justify-center rounded-full bg-gold-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-900 disabled:opacity-60"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Apply to Volunteer</span>
                <span wire:loading>Submitting…</span>
            </button>
        </div>
    </form>
@endif
