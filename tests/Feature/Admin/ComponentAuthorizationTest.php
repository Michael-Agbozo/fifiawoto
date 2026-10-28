<?php

use App\Enums\ContactMessageStatus;
use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\User;
use Livewire\Livewire;

/**
 * Every admin Livewire component must refuse to mount for a user who lacks
 * "{resource}.view", independent of the route middleware.
 */
it('refuses to mount an admin component without the view permission', function (string $component, UserRole $role) {
    $this->actingAs(User::factory()->withRole($role)->create());

    Livewire::test($component)->assertForbidden();
})->with([
    'beneficiaries / media manager' => ['admin.beneficiaries', UserRole::MediaManager],
    'beneficiary applications / coordinator' => ['admin.beneficiary-applications', UserRole::VolunteerCoordinator],
    'events / volunteer' => ['admin.events', UserRole::Volunteer],
    'testimonials / coordinator' => ['admin.testimonials', UserRole::VolunteerCoordinator],
    'leaders / coordinator' => ['admin.leaders', UserRole::VolunteerCoordinator],
    'media / coordinator' => ['admin.media', UserRole::VolunteerCoordinator],
    'inbox / volunteer' => ['admin.inbox', UserRole::Volunteer],
    'volunteers / media manager' => ['admin.volunteers', UserRole::MediaManager],
    'instagram / coordinator' => ['admin.instagram', UserRole::VolunteerCoordinator],
]);

it('mounts admin components for a role with the view permission', function (string $component) {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test($component)->assertOk();
})->with([
    'admin.beneficiaries', 'admin.beneficiary-applications', 'admin.events',
    'admin.testimonials', 'admin.leaders', 'admin.media', 'admin.inbox',
    'admin.volunteers', 'admin.instagram',
]);

it('blocks inbox status changes without inbox.reply', function (string $method) {
    // Media managers have inbox.view but not inbox.reply.
    $this->actingAs(User::factory()->mediaManager()->create());
    $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::InProgress->value]);

    Livewire::test('admin.inbox')->call($method, $message->id)->assertForbidden();

    expect($message->refresh()->status)->toBe(ContactMessageStatus::InProgress);
})->with(['markResolved', 'markNew', 'archive']);

it('hides inbox status actions from users without inbox.reply', function () {
    $this->actingAs(User::factory()->mediaManager()->create());
    $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::InProgress->value]);

    Livewire::test('admin.inbox')
        ->set('statusFilter', '')
        ->call('openMessage', $message->id)
        ->assertDontSeeHtml('wire:click="markResolved(')
        ->assertDontSeeHtml('wire:click="markNew(')
        ->assertDontSeeHtml('wire:click="archive(');
});

it('lets users with inbox.reply change message status', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::InProgress->value]);

    Livewire::test('admin.inbox')
        ->set('statusFilter', '')
        ->call('openMessage', $message->id)
        ->assertSeeHtml('wire:click="markResolved(')
        ->call('markResolved', $message->id)
        ->call('archive', $message->id);

    expect($message->refresh()->status)->toBe(ContactMessageStatus::Archived);
});

it('does not flip a new message to in-progress when a view-only user opens it', function () {
    $this->actingAs(User::factory()->mediaManager()->create());
    $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New->value]);

    Livewire::test('admin.inbox')->call('openMessage', $message->id);

    expect($message->refresh()->status)->toBe(ContactMessageStatus::New);
});
