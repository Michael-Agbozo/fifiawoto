<?php

use App\Enums\UserRole;
use App\Mail\TeamInvitation;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public bool $showInviteForm = false;

    public ?int $confirmDeleteId = null;

    public ?string $flashMessage = null;

    public ?string $generatedPassword = null;

    public string $search = '';

    public string $roleFilter = '';

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('nullable|string|max:40')]
    public string $phone = '';

    #[Validate('required|string')]
    public string $role = 'foundation_staff';

    public ?int $editRoleId = null;

    public string $editRoleValue = '';

    public ?int $editPermissionsId = null;

    /** @var array<int, string> */
    public array $editPermissionsValues = [];

    public function startEditPermissions(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->editPermissionsId = $user->id;
        $this->editPermissionsValues = $user->permissions ?? [];
    }

    public function cancelEditPermissions(): void
    {
        $this->editPermissionsId = null;
        $this->editPermissionsValues = [];
    }

    public function savePermissions(int $id): void
    {
        abort_unless(auth()->user()?->canDo('users', 'update'), 403);

        $filtered = Permissions::sanitize($this->editPermissionsValues);

        $user = User::query()->findOrFail($id);
        $user->forceFill(['permissions' => $filtered ?: null])->save();

        $this->editPermissionsId = null;
        $this->editPermissionsValues = [];
        $this->flashMessage = "Permissions updated for {$user->name}.";
    }

    public function startInvite(): void
    {
        $this->resetForm();
        $this->showInviteForm = true;
    }

    public function cancelInvite(): void
    {
        $this->resetForm();
    }

    public function invite(): void
    {
        abort_unless(auth()->user()?->canDo('users', 'create'), 403);

        $data = $this->validate();

        if (! in_array($data['role'], array_column(UserRole::cases(), 'value'), true)) {
            $this->addError('role', 'Pick a valid role.');

            return;
        }

        $generated = Str::password(14);

        $user = User::query()->forceCreate([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'role' => $data['role'],
            'password' => Hash::make($generated),
            'email_verified_at' => now(),
        ]);

        Mail::send(new TeamInvitation($user, $generated));

        $this->generatedPassword = $generated;
        $this->flashMessage = "Account created for {$data['name']}. Invitation email sent.";
        $this->reset(['name', 'email', 'phone']);
        $this->role = 'foundation_staff';
        $this->resetErrorBag();
    }

    public function dismissPassword(): void
    {
        $this->generatedPassword = null;
        $this->showInviteForm = false;
    }

    public function startEditRole(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->editRoleId = $user->id;
        $this->editRoleValue = $user->role?->value ?? UserRole::Volunteer->value;
    }

    public function cancelEditRole(): void
    {
        $this->editRoleId = null;
        $this->editRoleValue = '';
    }

    public function saveRole(int $id): void
    {
        abort_unless(auth()->user()?->canDo('users', 'update'), 403);

        if (! in_array($this->editRoleValue, array_column(UserRole::cases(), 'value'), true)) {
            $this->addError('role_'.$id, 'Pick a valid role.');

            return;
        }

        if ($id === auth()->id() && ! in_array($this->editRoleValue, [UserRole::Owner->value, UserRole::SuperAdmin->value], true)) {
            $this->addError('role_'.$id, 'You cannot demote your own administrator account.');

            return;
        }

        $user = User::query()->findOrFail($id);
        $user->forceFill(['role' => $this->editRoleValue])->save();

        $this->editRoleId = null;
        $this->editRoleValue = '';
        $this->flashMessage = "Updated role for {$user->name}.";
    }

    public function resetPassword(int $id): void
    {
        abort_unless(auth()->user()?->canDo('users', 'update'), 403);

        $user = User::query()->findOrFail($id);
        $newPassword = Str::password(14);
        $user->update(['password' => Hash::make($newPassword)]);

        $this->generatedPassword = $newPassword;
        $this->flashMessage = "Password reset for {$user->name}. Share securely.";
    }

    public function askDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->canDo('users', 'delete'), 403);

        if ($id === auth()->id()) {
            $this->confirmDeleteId = null;
            $this->addError('delete_'.$id, 'You cannot delete your own account.');

            return;
        }

        $user = User::query()->findOrFail($id);
        $name = $user->name;
        $user->delete();

        $this->confirmDeleteId = null;
        $this->flashMessage = "Removed account for {$name}.";
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->reset(['name', 'email', 'phone']);
        $this->role = 'foundation_staff';
        $this->resetErrorBag();
    }

    #[Computed]
    public function editingPermissionsUser(): ?User
    {
        return $this->editPermissionsId
            ? User::query()->find($this->editPermissionsId)
            : null;
    }

    #[Computed]
    public function users()
    {
        $query = User::query();

        if (filled($this->roleFilter)) {
            $query->where('role', $this->roleFilter);
        }

        if (filled($this->search)) {
            $needle = '%'.$this->search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            });
        }

        return $query->orderBy('name')->paginate(15);
    }

    #[Computed]
    public function roleCounts(): array
    {
        return User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role')
            ->all();
    }

    public function with(): array
    {
        return [
            'roleOptions' => collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @php
            $roleMeta = [
                'owner'                 => ['icon' => 'shield', 'palette' => 'red'],
                'super_admin'           => ['icon' => 'shield', 'palette' => 'gold'],
                'foundation_staff'      => ['icon' => 'users',  'palette' => 'brand'],
                'volunteer_coordinator' => ['icon' => 'hand',   'palette' => 'blue'],
                'media_manager'         => ['icon' => 'image',  'palette' => 'amber'],
                'volunteer'             => ['icon' => 'hand',   'palette' => 'green'],
            ];
        @endphp
        @foreach (\App\Enums\UserRole::cases() as $r)
            <x-admin.stat-card
                wire:click="$set('roleFilter', '{{ $r->value }}')"
                :icon="$roleMeta[$r->value]['icon'] ?? 'users'"
                :label="$r->label()"
                :value="(string) ($this->roleCounts[$r->value] ?? 0)"
                :active="$roleFilter === $r->value"
                :palette="$roleMeta[$r->value]['palette'] ?? 'brand'"
            />
        @endforeach
    </div>

    <x-admin.section-header
        title="Team"
        :subtitle="$this->users->total().' account(s) in view'"
    >
        <x-slot:actions>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name or email" class="w-72 rounded-xl border border-cream-300 bg-white px-3 py-2 text-xs text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-900/15">
            <button type="button" wire:click="startInvite" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-900">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Invite teammate
            </button>
            @if (filled($roleFilter) || filled($search))
                <x-admin.actions-menu>
                    <button type="button" wire:click="$reset('roleFilter','search')" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                        <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Clear all filters
                    </button>
                </x-admin.actions-menu>
            @endif
        </x-slot:actions>
    </x-admin.section-header>

    @if ($flashMessage)
        <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="$set('flashMessage', null)" class="text-green-700">×</button>
        </div>
    @endif

    @if ($generatedPassword)
        <div class="rounded-2xl border border-cream-300 bg-cream-100 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Temporary password</p>
            <p class="mt-3 font-mono text-lg text-ink-900">{{ $generatedPassword }}</p>
            <p class="mt-2 text-xs text-ink-500">Share this securely with the user. We will not show it again.</p>
            <button type="button" wire:click="dismissPassword" class="mt-3 inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Got it</button>
        </div>
    @endif

    <x-admin.modal :show="$showInviteForm" title="Invite a teammate" onClose="cancelInvite">
        <form wire:submit="invite" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Full name</label>
                    <input type="text" wire:model="name" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Email address</label>
                    <input type="email" wire:model="email" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Phone <span class="font-normal normal-case tracking-normal text-ink-500">(optional)</span></label>
                    <input type="tel" wire:model="phone" class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Role</label>
                    <select wire:model="role" required class="mt-2 block w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <p class="text-xs text-ink-500">We'll generate a one-time password for you to share securely. The user can change it after their first login.</p>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">
                    Create account
                </button>
                <button type="button" wire:click="cancelInvite" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">
                    Cancel
                </button>
            </div>
        </form>
    </x-admin.modal>

    <div class="overflow-hidden rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        @if ($this->users->isEmpty())
            <div class="p-10 text-center text-sm text-ink-500">No users match these filters.</div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                    <tr class="transition hover:bg-cream-100/40">
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3">Joined</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200 text-sm">
                    @foreach ($this->users as $user)
                        <tr class="transition hover:bg-cream-100/40">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-ink-900">
                                    {{ $user->name }}
                                    @if ($user->id === auth()->id())
                                        <span class="ml-1 text-xs text-ink-500">(you)</span>
                                    @endif
                                </p>
                                <p class="text-xs text-ink-500">{{ $user->email }}</p>
                                @if ($user->phone)
                                    <p class="text-xs text-ink-500">{{ $user->phone }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($editRoleId === $user->id)
                                    <select wire:model="editRoleValue" class="rounded-lg border border-cream-300 bg-white px-2 py-1.5 text-xs focus:border-gold-500 focus:outline-none focus:ring-2 focus:ring-gold-500/30">
                                        @foreach ($roleOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('role_'.$user->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @else
                                    @php
                                        $rolePalettes = [
                                            'owner'                 => 'red',
                                            'super_admin'           => 'gold',
                                            'foundation_staff'      => 'brand',
                                            'volunteer_coordinator' => 'blue',
                                            'media_manager'         => 'amber',
                                            'volunteer'             => 'green',
                                        ];
                                    @endphp
                                    <x-admin.status-pill
                                        :palette="$rolePalettes[$user->role?->value] ?? 'gray'"
                                        :label="$user->role?->label() ?? 'Member'"
                                    />
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-ink-500">{{ $user->created_at?->format('M j, Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end">
                                    @if ($editRoleId === $user->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="saveRole({{ $user->id }})" class="inline-flex items-center rounded-lg bg-gold-500 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-brand-900">Save role</button>
                                            <button type="button" wire:click="cancelEditRole" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                        </div>
                                    @elseif ($confirmDeleteId === $user->id)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" wire:click="delete({{ $user->id }})" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700">Confirm delete</button>
                                            <button type="button" wire:click="cancelDelete" class="inline-flex items-center rounded-lg border border-cream-300 px-3 py-1.5 text-xs font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
                                            @error('delete_'.$user->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    @else
                                        <x-admin.actions-menu>
                                            <button type="button" wire:click="startEditRole({{ $user->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.06 2.06 0 1 1 2.915 2.914L7.5 19.679l-4 1 1-4L16.862 4.487Z"/></svg>
                                                Edit role
                                            </button>
                                            <button type="button" wire:click="startEditPermissions({{ $user->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-brand-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c2.21 0 4-1.79 4-4S14.21 3 12 3 8 4.79 8 7s1.79 4 4 4Zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Zm6 5-2 2-1.5-1.5"/></svg>
                                                Manage permissions
                                            </button>
                                            <button type="button" wire:click="resetPassword({{ $user->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-ink-700 transition hover:bg-cream-100 hover:text-brand-900">
                                                <svg class="size-4 text-gold-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a4 4 0 1 1-4 4M7 11h.01M11 11h.01M3 11l8 0M3 11l3 3M3 11l3-3"/></svg>
                                                Reset password
                                            </button>
                                            <button type="button" wire:click="askDelete({{ $user->id }})" @click="open = false" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                                                Delete
                                            </button>
                                        </x-admin.actions-menu>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div>{{ $this->users->links() }}</div>

    <x-admin.modal :show="(bool) $this->editingPermissionsUser" :title="'Permissions · '.($this->editingPermissionsUser?->name ?? '')" :subtitle="'Role: '.($this->editingPermissionsUser?->role?->label() ?? '—').'. Defaults from the role are checked + locked; tick extra ones to grant more access.'" size="xl" onClose="cancelEditPermissions">
        @if ($pUser = $this->editingPermissionsUser)
            @php
                $registry = \App\Support\Permissions::registry();
                $roleDefaults = $pUser->role?->defaultPermissions() ?? [];
                $isOwner = $pUser->role === \App\Enums\UserRole::Owner;
                // For UI: every action column header (cluster of unique action keys across all resources, ordered)
                $allActionLabels = ['view' => 'View', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete', 'export' => 'Export'];
            @endphp

            <div class="overflow-x-auto rounded-2xl border border-cream-200">
                <table class="w-full text-sm">
                    <thead class="bg-cream-100/60 text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Section</th>
                            @foreach ($allActionLabels as $actionKey => $actionLabel)
                                <th class="px-2 py-3 text-center w-20">{{ $actionLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-200">
                        @foreach ($registry as $resource => $config)
                            <tr class="hover:bg-cream-100/40">
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $config['label'] }}</td>
                                @foreach ($allActionLabels as $actionKey => $actionLabel)
                                    <td class="px-2 py-3 text-center">
                                        @if (array_key_exists($actionKey, $config['actions']))
                                            @php
                                                $permKey = $resource.'.'.$actionKey;
                                                $isRoleGrant = $isOwner || in_array($permKey, $roleDefaults, true);
                                            @endphp
                                            <label class="inline-flex cursor-pointer items-center justify-center" title="{{ $permKey }}{{ $isRoleGrant ? ' (granted by role)' : '' }}">
                                                <input
                                                    type="checkbox"
                                                    value="{{ $permKey }}"
                                                    wire:model="editPermissionsValues"
                                                    @checked($isRoleGrant)
                                                    @disabled($isRoleGrant)
                                                    class="size-4 rounded border-cream-300 text-gold-500 focus:ring-gold-500 {{ $isRoleGrant ? 'opacity-60' : '' }}"
                                                >
                                            </label>
                                        @else
                                            <span class="text-ink-400">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-xs text-ink-500">
                <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-sm bg-gold-500/40"></span> Greyed boxes are granted automatically by the user's role and can't be removed without changing the role.</span>
            </p>

            <div class="mt-5 flex flex-wrap gap-3">
                <button type="button" wire:click="savePermissions({{ $pUser->id }})" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">Save permissions</button>
                <button type="button" wire:click="cancelEditPermissions" class="inline-flex items-center justify-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900">Cancel</button>
            </div>
        @endif
    </x-admin.modal>
</div>
