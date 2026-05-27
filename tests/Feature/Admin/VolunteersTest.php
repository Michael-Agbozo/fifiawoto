<?php

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerRole;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
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
