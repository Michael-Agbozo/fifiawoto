<?php

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create(['name' => 'Boss'])));

it('invites a new teammate and surfaces a temporary password', function () {
    Livewire::test('admin.users')
        ->call('startInvite')
        ->set('name', 'New Staffer')
        ->set('email', 'new@example.test')
        ->set('role', UserRole::FoundationStaff->value)
        ->call('invite')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'new@example.test')->sole();
    expect($user->role)->toBe(UserRole::FoundationStaff);
});

it('changes a role', function () {
    $target = User::factory()->withRole(UserRole::FoundationStaff)->create();

    Livewire::test('admin.users')
        ->call('startEditRole', $target->id)
        ->set('editRoleValue', UserRole::VolunteerCoordinator->value)
        ->call('saveRole', $target->id);

    expect($target->refresh()->role)->toBe(UserRole::VolunteerCoordinator);
});

it('prevents you from demoting your own account', function () {
    $me = auth()->user();

    Livewire::test('admin.users')
        ->call('startEditRole', $me->id)
        ->set('editRoleValue', UserRole::Volunteer->value)
        ->call('saveRole', $me->id)
        ->assertHasErrors('role_'.$me->id);

    expect($me->refresh()->role)->toBe(UserRole::SuperAdmin);
});

it('prevents you from deleting your own account', function () {
    $me = auth()->user();

    Livewire::test('admin.users')
        ->call('askDelete', $me->id)
        ->call('delete', $me->id)
        ->assertHasErrors('delete_'.$me->id);

    expect(User::query()->whereKey($me->id)->exists())->toBeTrue();
});

it('deletes another user', function () {
    $target = User::factory()->withRole(UserRole::FoundationStaff)->create();

    Livewire::test('admin.users')
        ->call('askDelete', $target->id)
        ->call('delete', $target->id);

    expect(User::query()->whereKey($target->id)->exists())->toBeFalse();
});
