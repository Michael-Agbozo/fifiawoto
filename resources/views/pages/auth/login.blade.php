<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <div>
                <label for="login-email" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Email address') }}</label>
                <input
                    id="login-email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 placeholder-ink-500/60 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ show: false }">
                <div class="flex items-center justify-between">
                    <label for="login-password" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Password') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-gold-500 hover:text-brand-900" wire:navigate>
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>
                <div class="relative mt-2">
                    <input
                        id="login-password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="{{ __('Password') }}"
                        class="block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 pr-10 text-sm text-ink-900 placeholder-ink-500/60 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                    >
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute inset-y-0 right-0 grid w-10 place-items-center text-ink-500 hover:text-gold-500"
                        aria-label="Toggle password visibility"
                    >
                        <svg x-show="!show" class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                        <svg x-show="show" x-cloak class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-3 text-sm text-ink-700">
                <input
                    type="checkbox"
                    name="remember"
                    @checked(old('remember'))
                    class="size-4 rounded border-cream-300 text-gold-500 focus:ring-gold-500"
                >
                {{ __('Remember me') }}
            </label>

            <button
                type="submit"
                data-test="login-button"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60"
            >
                {{ __('Log in') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
