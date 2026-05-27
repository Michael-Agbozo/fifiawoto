<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div
    x-data="{ open: @js($errors->isNotEmpty()) }"
    @open-delete-account.window="open = true"
    @keydown.escape.window="open = false"
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
            @click.outside="open = false"
            class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl"
        >
            <form method="POST" wire:submit="deleteUser" class="space-y-5 p-8">
                <div>
                    <h3 class="font-serif text-2xl font-bold text-ink-900">{{ __('Are you sure you want to delete your account?') }}</h3>
                    <p class="mt-2 text-sm text-ink-500">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                    </p>
                </div>

                <div>
                    <label for="delete-account-password" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Password') }}</label>
                    <input
                        id="delete-account-password"
                        wire:model="password"
                        type="password"
                        autocomplete="current-password"
                        class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                    >
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="open = false"
                        class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-5 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        data-test="confirm-delete-user-button"
                        class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900"
                    >
                        {{ __('Delete account') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
