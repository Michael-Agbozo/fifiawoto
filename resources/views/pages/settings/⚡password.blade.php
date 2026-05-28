<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Password settings')] class extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-5">
            <div>
                <label for="current-password" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Current password') }}</label>
                <input
                    id="current-password"
                    wire:model="current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="new-password" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('New password') }}</label>
                <input
                    id="new-password"
                    wire:model="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="new-password-confirmation" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Confirm password') }}</label>
                <input
                    id="new-password-confirmation"
                    wire:model="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    data-test="update-password-button"
                    class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900"
                >
                    {{ __('Save') }}
                </button>

                <x-action-message on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
