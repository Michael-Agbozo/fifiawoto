<?php

use App\Enums\UserRole;
use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

it('lands /admin straight on /admin/dashboard', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect(route('admin.dashboard'));
});

it('lets every admin-class role into the dashboard', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Overview');
})->with(UserRole::adminRoles());

it('blocks a plain volunteer from the admin area', function () {
    $volunteer = User::factory()->withRole(UserRole::Volunteer)->create();

    $this->actingAs($volunteer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('redirects an admin landing on /dashboard to /admin/dashboard', function () {
    $admin = User::factory()->foundationStaff()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

it('keeps a non-admin on /dashboard', function () {
    $volunteer = User::factory()->withRole(UserRole::Volunteer)->create();

    $this->actingAs($volunteer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome back, '.$volunteer->name);
});
