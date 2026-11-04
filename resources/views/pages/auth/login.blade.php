<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-8">
        <x-auth-header eyebrow="Staff sign in" :title="__('Welcome back')" :description="__('Log in to your account with the email and password your administrator gave you.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <label for="login-email" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">{{ __('Email address') }}</label>
                <div class="relative mt-2">
                <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                <input
                    id="login-email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="block w-full rounded-2xl border border-cream-300 bg-surface py-3.5 pl-12 pr-4 text-base text-ink-900 shadow-sm placeholder-ink-500/60 transition focus:border-heading focus:outline-none focus:ring-4 focus:ring-heading/10"
                >
                </div>
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ show: false }">
                <div class="flex items-center justify-between">
                    <label for="login-password" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">{{ __('Password') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-gold-500 hover:text-heading" wire:navigate>
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>
                <div class="relative mt-2">
                    <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    <input
                        id="login-password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="{{ __('Password') }}"
                        class="block w-full rounded-2xl border border-cream-300 bg-surface py-3.5 pl-12 pr-12 text-base text-ink-900 shadow-sm placeholder-ink-500/60 transition focus:border-heading focus:outline-none focus:ring-4 focus:ring-heading/10"
                    >
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute inset-y-0 right-1 grid w-11 place-items-center text-ink-500 hover:text-gold-500"
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
                class="btn btn-primary btn-lg w-full gap-2 disabled:opacity-60"
            >
                {{ __('Log in') }} <span aria-hidden="true">→</span>
            </button>
        </form>

        <p class="flex items-start gap-3 rounded-2xl bg-cream-100 p-4 text-xs leading-relaxed text-ink-500 ring-1 ring-cream-300">
            <svg class="mt-0.5 size-4 shrink-0 text-gold-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
            <span>This area is for foundation staff. Accounts are created by an administrator. Need access? <a href="{{ route('contact') }}" class="font-semibold text-heading underline-offset-2 hover:underline" wire:navigate>Contact the team</a>.</span>
        </p>
    </div>
</x-layouts::auth>
