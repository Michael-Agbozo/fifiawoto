<x-layouts::auth :title="__('Two-factor authentication')">
    <div
        class="flex flex-col gap-6"
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;
                $nextTick(() => {
                    this.showRecoveryInput
                        ? this.$refs.recovery_code?.focus()
                        : this.$refs.code?.focus();
                });
            },
        }"
    >
        <div x-show="!showRecoveryInput">
            <x-auth-header
                :title="__('Authentication code')"
                :description="__('Enter the authentication code provided by your authenticator application.')"
            />
        </div>

        <div x-show="showRecoveryInput" x-cloak>
            <x-auth-header
                :title="__('Recovery code')"
                :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
            />
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-5">
            @csrf

            <div x-show="!showRecoveryInput">
                <label for="tfa-code" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Authentication code') }}</label>
                <input
                    id="tfa-code"
                    x-ref="code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="123 456"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-center text-lg tracking-[0.4em] text-ink-900 placeholder-ink-500/40 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div x-show="showRecoveryInput" x-cloak>
                <label for="tfa-recovery" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Recovery code') }}</label>
                <input
                    id="tfa-recovery"
                    x-ref="recovery_code"
                    name="recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    x-bind:required="showRecoveryInput"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 placeholder-ink-500/60 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('recovery_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60"
            >
                {{ __('Continue') }}
            </button>
        </form>

        <p class="text-center text-sm text-ink-500">
            <span x-show="!showRecoveryInput">{{ __('or you can') }}
                <button type="button" @click="toggleInput()" class="font-semibold text-gold-500 underline-offset-2 hover:text-brand-900 hover:underline">
                    {{ __('login using a recovery code') }}
                </button>
            </span>
            <span x-show="showRecoveryInput" x-cloak>{{ __('or you can') }}
                <button type="button" @click="toggleInput()" class="font-semibold text-gold-500 underline-offset-2 hover:text-brand-900 hover:underline">
                    {{ __('login using an authentication code') }}
                </button>
            </span>
        </p>
    </div>
</x-layouts::auth>
