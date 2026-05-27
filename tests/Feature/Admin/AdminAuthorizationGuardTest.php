<?php

use App\Enums\BeneficiaryApplicationStatus;
use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerRole;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\Donation;
use App\Models\Event;
use App\Models\InstagramPost;
use App\Models\Leader;
use App\Models\MediaItem;
use App\Models\NewsletterSubscriber;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use Livewire\Livewire;

/**
 * Locks in the rule that every admin Livewire mutation method calls canDo()
 * server-side, not just UI-gates the button. We log in as a role that does NOT
 * have the relevant permission, call the method directly, and assert the data
 * is untouched (Livewire's abort() handler returns a 403 response instead of
 * propagating the exception, so we check the actual security property: nothing
 * was mutated).
 *
 * If you add a new admin mutation method without an abort_unless(canDo) guard,
 * one of these assertions will fail.
 */
it('leaders::save is blocked without leaders.create', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());

    expect(Leader::query()->count())->toBe(0);

    Livewire::test('admin.leaders')
        ->call('startCreate')
        ->set('name', 'Sneak')
        ->set('role', 'Director')
        ->set('sort', 0)
        ->set('is_published', true)
        ->call('save');

    expect(Leader::query()->count())->toBe(0);
});

it('leaders::togglePublished is blocked without leaders.update', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $leader = Leader::factory()->create(['is_published' => true]);

    Livewire::test('admin.leaders')->call('togglePublished', $leader->id);

    expect($leader->refresh()->is_published)->toBeTrue();
});

it('leaders::delete is blocked without leaders.delete', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $leader = Leader::factory()->create();

    Livewire::test('admin.leaders')->call('delete', $leader->id);

    expect(Leader::query()->whereKey($leader->id)->exists())->toBeTrue();
});

it('testimonials::save is blocked without testimonials.create', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());

    Livewire::test('admin.testimonials')
        ->call('startCreate')
        ->set('author_name', 'X')
        ->set('author_role', 'Volunteer')
        ->set('quote', 'A long-enough quote for validation.')
        ->call('save');

    expect(Testimonial::query()->count())->toBe(0);
});

it('testimonials::toggleFeatured is blocked without testimonials.update', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $t = Testimonial::factory()->create(['featured' => false]);

    Livewire::test('admin.testimonials')->call('toggleFeatured', $t->id);

    expect($t->refresh()->featured)->toBeFalse();
});

it('testimonials::delete is blocked without testimonials.delete', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $t = Testimonial::factory()->create();

    Livewire::test('admin.testimonials')->call('delete', $t->id);

    expect(Testimonial::query()->whereKey($t->id)->exists())->toBeTrue();
});

it('media::delete is blocked without media.delete', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $m = MediaItem::factory()->create();

    Livewire::test('admin.media')->call('delete', $m->id);

    expect(MediaItem::query()->whereKey($m->id)->exists())->toBeTrue();
});

it('instagram::toggleApprove is blocked without instagram.update', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $post = InstagramPost::factory()->create(['is_approved' => false]);

    Livewire::test('admin.instagram')->call('toggleApprove', $post->id);

    expect($post->refresh()->is_approved)->toBeFalse();
});

it('instagram::delete is blocked without instagram.delete', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $post = InstagramPost::factory()->create();

    Livewire::test('admin.instagram')->call('delete', $post->id);

    expect(InstagramPost::query()->whereKey($post->id)->exists())->toBeTrue();
});

it('volunteers::saveRole is blocked without volunteers.update', function () {
    // Foundation Staff has only volunteers.view.
    $this->actingAs(User::factory()->foundationStaff()->create());
    $v = Volunteer::factory()->create(['role' => VolunteerRole::Event->value]);

    Livewire::test('admin.volunteers')
        ->set('editingVolunteerId', $v->id)
        ->set('editingRole', VolunteerRole::Administrative->value)
        ->call('saveRole', $v->id);

    expect($v->refresh()->role->value)->toBe(VolunteerRole::Event->value);
});

it('volunteers::remove is blocked without volunteers.delete', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $v = Volunteer::factory()->create();

    Livewire::test('admin.volunteers')->call('remove', $v->id);

    expect(Volunteer::query()->whereKey($v->id)->exists())->toBeTrue();
});

it('volunteers::approve is blocked without volunteers.update', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $app = VolunteerApplication::factory()->create([
        'status' => VolunteerApplicationStatus::New->value,
    ]);

    Livewire::test('admin.volunteers')
        ->set('applicationRoleChoice', [$app->id => VolunteerRole::Event->value])
        ->call('approve', $app->id);

    expect($app->refresh()->status->value)
        ->toBe(VolunteerApplicationStatus::New->value);
});

it('users::saveRole is blocked without users.update', function () {
    // Foundation Staff has zero users.* permissions.
    $this->actingAs(User::factory()->foundationStaff()->create());
    $target = User::factory()->volunteerCoordinator()->create();

    Livewire::test('admin.users')
        ->set('editRoleValue', UserRole::Owner->value)
        ->call('saveRole', $target->id);

    expect($target->refresh()->role->value)
        ->toBe(UserRole::VolunteerCoordinator->value);
});

it('users::savePermissions is blocked without users.update', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $target = User::factory()->volunteerCoordinator()->create();

    Livewire::test('admin.users')
        ->set('editPermissionsValues', ['users.delete'])
        ->call('savePermissions', $target->id);

    expect($target->refresh()->permissions)->toBeNull();
});

it('users::resetPassword is blocked without users.update', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $target = User::factory()->volunteerCoordinator()->create();

    $originalHash = $target->password;

    Livewire::test('admin.users')->call('resetPassword', $target->id);

    expect($target->refresh()->password)->toBe($originalHash);
});

it('users::delete is blocked without users.delete', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $target = User::factory()->volunteerCoordinator()->create();

    Livewire::test('admin.users')->call('delete', $target->id);

    expect(User::query()->whereKey($target->id)->exists())->toBeTrue();
});

it('events::publish is blocked without events.update', function () {
    // Media Manager has only events.view.
    $this->actingAs(User::factory()->mediaManager()->create());
    $e = Event::factory()->create(['status' => EventStatus::Draft->value]);

    Livewire::test('admin.events')->call('publish', $e->id);

    expect($e->refresh()->status->value)->toBe(EventStatus::Draft->value);
});

it('events::delete is blocked without events.delete', function () {
    $this->actingAs(User::factory()->mediaManager()->create());
    $e = Event::factory()->create();

    Livewire::test('admin.events')->call('delete', $e->id);

    expect(Event::query()->whereKey($e->id)->exists())->toBeTrue();
});

it('donations::delete is blocked without donations.delete', function () {
    $this->actingAs(User::factory()->mediaManager()->create());
    $d = Donation::factory()->create();

    Livewire::test('admin.donations')->call('delete', $d->id);

    expect(Donation::query()->whereKey($d->id)->exists())->toBeTrue();
});

it('newsletter::delete is blocked without newsletter.delete', function () {
    // Volunteer Coordinator has zero newsletter.* permissions.
    $this->actingAs(User::factory()->volunteerCoordinator()->create());

    $sub = NewsletterSubscriber::query()->forceCreate([
        'email' => 'sub@example.com',
        'name' => 'Sub',
        'source' => 'home',
        'subscribed_at' => now(),
    ]);

    Livewire::test('admin.newsletter')->call('delete', $sub->id);

    expect(NewsletterSubscriber::query()->whereKey($sub->id)->exists())->toBeTrue();
});

it('beneficiary-applications mutations are blocked without permission', function () {
    $this->actingAs(User::factory()->mediaManager()->create());
    $app = BeneficiaryApplication::factory()->create([
        'status' => BeneficiaryApplicationStatus::New->value,
    ]);

    Livewire::test('admin.beneficiary-applications')->call('setStatus', $app->id, 'approved');
    expect($app->refresh()->status->value)
        ->toBe(BeneficiaryApplicationStatus::New->value);

    Livewire::test('admin.beneficiary-applications')->call('delete', $app->id);
    expect(BeneficiaryApplication::query()->whereKey($app->id)->exists())->toBeTrue();
});

it('beneficiaries::delete is blocked without beneficiaries.delete', function () {
    $this->actingAs(User::factory()->mediaManager()->create());
    $b = Beneficiary::factory()->create();

    Livewire::test('admin.beneficiaries')->call('delete', $b->id);

    expect(Beneficiary::query()->whereKey($b->id)->exists())->toBeTrue();
});
