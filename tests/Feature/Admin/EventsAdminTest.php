<?php

use App\Enums\EventStatus;
use App\Mail\EventVolunteerInvitation;
use App\Models\Event;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create()));

it('creates a new event', function () {
    Storage::fake('public');

    Livewire::test('admin.events')
        ->call('startCreate')
        ->set('title', 'Community Day')
        ->set('starts_at', now()->addWeeks(2)->toDateString())
        ->set('location', 'Volta')
        ->set('country', 'Ghana')
        ->set('description', 'A multi-day community outreach programme covering education and health.')
        ->set('status', EventStatus::Draft->value)
        ->set('hero_image_upload', UploadedFile::fake()->image('program.jpg', 1080, 1080))
        ->call('save')
        ->assertHasNoErrors();

    expect(Event::query()->where('title', 'Community Day')->exists())->toBeTrue();
});

it('rejects a program image that is not 1080x1080', function () {
    Storage::fake('public');

    Livewire::test('admin.events')
        ->call('startCreate')
        ->set('title', 'Community Day')
        ->set('starts_at', now()->addWeeks(2)->toDateString())
        ->set('location', 'Volta')
        ->set('country', 'Ghana')
        ->set('description', 'A multi-day community outreach programme covering education and health.')
        ->set('status', EventStatus::Draft->value)
        ->set('hero_image_upload', UploadedFile::fake()->image('program.jpg', 800, 800))
        ->call('save')
        ->assertHasErrors(['hero_image_upload']);

    expect(Event::query()->where('title', 'Community Day')->exists())->toBeFalse();
});

it('requires a program image when creating', function () {
    Livewire::test('admin.events')
        ->call('startCreate')
        ->set('title', 'Community Day')
        ->set('starts_at', now()->addWeeks(2)->toDateString())
        ->set('location', 'Volta')
        ->set('country', 'Ghana')
        ->set('description', 'A multi-day community outreach programme covering education and health.')
        ->set('status', EventStatus::Draft->value)
        ->call('save')
        ->assertHasErrors(['hero_image_upload']);
});

it('publishes a draft event', function () {
    $e = Event::factory()->draft()->create();

    Livewire::test('admin.events')->call('publish', $e->id);

    expect($e->refresh()->status)->toBe(EventStatus::Published);
});

it('archives an event', function () {
    $e = Event::factory()->create();

    Livewire::test('admin.events')->call('archive', $e->id);

    expect($e->refresh()->status)->toBe(EventStatus::Archived);
});

it('emails every active volunteer when a new event is created', function () {
    Storage::fake('public');
    Mail::fake();

    Volunteer::factory()->create(['email' => 'alice@example.com']);
    Volunteer::factory()->create(['email' => 'bob@example.com']);

    Livewire::test('admin.events')
        ->call('startCreate')
        ->set('title', 'Community Outreach')
        ->set('starts_at', now()->addWeeks(2)->toDateString())
        ->set('location', 'Volta')
        ->set('country', 'Ghana')
        ->set('description', 'A multi-day community outreach programme covering education and health.')
        ->set('status', EventStatus::Draft->value)
        ->set('hero_image_upload', UploadedFile::fake()->image('program.jpg', 1080, 1080))
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertQueued(EventVolunteerInvitation::class, 2);
    Mail::assertQueued(EventVolunteerInvitation::class, fn ($mail) => $mail->hasTo('alice@example.com'));
    Mail::assertQueued(EventVolunteerInvitation::class, fn ($mail) => $mail->hasTo('bob@example.com'));
});

it('does not email volunteers when an existing event is edited', function () {
    Storage::fake('public');
    Mail::fake();

    Volunteer::factory()->create(['email' => 'alice@example.com']);
    $event = Event::factory()->create(['title' => 'Original']);

    Livewire::test('admin.events')
        ->call('startEdit', $event->id)
        ->set('title', 'Updated')
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertNotQueued(EventVolunteerInvitation::class);
});
