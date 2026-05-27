<?php

use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

new #[Title('Two-factor authentication')] class extends Component
{
    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), Response::HTTP_FORBIDDEN);

        if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
            $disableTwoFactorAuthentication(auth()->user());
        }

        $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout
        :heading="__('Two-factor authentication')"
        :subheading="__('Manage your two-factor authentication settings')"
    >
        <div class="space-y-6 text-sm" wire:cloak>
            @if ($twoFactorEnabled)
                <div class="space-y-5">
                    <span class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-green-700">
                        <span class="size-1.5 rounded-full bg-green-600"></span>{{ __('Enabled') }}
                    </span>

                    <p class="text-ink-500">
                        {{ __('With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </p>

                    <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />

                    <button
                        type="button"
                        wire:click="disable"
                        class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900"
                    >
                        {{ __('Disable 2FA') }}
                    </button>
                </div>
            @else
                <div class="space-y-5">
                    <span class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-red-700">
                        <span class="size-1.5 rounded-full bg-red-600"></span>{{ __('Disabled') }}
                    </span>

                    <p class="text-ink-500">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </p>

                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-two-factor-setup'); $wire.dispatch('start-two-factor-setup')"
                        class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900"
                    >
                        {{ __('Enable 2FA') }}
                    </button>

                    <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                </div>
            @endif
        </div>
    </x-pages::settings.layout>
</section>
