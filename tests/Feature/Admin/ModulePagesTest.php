<?php

use App\Enums\UserRole;
use App\Models\User;

it('renders every admin module index for a super admin', function (string $route, string $expectedHeading) {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route($route))
        ->assertOk()
        ->assertSee($expectedHeading);
})->with([
    'beneficiaries' => ['admin.beneficiaries.index',            'Beneficiaries'],
    'beneficiary applications' => ['admin.beneficiary-applications.index', 'Beneficiary applications'],
    'volunteers' => ['admin.volunteers.index',               'Volunteers'],
    'testimonials' => ['admin.testimonials.index',             'Testimonials'],
    'events' => ['admin.events.index',                   'Events'],
    'donations' => ['admin.donations.index',                'Donations'],
    'media' => ['admin.media.index',                    'Media gallery'],
    'instagram' => ['admin.instagram.index',                'Instagram sync'],
    'reports' => ['admin.reports.index',                  'Reports'],
    'users' => ['admin.users.index',                    'User management'],
    'newsletter' => ['admin.newsletter.index',          'Newsletter subscribers'],
    'inbox' => ['admin.inbox.index',                    'Email inbox'],
]);

it('gates beneficiaries to Foundation Staff but blocks Media Manager', function () {
    $allowed = User::factory()->foundationStaff()->create();
    $blocked = User::factory()->mediaManager()->create();

    $this->actingAs($allowed)->get(route('admin.beneficiaries.index'))->assertOk();
    $this->actingAs($blocked)->get(route('admin.beneficiaries.index'))->assertForbidden();
});

it('gates volunteers to Volunteer Coordinator but blocks Foundation Staff', function () {
    $allowed = User::factory()->volunteerCoordinator()->create();
    $blocked = User::factory()->foundationStaff()->create();

    $this->actingAs($allowed)->get(route('admin.volunteers.index'))->assertOk();
    $this->actingAs($blocked)->get(route('admin.volunteers.index'))->assertForbidden();
});

it('opens Media & Website pages for Media Manager and Foundation Staff but blocks Volunteer Coordinator', function (string $route) {
    $mediaManager = User::factory()->mediaManager()->create();
    $foundationStaff = User::factory()->foundationStaff()->create();
    $blocked = User::factory()->volunteerCoordinator()->create();

    $this->actingAs($mediaManager)->get(route($route))->assertOk();
    $this->actingAs($foundationStaff)->get(route($route))->assertOk();
    $this->actingAs($blocked)->get(route($route))->assertForbidden();
})->with([
    'admin.testimonials.index',
    'admin.media.index',
    'admin.instagram.index',
    'admin.newsletter.index',
]);

it('opens the email inbox to every admin role except Volunteer', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get(route('admin.inbox.index'))->assertOk();
})->with(UserRole::adminRoles());

it('gates user management to Owner and Admin only', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    if (in_array($role, [UserRole::Owner, UserRole::SuperAdmin], true)) {
        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
    } else {
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }
})->with(UserRole::adminRoles());

it('gates system logs to Owner only', function (UserRole $role) {
    $user = User::factory()->withRole($role)->create();

    if ($role === UserRole::Owner) {
        $this->actingAs($user)->get(route('admin.system-logs.index'))->assertOk();
    } else {
        $this->actingAs($user)->get(route('admin.system-logs.index'))->assertForbidden();
    }
})->with(UserRole::adminRoles());

it('redirects guests away from every module route', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'admin.beneficiaries.index',
    'admin.beneficiary-applications.index',
    'admin.volunteers.index',
    'admin.testimonials.index',
    'admin.events.index',
    'admin.donations.index',
    'admin.media.index',
    'admin.instagram.index',
    'admin.reports.index',
    'admin.users.index',
    'admin.system-logs.index',
    'admin.newsletter.index',
    'admin.inbox.index',
]);
