<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();
    }

    /**
     * @return array{title: string, description: string, buttonText: string}
     */
    public function getModalConfigProperty(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => __('Two-factor authentication enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify authentication code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable two-factor authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }
}; ?>

<div
    x-data="{ open: false }"
    @open-two-factor-setup.window="open = true"
    @close-two-factor-setup.window="open = false"
    @keydown.escape.window="open = false; $wire.closeModal()"
>
    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 grid place-items-center bg-brand-900/60 px-4 py-8"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="open"
            x-cloak
            x-transition
            @click.outside="open = false; $wire.closeModal()"
            class="w-full max-w-md overflow-hidden rounded-3xl bg-white p-8 shadow-2xl"
        >
            <div class="space-y-6">
                <div class="text-center">
                    <h3 class="font-serif text-xl font-bold text-ink-900">{{ $this->modalConfig['title'] }}</h3>
                    <p class="mt-2 text-sm text-ink-500">{{ $this->modalConfig['description'] }}</p>
                </div>

                @if ($showVerificationStep)
                    <div class="space-y-5">
                        <label for="tfa-confirm-code" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Authentication code') }}</label>
                        <input
                            id="tfa-confirm-code"
                            wire:model="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            placeholder="123 456"
                            class="block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-center text-lg tracking-[0.4em] text-ink-900 placeholder-ink-500/40 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                        >
                        @error('code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        <div class="flex gap-3">
                            <button
                                type="button"
                                wire:click="resetVerification"
                                class="inline-flex flex-1 items-center justify-center rounded-2xl border-2 border-cream-300 px-5 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900"
                            >
                                {{ __('Back') }}
                            </button>
                            <button
                                type="button"
                                wire:click="confirmTwoFactor"
                                x-bind:disabled="$wire.code.length < 6"
                                class="inline-flex flex-1 items-center justify-center rounded-2xl bg-gold-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60"
                            >
                                {{ __('Confirm') }}
                            </button>
                        </div>
                    </div>
                @else
                    @error('setupData')
                        <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror

                    <div class="flex justify-center">
                        <div class="relative aspect-square w-56 overflow-hidden rounded-2xl border border-cream-300">
                            @empty($qrCodeSvg)
                                <div class="absolute inset-0 grid animate-pulse place-items-center bg-cream-100">
                                    <span class="text-xs text-ink-500">Generating…</span>
                                </div>
                            @else
                                <div class="grid h-full place-items-center p-4">
                                    <div class="rounded bg-white p-3">{!! $qrCodeSvg !!}</div>
                                </div>
                            @endempty
                        </div>
                    </div>

                    <button
                        type="button"
                        :disabled="@js($errors->has('setupData'))"
                        wire:click="showVerificationIfNecessary"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-gold-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 disabled:opacity-60"
                    >
                        {{ $this->modalConfig['buttonText'] }}
                    </button>

                    <div class="space-y-3">
                        <div class="relative flex items-center justify-center">
                            <div class="absolute inset-x-0 top-1/2 h-px bg-cream-300"></div>
                            <span class="relative bg-white px-3 text-xs uppercase tracking-[0.18em] text-ink-500">{{ __('or, enter the code manually') }}</span>
                        </div>

                        <div
                            x-data="{
                                copied: false,
                                async copy() {
                                    try {
                                        await navigator.clipboard.writeText(@js($manualSetupKey));
                                        this.copied = true;
                                        setTimeout(() => this.copied = false, 1500);
                                    } catch (e) {
                                        console.warn('Could not copy to clipboard');
                                    }
                                }
                            }"
                            class="flex items-stretch overflow-hidden rounded-lg border border-cream-300"
                        >
                            @empty($manualSetupKey)
                                <div class="grid w-full place-items-center bg-cream-100 p-3 text-xs text-ink-500">Loading…</div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full bg-transparent px-3 py-2.5 font-mono text-sm text-ink-900 outline-none"
                                >
                                <button
                                    type="button"
                                    @click="copy()"
                                    class="border-l border-cream-300 px-3 text-xs font-semibold uppercase tracking-[0.18em] text-ink-700 hover:bg-cream-100"
                                >
                                    <span x-show="!copied">Copy</span>
                                    <span x-show="copied" x-cloak class="text-green-600">Copied</span>
                                </button>
                            @endempty
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
