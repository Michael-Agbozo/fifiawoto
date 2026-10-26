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
    class="space-y-5 rounded-2xl border border-cream-300 bg-surface p-6 shadow-sm"
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
            class="inline-flex items-center justify-center rounded-2xl border-2 border-heading px-5 py-2.5 text-sm font-bold text-heading transition hover:bg-brand-900 hover:text-white"
        >
            {{ __('Hide recovery codes') }}
        </button>

        @if (filled($recoveryCodes))
            <button
                type="button"
                x-show="showRecoveryCodes"
                x-cloak
                wire:click="regenerateRecoveryCodes"
                class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-5 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-heading hover:text-heading"
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
            <div x-data="{ copied: false, codes: @js(implode("\n", $recoveryCodes)) }">
                <button
                    type="button"
                    data-test="copy-recovery-codes"
                    @click="navigator.clipboard.writeText(codes).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="inline-flex items-center gap-2 rounded-xl border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-heading hover:text-heading"
                >
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z"/></svg>
                    <span x-text="copied ? '{{ __('Copied') }}' : '{{ __('Copy all') }}'">{{ __('Copy all') }}</span>
                </button>
                <span class="sr-only" role="status" x-text="copied ? '{{ __('Recovery codes copied to clipboard') }}' : ''"></span>
            </div>
            <p class="text-xs text-ink-500">
                {{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.') }}
            </p>
        @endif
    </div>
</div>
