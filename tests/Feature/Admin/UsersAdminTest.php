<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
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

it('forbids the users panel without users.view', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());

    Livewire::test('admin.users')->assertForbidden();
});

it('does not let a non-owner promote anyone to owner', function () {
    $target = User::factory()->withRole(UserRole::FoundationStaff)->create();

    Livewire::test('admin.users')
        ->call('startEditRole', $target->id)
        ->set('editRoleValue', UserRole::Owner->value)
        ->call('saveRole', $target->id)
        ->assertHasErrors('role_'.$target->id);

    expect($target->refresh()->role)->toBe(UserRole::FoundationStaff);
});

it('does not let a non-owner invite an owner', function () {
    Mail::fake();

    Livewire::test('admin.users')
        ->call('startInvite')
        ->set('name', 'Sneaky Owner')
        ->set('email', 'sneaky@example.test')
        ->set('role', UserRole::Owner->value)
        ->call('invite')
        ->assertHasErrors('role');

    expect(User::query()->where('email', 'sneaky@example.test')->exists())->toBeFalse();
});

it('only offers assignable roles in the role dropdowns', function () {
    expect(Livewire::test('admin.users')->viewData('roleOptions'))
        ->not->toHaveKey(UserRole::Owner->value)
        ->toHaveKey(UserRole::SuperAdmin->value);

    $this->actingAs(User::factory()->owner()->create());

    expect(Livewire::test('admin.users')->viewData('roleOptions'))->toHaveKey(UserRole::Owner->value);
});

it('lets an owner promote another user to owner', function () {
    $this->actingAs(User::factory()->owner()->create());
    $target = User::factory()->superAdmin()->create();

    Livewire::test('admin.users')
        ->call('startEditRole', $target->id)
        ->set('editRoleValue', UserRole::Owner->value)
        ->call('saveRole', $target->id)
        ->assertHasNoErrors();

    expect($target->refresh()->role)->toBe(UserRole::Owner);
});

it('blocks changing your own role, even upwards', function () {
    $me = auth()->user();

    Livewire::test('admin.users')
        ->set('editRoleValue', UserRole::Owner->value)
        ->call('saveRole', $me->id)
        ->assertHasErrors('role_'.$me->id);

    expect($me->refresh()->role)->toBe(UserRole::SuperAdmin);
});

it('blocks editing your own permissions', function () {
    $me = auth()->user();

    Livewire::test('admin.users')->call('startEditPermissions', $me->id)->assertForbidden();
    Livewire::test('admin.users')
        ->set('editPermissionsValues', ['system_logs.view'])
        ->call('savePermissions', $me->id)
        ->assertForbidden();

    expect($me->refresh()->permissions)->toBeNull();
});

it('requires users.update to open the permissions editor', function () {
    $viewer = User::factory()->foundationStaff()->create();
    $viewer->forceFill(['permissions' => ['users.view']])->save();
    $target = User::factory()->create();
    $this->actingAs($viewer);

    Livewire::test('admin.users')
        ->call('startEditPermissions', $target->id)
        ->assertForbidden();
});

it('locks id-holding properties against client tampering', function (string $property) {
    $target = User::factory()->create();

    Livewire::test('admin.users')->set($property, $target->id);
})->with([
    'resetPasswordUserId',
    'editRoleId',
    'editPermissionsId',
    'confirmDeleteId',
    'confirmDisable2faId',
])->throws(CannotUpdateLockedPropertyException::class);

it('applies the default password rules when setting a password', function () {
    Http::fake();
    app()->detectEnvironment(fn () => 'production');
    $target = User::factory()->create();

    Livewire::test('admin.users')
        ->call('startResetPassword', $target->id)
        ->set('newPassword', 'password1')
        ->set('newPasswordConfirm', 'password1')
        ->call('saveResetPassword')
        ->assertHasErrors('newPassword');
});

it('never demotes, deactivates or deletes the last active owner', function () {
    $lastOwner = User::factory()->owner()->create();
    // An owner whose account was deactivated mid-session is not counted as "active".
    $this->actingAs(User::factory()->owner()->create(['is_active' => false]));

    Livewire::test('admin.users')
        ->call('startEditRole', $lastOwner->id)
        ->set('editRoleValue', UserRole::SuperAdmin->value)
        ->call('saveRole', $lastOwner->id)
        ->assertHasErrors('role_'.$lastOwner->id);

    Livewire::test('admin.users')->call('toggleActive', $lastOwner->id);

    Livewire::test('admin.users')
        ->call('delete', $lastOwner->id)
        ->assertHasErrors('delete_'.$lastOwner->id);

    $lastOwner->refresh();
    expect($lastOwner->role)->toBe(UserRole::Owner)
        ->and($lastOwner->is_active)->toBeTrue();
});

it('still lets an owner demote another owner when one stays active', function () {
    $this->actingAs(User::factory()->owner()->create());
    $otherOwner = User::factory()->owner()->create();

    Livewire::test('admin.users')
        ->call('startEditRole', $otherOwner->id)
        ->set('editRoleValue', UserRole::SuperAdmin->value)
        ->call('saveRole', $otherOwner->id)
        ->assertHasNoErrors();

    expect($otherOwner->refresh()->role)->toBe(UserRole::SuperAdmin);
});

it('does not generate a new password for your own account', function () {
    $originalHash = auth()->user()->password;

    Livewire::test('admin.users')
        ->call('resetPassword', auth()->id())
        ->assertForbidden();

    expect(auth()->user()->fresh()->password)->toBe($originalHash);
});

function storeSessionFor(User $user, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);
}

it('makes an invited user choose a new password', function () {
    Mail::fake();

    Livewire::test('admin.users')
        ->set('name', 'New Hire')
        ->set('email', 'new-hire@example.com')
        ->set('role', UserRole::FoundationStaff->value)
        ->call('invite')
        ->assertHasNoErrors();

    expect(User::query()->where('email', 'new-hire@example.com')->sole()->force_password_change)->toBeTrue();
});

it('asks for confirmation before a one-click password reset', function () {
    User::factory()->create(['name' => 'Target Person']);

    Livewire::test('admin.users')
        ->assertSeeHtml('wire:confirm="Reset the password for Target Person?');
});

it('makes a one-click reset user choose a new password and signs them out everywhere', function () {
    config(['session.driver' => 'database']);
    $target = User::factory()->create(['remember_token' => 'old-token']);
    $bystander = User::factory()->create();
    storeSessionFor($target, 'target-session');
    storeSessionFor($bystander, 'bystander-session');

    Livewire::test('admin.users')->call('resetPassword', $target->id);

    $target->refresh();
    expect($target->force_password_change)->toBeTrue()
        ->and($target->remember_token)->not->toBe('old-token')
        ->and(DB::table('sessions')->pluck('id')->all())->toBe(['bystander-session']);
});

it('signs a user out everywhere when an admin sets their password', function () {
    config(['session.driver' => 'database']);
    $target = User::factory()->create();
    storeSessionFor($target, 'target-session');

    Livewire::test('admin.users')
        ->call('startResetPassword', $target->id)
        ->set('newPassword', 'a-Str0ng-Passw0rd!-x9')
        ->set('newPasswordConfirm', 'a-Str0ng-Passw0rd!-x9')
        ->call('saveResetPassword')
        ->assertHasNoErrors();

    expect(DB::table('sessions')->count())->toBe(0);
});

it('signs a user out everywhere when deactivated, but not when reactivated', function () {
    config(['session.driver' => 'database']);
    $target = User::factory()->create();
    storeSessionFor($target, 'target-session');

    $component = Livewire::test('admin.users')->call('toggleActive', $target->id);
    expect(DB::table('sessions')->count())->toBe(0);

    storeSessionFor($target, 'after-deactivation');
    $component->call('toggleActive', $target->id);
    expect(DB::table('sessions')->count())->toBe(1);
});

it('leaves sessions alone on other session drivers', function () {
    config(['session.driver' => 'array']);
    $target = User::factory()->create();
    storeSessionFor($target, 'target-session');

    Livewire::test('admin.users')->call('resetPassword', $target->id);

    expect(DB::table('sessions')->count())->toBe(1);
});

it('reports the forced password change in the message after saving a reset', function () {
    $target = User::factory()->create();

    Livewire::test('admin.users')
        ->call('startResetPassword', $target->id)
        ->set('newPassword', 'a-Str0ng-Passw0rd!-x9')
        ->set('newPasswordConfirm', 'a-Str0ng-Passw0rd!-x9')
        ->set('forcePasswordChange', true)
        ->call('saveResetPassword')
        ->assertHasNoErrors()
        ->assertSet('forcePasswordChange', false)
        ->assertSet('flashMessage', "Password updated for {$target->name}. They will be asked to set a new password on next login.");
});

it('removes an uploaded avatar when an admin deletes the account, and leaves remote ones', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/target.jpg', 'image');
    $target = User::factory()->create(['avatar_path' => 'avatars/target.jpg']);
    $remote = User::factory()->create(['avatar_path' => 'https://cdn.example.com/a.jpg']);

    Livewire::test('admin.users')->call('delete', $target->id)->call('delete', $remote->id);

    expect(User::query()->whereKey([$target->id, $remote->id])->exists())->toBeFalse();
    Storage::disk('public')->assertMissing('avatars/target.jpg');
});
