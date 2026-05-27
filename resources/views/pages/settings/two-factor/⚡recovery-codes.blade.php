<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    /** @var array<int, string> */
    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div
    class="space-y-5 rounded-2xl border border-cream-300 bg-white p-6 shadow-sm"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div>
        <h3 class="font-serif text-lg font-bold text-ink-900">{{ __('2FA recovery codes') }}</h3>
        <p class="mt-1 text-sm text-ink-500">
            {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
        </p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button
            type="button"
            x-show="!showRecoveryCodes"
            @click="showRecoveryCodes = true"
            aria-expanded="false"
            aria-controls="recovery-codes-section"
            class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900"
        >
            {{ __('View recovery codes') }}
        </button>

        <button
            type="button"
            x-show="showRecoveryCodes"
            x-cloak
            @click="showRecoveryCodes = false"
            aria-expanded="true"
            aria-controls="recovery-codes-section"
            class="inline-flex items-center justify-center rounded-2xl border-2 border-brand-900 px-5 py-2.5 text-sm font-bold text-brand-900 transition hover:bg-brand-900 hover:text-white"
        >
            {{ __('Hide recovery codes') }}
        </button>

        @if (filled($recoveryCodes))
            <button
                type="button"
                x-show="showRecoveryCodes"
                x-cloak
                wire:click="regenerateRecoveryCodes"
                class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-5 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900"
            >
                {{ __('Regenerate codes') }}
            </button>
        @endif
    </div>

    <div
        x-show="showRecoveryCodes"
        x-cloak
        x-transition
        id="recovery-codes-section"
        class="space-y-3"
        x-bind:aria-hidden="!showRecoveryCodes"
    >
        @error('recoveryCodes')
            <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        @if (filled($recoveryCodes))
            <div
                class="grid gap-1 rounded-lg bg-cream-100 p-4 font-mono text-sm text-ink-900"
                role="list"
                aria-label="{{ __('Recovery codes') }}"
            >
                @foreach ($recoveryCodes as $code)
                    <div role="listitem" class="select-text" wire:loading.class="opacity-50 animate-pulse">
                        {{ $code }}
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-ink-500">
                {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.') }}
            </p>
        @endif
    </div>
</div>
