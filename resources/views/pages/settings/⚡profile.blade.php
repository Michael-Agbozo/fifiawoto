<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component
{
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';

    public string $email = '';

    public ?TemporaryUploadedFile $photo = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            ...$this->profileRules($user->id),
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($this->photo) {
            $this->deleteStoredAvatar($user->avatar_path);
            $user->avatar_path = $this->photo->store('avatars', 'public');
            $this->photo = null;
        }

        $user->fill(Arr::except($validated, 'photo'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        $this->deleteStoredAvatar($user->avatar_path);
        $user->forceFill(['avatar_path' => null])->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Remove a previously uploaded avatar; remote URLs (seeded/external) are left alone.
     */
    protected function deleteStoredAvatar(?string $path): void
    {
        if (filled($path) && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-5">
            <div class="flex items-center gap-5">
                @if ($photo && $photo->isPreviewable())
                    <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('New profile picture preview') }}" class="size-20 shrink-0 rounded-full object-cover ring-2 ring-cream-200">
                @else
                    <x-admin.avatar :src="Auth::user()->avatar_path" :name="Auth::user()->name" size="xl" class="shrink-0" />
                @endif

                <div class="space-y-2">
                    <label for="profile-photo" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Profile picture') }}</label>
                    <input
                        id="profile-photo"
                        wire:model="photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="block text-sm text-ink-700 file:mr-3 file:rounded-full file:border-0 file:bg-gold-500 file:px-4 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-brand-900"
                    >
                    <p class="text-xs text-ink-500" wire:loading.remove wire:target="photo">{{ __('JPG, PNG or WebP, up to 2 MB. Saved when you click Save.') }}</p>
                    <p class="text-xs text-ink-500" wire:loading wire:target="photo">{{ __('Uploading…') }}</p>
                    @error('photo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    @if (Auth::user()->avatar_path && ! $photo)
                        <button type="button" wire:click="removeAvatar" class="text-xs font-semibold text-red-600 hover:text-red-700">
                            {{ __('Remove photo') }}
                        </button>
                    @endif
                </div>
            </div>

            <div>
                <label for="profile-name" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Name') }}</label>
                <input
                    id="profile-name"
                    wire:model="name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="profile-email" class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ __('Email') }}</label>
                <input
                    id="profile-email"
                    wire:model="email"
                    type="email"
                    required
                    autocomplete="email"
                    class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30"
                >
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                @if ($this->hasUnverifiedEmail)
                    <p class="mt-3 text-sm text-ink-500">
                        {{ __('Your email address is unverified.') }}
                        <button type="button" wire:click.prevent="resendVerificationNotification" class="font-semibold text-gold-500 hover:text-brand-900">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-semibold text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                @endif
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    data-test="update-profile-button"
                    class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900"
                >
                    {{ __('Save') }}
                </button>

                <x-action-message on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
