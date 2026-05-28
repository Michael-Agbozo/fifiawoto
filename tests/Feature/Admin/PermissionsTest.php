<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Permissions;
use Livewire\Livewire;

it('grants role-derived permissions automatically', function () {
    $user = User::factory()->create(['role' => UserRole::FoundationStaff]);

    expect($user->canDo('beneficiaries', 'view'))->toBeTrue()
        ->and($user->canDo('beneficiaries', 'create'))->toBeTrue()
        ->and($user->canDo('donations', 'update'))->toBeTrue()
        ->and($user->canDo('users', 'create'))->toBeFalse()
        ->and($user->canDo('system_logs', 'view'))->toBeFalse();
});

it('grants every permission to owners', function () {
    $user = User::factory()->owner()->create();

    foreach (Permissions::keys() as $key) {
        expect($user->hasPermission($key))->toBeTrue();
    }
});

it('does not give Admin (super_admin) access to system logs by default', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->hasPermission('system_logs.view'))->toBeFalse()
        ->and($user->canDo('users', 'create'))->toBeTrue()
        ->and($user->canDo('beneficiaries', 'delete'))->toBeTrue();
});

it('grants only view-level access when only view perms are stored', function () {
    $user = User::factory()->create([
        'role' => UserRole::Volunteer,
        'permissions' => ['beneficiaries.view', 'donations.view'],
    ]);

    expect($user->canDo('beneficiaries', 'view'))->toBeTrue()
        ->and($user->canDo('beneficiaries', 'create'))->toBeFalse()
        ->and($user->canDo('beneficiaries', 'update'))->toBeFalse()
        ->and($user->canDo('beneficiaries', 'delete'))->toBeFalse()
        ->and($user->canDo('donations', 'view'))->toBeTrue()
        ->and($user->canDo('donations', 'delete'))->toBeFalse();
});

it('sanitizes unknown permission keys', function () {
    expect(Permissions::sanitize(['beneficiaries.view', 'not_a_real.thing', 'donations.create']))
        ->toEqualCanonicalizing(['beneficiaries.view', 'donations.create']);
});

it('allows super admin to grant a view-only permission via the admin UI', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $target = User::factory()->create([
        'role' => UserRole::Volunteer,
        'permissions' => null,
    ]);

    Livewire::test('admin.users')
        ->call('startEditPermissions', $target->id)
        ->set('editPermissionsValues', ['reports.view'])
        ->call('savePermissions', $target->id);

    expect($target->refresh()->permissions)->toBe(['reports.view'])
        ->and($target->canDo('reports', 'view'))->toBeTrue()
        ->and($target->canDo('reports', 'export'))->toBeFalse();
});
