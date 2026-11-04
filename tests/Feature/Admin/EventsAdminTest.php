<?php

use App\Enums\EventStatus;
use App\Mail\EventVolunteerInvitation;
use App\Models\Donation;
use App\Models\Event;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
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

/**
 * Fill the create form with a valid event in the given status.
 */
function fillNewEvent(Testable $component, EventStatus $status): Testable
{
    return $component
        ->call('startCreate')
        ->set('title', 'Community Outreach')
        ->set('starts_at', now()->addWeeks(2)->toDateString())
        ->set('location', 'Volta')
        ->set('country', 'Ghana')
        ->set('description', 'A multi-day community outreach programme covering education and health.')
        ->set('status', $status->value)
        ->set('hero_image_upload', UploadedFile::fake()->image('program.jpg', 1080, 1080));
}

it('emails every active volunteer when an event is created as published', function () {
    Storage::fake('public');
    Mail::fake();

    Volunteer::factory()->create(['email' => 'alice@example.com']);
    Volunteer::factory()->create(['email' => 'bob@example.com']);

    fillNewEvent(Livewire::test('admin.events'), EventStatus::Published)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertQueued(EventVolunteerInvitation::class, 2);
    Mail::assertQueued(EventVolunteerInvitation::class, fn ($mail) => $mail->hasTo('alice@example.com'));
    Mail::assertQueued(EventVolunteerInvitation::class, fn ($mail) => $mail->hasTo('bob@example.com'));
    expect(Event::query()->first()->volunteers_notified_at)->not->toBeNull();
});

it('does not email volunteers when an event is created as a draft', function () {
    Storage::fake('public');
    Mail::fake();

    Volunteer::factory()->create(['email' => 'alice@example.com']);

    fillNewEvent(Livewire::test('admin.events'), EventStatus::Draft)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertNotQueued(EventVolunteerInvitation::class);
    expect(Event::query()->first()->volunteers_notified_at)->toBeNull();
});

it('emails volunteers when a draft is published, and only once', function () {
    Mail::fake();

    Volunteer::factory()->create(['email' => 'alice@example.com']);
    $event = Event::factory()->draft()->create();

    Livewire::test('admin.events')
        ->call('publish', $event->id)
        ->call('unpublish', $event->id)
        ->call('publish', $event->id);

    Mail::assertQueued(EventVolunteerInvitation::class, 1);
    expect($event->refresh()->volunteers_notified_at)->not->toBeNull();
});

it('emails volunteers when a draft is edited to published', function () {
    Storage::fake('public');
    Mail::fake();

    Volunteer::factory()->create(['email' => 'alice@example.com']);
    $event = Event::factory()->draft()->create();

    Livewire::test('admin.events')
        ->call('startEdit', $event->id)
        ->set('status', EventStatus::Published->value)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertQueued(EventVolunteerInvitation::class, 1);
    expect($event->refresh()->published_at)->not->toBeNull()
        ->and($event->published_at->lte(now()))->toBeTrue();
});

it('does not email volunteers when an already-notified event is edited', function () {
    Storage::fake('public');
    Mail::fake();

    Volunteer::factory()->create(['email' => 'alice@example.com']);
    $event = Event::factory()->create(['title' => 'Original', 'volunteers_notified_at' => now()->subDay()]);

    Livewire::test('admin.events')
        ->call('startEdit', $event->id)
        ->set('title', 'Updated')
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertNotQueued(EventVolunteerInvitation::class);
});

it('keeps the original published_at when a published event is edited or re-published', function () {
    Storage::fake('public');
    Mail::fake();

    $originalPublishedAt = now()->subDays(10)->startOfSecond();
    $event = Event::factory()->create([
        'published_at' => $originalPublishedAt,
        'volunteers_notified_at' => now()->subDays(10),
    ]);

    Livewire::test('admin.events')
        ->call('startEdit', $event->id)
        ->set('title', 'Renamed event')
        ->call('save')
        ->assertHasNoErrors()
        ->call('publish', $event->id);

    expect($event->refresh()->published_at->equalTo($originalPublishedAt))->toBeTrue();
});

it('rejects an invalid status value instead of saving it', function () {
    Storage::fake('public');

    fillNewEvent(Livewire::test('admin.events'), EventStatus::Draft)
        ->set('status', 'bogus')
        ->call('save')
        ->assertHasErrors(['status']);

    expect(Event::query()->count())->toBe(0);
});

it('ignores a tampered hero_image_path and never deletes another file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('beneficiaries/secret.jpg', 'x');
    $event = Event::factory()->create(['hero_image_path' => 'events/own.jpg']);
    Storage::disk('public')->put('events/own.jpg', 'x');

    $component = Livewire::test('admin.events')->call('startEdit', $event->id);

    expect(fn () => $component->set('hero_image_path', 'beneficiaries/secret.jpg'))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    $component->set('remove_hero_image', true)->call('save')->assertHasNoErrors();

    Storage::disk('public')->assertExists('beneficiaries/secret.jpg');
    Storage::disk('public')->assertMissing('events/own.jpg');
    expect($event->refresh()->hero_image_path)->toBeNull();
});

it('only counts base-currency donations towards an event goal', function () {
    $event = Event::factory()->create(['goal_cents' => 100000]);
    Donation::factory()->create(['event_id' => $event->id, 'amount_cents' => 25000, 'currency' => 'USD']);
    Donation::factory()->create(['event_id' => $event->id, 'amount_cents' => 90000, 'currency' => 'GHS']);

    expect($event->raisedCents())->toBe(25000)
        ->and($event->progressPercent())->toBe(25);

    $listed = Livewire::test('admin.events')->instance()->events->firstWhere('id', $event->id);

    expect((int) $listed->raised_cents)->toBe(25000);
});
