<?php

use App\Enums\EventStatus;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Mail\VolunteerApplicationReceived;
use App\Models\Event;
use App\Models\VolunteerApplication;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

it('renders the volunteer page with the application form', function () {
    $this->get(route('volunteer'))
        ->assertOk()
        ->assertSee('Lend your time')
        ->assertSee('Community outreach')
        ->assertSee('Weekdays');
});

it('accepts a valid volunteer application', function () {
    Livewire::test('site.volunteer-application-form')
        ->set('full_name', 'Ama Volunteer')
        ->set('email', 'ama@example.test')
        ->set('phone', '+233 24 000 0000')
        ->set('country', 'Ghana')
        ->set('interests', [VolunteerInterest::CommunityOutreach->value, VolunteerInterest::EducationPrograms->value])
        ->set('availability', VolunteerAvailability::Flexible->value)
        ->set('skills', 'Five years teaching primary school children.')
        ->set('motivation', 'I have lived in the Volta Region for two decades and want to give back to my community.')
        ->set('consent', true)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee('Thank you for applying.');

    $application = VolunteerApplication::query()->sole();

    expect($application->email)->toBe('ama@example.test')
        ->and($application->status)->toBe(VolunteerApplicationStatus::New)
        ->and($application->availability)->toBe(VolunteerAvailability::Flexible)
        ->and($application->interests)->toEqualCanonicalizing([
            VolunteerInterest::CommunityOutreach->value,
            VolunteerInterest::EducationPrograms->value,
        ])
        ->and($application->consented_at)->not->toBeNull();
});

it('requires consent', function () {
    Livewire::test('site.volunteer-application-form')
        ->set('full_name', 'Ama Volunteer')
        ->set('email', 'ama@example.test')
        ->set('phone', '+233 24 000 0000')
        ->set('country', 'Ghana')
        ->set('interests', [VolunteerInterest::CommunityOutreach->value])
        ->set('availability', VolunteerAvailability::Flexible->value)
        ->set('motivation', 'A motivation that is at least thirty characters long, easily.')
        ->set('consent', false)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasErrors(['consent' => 'accepted']);

    expect(VolunteerApplication::query()->count())->toBe(0);
});

it('rejects empty interests and invalid availability', function () {
    Livewire::test('site.volunteer-application-form')
        ->set('full_name', 'Ama Volunteer')
        ->set('email', 'ama@example.test')
        ->set('phone', '+233 24 000 0000')
        ->set('country', 'Ghana')
        ->set('interests', [])
        ->set('availability', 'not-a-valid-value')
        ->set('motivation', 'A motivation that is at least thirty characters long, easily.')
        ->set('consent', true)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasErrors(['interests', 'availability']);
});

it('shows the volunteer form beside the what happens next steps', function () {
    $this->get(route('volunteer'))
        ->assertOk()
        ->assertSeeInOrder(['What happens next', 'Send your application', 'We get in touch', 'Join an outreach', 'Apply to Volunteer']);
});

function filledVolunteerForm(array $props = [])
{
    $component = Livewire::test('site.volunteer-application-form', $props)
        ->set('full_name', 'Ama Volunteer')
        ->set('email', 'ama@example.test')
        ->set('phone', '+233 24 000 0000')
        ->set('country', 'Ghana')
        ->set('interests', [VolunteerInterest::CommunityOutreach->value])
        ->set('availability', VolunteerAvailability::Flexible->value)
        ->set('motivation', 'I have lived in the Volta Region for two decades and want to give back.')
        ->set('consent', true);
    test()->travel(5)->seconds();

    return $component;
}

it('saves the event a volunteer applied from and tells staff', function () {
    Mail::fake();
    $event = Event::factory()->create(['title' => 'Volta Outreach', 'status' => EventStatus::Published, 'published_at' => now()->subDay()]);

    filledVolunteerForm(['compact' => true, 'eventId' => $event->id])
        ->assertSee('Volta Outreach')
        ->call('submit')
        ->assertHasNoErrors();

    $application = VolunteerApplication::query()->sole();

    expect($application->event_id)->toBe($event->id);
    Mail::assertQueued(VolunteerApplicationReceived::class, function (VolunteerApplicationReceived $mail) {
        $mail->assertSeeInHtml('Volta Outreach');

        return true;
    });
});

it('ignores an event that is not published', function () {
    $draft = Event::factory()->draft()->create(['title' => 'Hidden Draft Event']);

    filledVolunteerForm(['compact' => true, 'eventId' => $draft->id])
        ->assertSet('eventId', null)
        ->assertDontSee('Hidden Draft Event')
        ->call('submit')
        ->assertHasNoErrors();

    expect(VolunteerApplication::query()->sole()->event_id)->toBeNull();
});

it('does not save an event unpublished after the form opened', function () {
    $event = Event::factory()->create(['status' => EventStatus::Published, 'published_at' => now()->subDay()]);
    $component = filledVolunteerForm(['compact' => true, 'eventId' => $event->id]);
    $event->update(['status' => EventStatus::Draft]);

    $component->call('submit')->assertHasNoErrors();

    expect(VolunteerApplication::query()->sole()->event_id)->toBeNull();
});

it('locks the event against browser tampering', function (string $property, mixed $value) {
    Livewire::test('site.volunteer-application-form')->set($property, $value);
})->with([
    ['eventId', 1],
    ['contextEventTitle', 'Fake event'],
])->throws(CannotUpdateLockedPropertyException::class);

it('keeps the application if its event is permanently deleted', function () {
    $event = Event::factory()->create();
    $application = VolunteerApplication::factory()->create(['event_id' => $event->id]);

    $event->forceDelete();

    expect($application->fresh()->event_id)->toBeNull();
});

it('gives every field a unique id when several forms share a page', function () {
    $first = Event::factory()->create(['status' => EventStatus::Published, 'published_at' => now()->subDay(), 'starts_at' => now()->addWeek()]);
    $second = Event::factory()->create(['status' => EventStatus::Published, 'published_at' => now()->subDay(), 'starts_at' => now()->addWeeks(2)]);

    $html = $this->get(route('events.index'))->assertOk()->assertSee($first->title)->assertSee($second->title)->getContent();

    preg_match_all('/\sid="([^"]+)"/', $html, $ids);
    preg_match_all('/\sfor="(vol-[^"]+)"/', $html, $labels);

    expect($ids[1])->not->toBeEmpty()
        ->and(array_diff_assoc($ids[1], array_unique($ids[1])))->toBe([])
        ->and(array_diff($labels[1], $ids[1]))->toBe([]);
});
