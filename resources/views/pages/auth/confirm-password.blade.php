<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Confirm password')"
            :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
            @csrf

            <div x-data="{ show: false }">
                <label for="confirm-password" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Password') }}</label>
                <div class="relative mt-2">
                    <input
                        id="confirm-password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="{{ __('Password') }}"
                        class="block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 pr-10 text-sm text-ink-900 placeholder-ink-500/60 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                    >
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 grid w-10 place-items-center text-ink-500 hover:text-gold-500" aria-label="Toggle password visibility">
                        <span x-show="!show" aria-hidden="true">👁</span>
                        <span x-show="show" x-cloak aria-hidden="true">🙈</span>
                    </button>
                </div>
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                data-test="confirm-password-button"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60"
            >
                {{ __('Confirm') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
