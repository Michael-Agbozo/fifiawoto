<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
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

it('disables 2FA for another user', function () {
    $target = User::factory()->withTwoFactor()->create();

    Livewire::test('admin.users')
        ->call('confirmDisable2fa', $target->id)
        ->assertSee($target->name)
        ->call('disableTwoFactor', $target->id)
        ->assertSet('confirmDisable2faId', null);

    expect($target->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('toggles the 2FA requirement for another user', function () {
    $target = User::factory()->create();

    Livewire::test('admin.users')->call('toggleRequireTwoFactor', $target->id);
    expect($target->refresh()->requires_two_factor)->toBeTrue();

    Livewire::test('admin.users')->call('toggleRequireTwoFactor', $target->id);
    expect($target->refresh()->requires_two_factor)->toBeFalse();
});

it('prevents you from changing your own 2FA from the users panel', function () {
    $me = auth()->user();
    $me->forceFill(User::factory()->withTwoFactor()->raw())->save();

    Livewire::test('admin.users')->call('disableTwoFactor', $me->id);
    Livewire::test('admin.users')->call('toggleRequireTwoFactor', $me->id);

    $me->refresh();
    expect($me->hasEnabledTwoFactorAuthentication())->toBeTrue()
        ->and($me->requires_two_factor)->toBeFalse();
});

it('blocks 2FA management without users.update', function () {
    $target = User::factory()->withTwoFactor()->create();
    $this->actingAs(User::factory()->foundationStaff()->create());

    Livewire::test('admin.users')->call('disableTwoFactor', $target->id);
    Livewire::test('admin.users')->call('toggleRequireTwoFactor', $target->id);

    $target->refresh();
    expect($target->hasEnabledTwoFactorAuthentication())->toBeTrue()
        ->and($target->requires_two_factor)->toBeFalse();
});

it('deactivates and reactivates another user', function () {
    $target = User::factory()->create();

    Livewire::test('admin.users')->call('toggleActive', $target->id);
    expect($target->refresh()->is_active)->toBeFalse();

    Livewire::test('admin.users')->call('toggleActive', $target->id);
    expect($target->refresh()->is_active)->toBeTrue();
});

it('prevents you from deactivating your own account', function () {
    $me = auth()->user();

    Livewire::test('admin.users')->call('toggleActive', $me->id);

    expect($me->refresh()->is_active)->toBeTrue();
});

it('filters the list by active status', function () {
    User::factory()->create(['name' => 'Still Here']);
    User::factory()->create(['name' => 'On Leave', 'is_active' => false]);

    Livewire::test('admin.users')
        ->set('activeFilter', 'inactive')
        ->assertSee('On Leave')
        ->assertDontSee('Still Here');
});

it('sends a password reset link to another user', function () {
    Notification::fake();
    $target = User::factory()->create();

    Livewire::test('admin.users')
        ->call('sendPasswordReset', $target->id)
        ->assertSet('flashMessage', "Password reset link sent to {$target->name}.");

    Notification::assertSentTo($target, ResetPassword::class);
});

it('throttles repeated password reset links', function () {
    Notification::fake();
    $target = User::factory()->create();

    Livewire::test('admin.users')->call('sendPasswordReset', $target->id);
    Livewire::test('admin.users')
        ->call('sendPasswordReset', $target->id)
        ->assertSet('flashMessage', fn (string $message) => str_contains($message, 'recently'));

    Notification::assertSentToTimes($target, ResetPassword::class, 1);
});

it('does not send a password reset link to yourself', function () {
    Notification::fake();

    Livewire::test('admin.users')->call('sendPasswordReset', auth()->id());

    Notification::assertNothingSent();
});

it('blocks account status and reset links without users.update', function () {
    Notification::fake();
    $target = User::factory()->create();
    $this->actingAs(User::factory()->foundationStaff()->create());

    Livewire::test('admin.users')->call('toggleActive', $target->id);
    Livewire::test('admin.users')->call('sendPasswordReset', $target->id);

    expect($target->refresh()->is_active)->toBeTrue();
    Notification::assertNothingSent();
});

it('does not let a non-owner change the owner account', function () {
    Notification::fake();
    $owner = User::factory()->owner()->create();

    Livewire::test('admin.users')->call('toggleActive', $owner->id)->assertForbidden();
    Livewire::test('admin.users')->call('sendPasswordReset', $owner->id)->assertForbidden();
    Livewire::test('admin.users')->call('toggleRequireTwoFactor', $owner->id)->assertForbidden();
    Livewire::test('admin.users')->call('delete', $owner->id)->assertForbidden();
    Livewire::test('admin.users')
        ->set('editRoleValue', UserRole::Volunteer->value)
        ->call('saveRole', $owner->id)
        ->assertForbidden();

    $owner->refresh();
    expect($owner->is_active)->toBeTrue()
        ->and($owner->requires_two_factor)->toBeFalse()
        ->and($owner->role)->toBe(UserRole::Owner);
    Notification::assertNothingSent();
});

it('lets the owner manage another owner-level account', function () {
    $this->actingAs(User::factory()->owner()->create());
    $otherOwner = User::factory()->owner()->create();

    Livewire::test('admin.users')->call('toggleActive', $otherOwner->id)->assertOk();

    expect($otherOwner->refresh()->is_active)->toBeFalse();
});
