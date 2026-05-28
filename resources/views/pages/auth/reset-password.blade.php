<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <div>
                <label for="reset-email" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Email') }}</label>
                <input
                    id="reset-email"
                    name="email"
                    type="email"
                    value="{{ request('email') }}"
                    required
                    autocomplete="email"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 placeholder-ink-500/60 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ show: false }">
                <label for="reset-password" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Password') }}</label>
                <div class="relative mt-2">
                    <input
                        id="reset-password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
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

            <div x-data="{ show: false }">
                <label for="reset-password-confirmation" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Confirm password') }}</label>
                <div class="relative mt-2">
                    <input
                        id="reset-password-confirmation"
                        name="password_confirmation"
                        :type="show ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Confirm password') }}"
                        class="block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 pr-10 text-sm text-ink-900 placeholder-ink-500/60 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                    >
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 grid w-10 place-items-center text-ink-500 hover:text-gold-500" aria-label="Toggle password visibility">
                        <span x-show="!show" aria-hidden="true">👁</span>
                        <span x-show="show" x-cloak aria-hidden="true">🙈</span>
                    </button>
                </div>
                @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                data-test="reset-password-button"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60"
            >
                {{ __('Reset password') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
