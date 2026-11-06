<?php

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerRole;
use App\Mail\VolunteerApplicationDecision;
use App\Models\Event;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('lists new applications by default', function () {
    VolunteerApplication::factory()->create(['full_name' => 'Akua New',  'status' => VolunteerApplicationStatus::New->value]);
    VolunteerApplication::factory()->create(['full_name' => 'Kojo Done', 'status' => VolunteerApplicationStatus::Approved->value]);

    Livewire::test('admin.volunteers')
        ->assertSee('Akua New')
        ->assertDontSee('Kojo Done');
});

it('approves an application + creates a Volunteer roster record', function () {
    $app = VolunteerApplication::factory()->create([
        'full_name' => 'Ama Hopeful',
        'email' => 'ama@example.test',
        'status' => VolunteerApplicationStatus::New->value,
    ]);

    Livewire::test('admin.volunteers')
        ->set("applicationRoleChoice.{$app->id}", VolunteerRole::Media->value)
        ->call('approve', $app->id)
        ->assertHasNoErrors();

    $app->refresh();
    expect($app->status)->toBe(VolunteerApplicationStatus::Approved)
        ->and($app->reviewer_id)->toBe(auth()->id())
        ->and($app->reviewed_at)->not->toBeNull();

    $volunteer = Volunteer::query()->where('email', 'ama@example.test')->first();
    expect($volunteer)->not->toBeNull()
        ->and($volunteer->full_name)->toBe('Ama Hopeful')
        ->and($volunteer->role)->toBe(VolunteerRole::Media);
});

it('refuses to approve without a role selection', function () {
    $app = VolunteerApplication::factory()->create([
        'status' => VolunteerApplicationStatus::New->value,
    ]);

    Livewire::test('admin.volunteers')
        ->set("applicationRoleChoice.{$app->id}", '')
        ->call('approve', $app->id)
        ->assertHasErrors('approve_'.$app->id);

    expect(Volunteer::query()->count())->toBe(0);
    expect($app->refresh()->status)->toBe(VolunteerApplicationStatus::New);
});

it('rejects an application without creating a roster record', function () {
    $app = VolunteerApplication::factory()->create([
        'status' => VolunteerApplicationStatus::New->value,
    ]);

    Livewire::test('admin.volunteers')
        ->call('reject', $app->id);

    expect($app->refresh()->status)->toBe(VolunteerApplicationStatus::Rejected);
    expect(Volunteer::query()->count())->toBe(0);
});

it('edits a volunteer role in the roster', function () {
    $volunteer = Volunteer::factory()->create(['role' => VolunteerRole::Event->value]);

    Livewire::test('admin.volunteers', ['tab' => 'roster'])
        ->set('tab', 'roster')
        ->call('startEditRole', $volunteer->id)
        ->set('editingRole', VolunteerRole::Administrative->value)
        ->call('saveRole', $volunteer->id)
        ->assertHasNoErrors();

    expect($volunteer->refresh()->role)->toBe(VolunteerRole::Administrative);
});

it('removes a volunteer from the roster', function () {
    $volunteer = Volunteer::factory()->create();

    Livewire::test('admin.volunteers')
        ->set('tab', 'roster')
        ->call('askRemove', $volunteer->id)
        ->call('remove', $volunteer->id);

    expect(Volunteer::query()->whereKey($volunteer->id)->exists())->toBeFalse();
});

it('filters the roster by role', function () {
    Volunteer::factory()->create(['full_name' => 'Event Person',   'role' => VolunteerRole::Event->value]);
    Volunteer::factory()->create(['full_name' => 'Media Person',   'role' => VolunteerRole::Media->value]);

    Livewire::test('admin.volunteers')
        ->set('tab', 'roster')
        ->set('rosterFilter', VolunteerRole::Media->value)
        ->assertSee('Media Person')
        ->assertDontSee('Event Person');
});

it('searches the roster by name or email', function () {
    Volunteer::factory()->create(['full_name' => 'Kweku Searchable', 'email' => 'kweku@example.test']);
    Volunteer::factory()->create(['full_name' => 'Someone Else',     'email' => 'other@example.test']);

    Livewire::test('admin.volunteers')
        ->set('tab', 'roster')
        ->set('rosterSearch', 'Kweku')
        ->assertSee('Kweku Searchable')
        ->assertDontSee('Someone Else');
});

it('refuses to reject an application that was already approved', function () {
    Mail::fake();

    $app = VolunteerApplication::factory()->create([
        'email' => 'ama@example.test',
        'status' => VolunteerApplicationStatus::New->value,
    ]);

    Livewire::test('admin.volunteers')
        ->set('applicationRoleChoice.'.$app->id, VolunteerRole::Event->value)
        ->call('approve', $app->id)
        ->call('reject', $app->id)
        ->assertSet('flashMessage', 'This application has already been reviewed.');

    expect($app->refresh()->status)->toBe(VolunteerApplicationStatus::Approved)
        ->and(Volunteer::query()->where('email', 'ama@example.test')->count())->toBe(1);

    Mail::assertQueued(VolunteerApplicationDecision::class, 1);
    Mail::assertNotQueued(
        VolunteerApplicationDecision::class,
        fn ($mail) => $mail->approved === false,
    );
});

it('refuses to approve an application that was already rejected', function () {
    Mail::fake();

    $app = VolunteerApplication::factory()->create([
        'email' => 'kofi@example.test',
        'status' => VolunteerApplicationStatus::Rejected->value,
    ]);

    Livewire::test('admin.volunteers')
        ->set('applicationRoleChoice.'.$app->id, VolunteerRole::Event->value)
        ->call('approve', $app->id);

    expect($app->refresh()->status)->toBe(VolunteerApplicationStatus::Rejected)
        ->and(Volunteer::query()->where('email', 'kofi@example.test')->exists())->toBeFalse();
    Mail::assertNothingQueued();
});

it('shows the event in the application details', function () {
    $event = Event::factory()->create(['title' => 'Volta Outreach']);
    $application = VolunteerApplication::factory()->create(['event_id' => $event->id]);

    Livewire::test('admin.volunteers')
        ->call('viewApplication', $application->id)
        ->assertSee('Volta Outreach');
});
