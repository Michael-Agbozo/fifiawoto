<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf

            <div>
                <label for="forgot-email" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Email address') }}</label>
                <input
                    id="forgot-email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="email@example.com"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 placeholder-ink-500/60 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                data-test="email-password-reset-link-button"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60"
            >
                {{ __('Email password reset link') }}
            </button>
        </form>

        <p class="text-center text-sm text-ink-500">
            {{ __('Or, return to') }}
            <a href="{{ route('login') }}" class="font-semibold text-gold-500 hover:text-brand-900" wire:navigate>{{ __('log in') }}</a>
        </p>
    </div>
</x-layouts::auth>
