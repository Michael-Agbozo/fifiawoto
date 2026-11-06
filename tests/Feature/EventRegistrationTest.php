<?php

use App\Enums\EventStatus;
use App\Mail\EventRegistrationConfirmation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    $this->event = Event::factory()->create([
        'status' => EventStatus::Published,
        'published_at' => now()->subDay(),
        'starts_at' => now()->addWeek(),
        'ends_at' => null,
        'registration_enabled' => true,
    ]);
});

function signUp(Event $event, string $email = 'ama@example.org', int $partySize = 1)
{
    $component = Livewire::test('site.event-registration', ['event' => $event]);
    test()->travel(10)->seconds();

    return $component
        ->set('full_name', 'Ama Mensah')
        ->set('email', $email)
        ->set('party_size', $partySize)
        ->set('consent', true)
        ->call('submit');
}

it('shows the form only when sign-ups are open', function () {
    $this->get(route('events.show', $this->event))->assertOk()->assertSee('Sign up for this event');

    $this->event->update(['registration_enabled' => false]);
    $this->get(route('events.show', $this->event))->assertDontSee('Sign up for this event');

    $this->event->update(['registration_enabled' => true, 'starts_at' => now()->subWeek()]);
    $this->get(route('events.show', $this->event))->assertDontSee('Sign up for this event');
});

it('saves a sign-up and emails a confirmation', function () {
    signUp($this->event)->assertHasNoErrors()->assertSet('submitted', true);

    expect(EventRegistration::query()->sole()->email)->toBe('ama@example.org');
    Mail::assertQueued(EventRegistrationConfirmation::class);
});

it('does not double-book the same email and does not reveal it', function () {
    signUp($this->event);
    signUp($this->event, 'AMA@example.org')->assertHasNoErrors()->assertSet('submitted', true);

    expect(EventRegistration::query()->count())->toBe(1);
    Mail::assertQueued(EventRegistrationConfirmation::class, 1);
});

it('stops at capacity, counting everyone in a group', function () {
    $this->event->update(['capacity' => 3]);

    signUp($this->event, 'one@example.org', 2)->assertHasNoErrors();
    signUp($this->event, 'two@example.org', 2)->assertHasErrors('party_size');

    expect($this->event->seatsTaken())->toBe(2);

    signUp($this->event, 'three@example.org', 1)->assertHasNoErrors();
    $this->get(route('events.show', $this->event))->assertSee('This event is full');
});

it('silently drops bots', function () {
    Livewire::test('site.event-registration', ['event' => $this->event])
        ->set('website', 'spam')
        ->set('full_name', 'Bot')->set('email', 'bot@example.org')->set('consent', true)
        ->call('submit')
        ->assertSet('submitted', true);

    expect(EventRegistration::query()->count())->toBe(0);
});

it('lets staff see and remove sign-ups', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $registration = $this->event->registrations()->create(['full_name' => 'Kofi', 'email' => 'kofi@example.org']);

    Livewire::test('admin.events')
        ->call('showRegistrations', $this->event->id)
        ->assertSee('kofi@example.org')
        ->call('removeRegistration', $registration->id);

    expect(EventRegistration::query()->count())->toBe(0);
});

it('saves the sign-up settings on an event', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.events')
        ->call('startEdit', $this->event->id)
        ->set('registration_enabled', false)
        ->set('capacity', 50)
        ->call('save');

    expect($this->event->refresh())->registration_enabled->toBeFalse()->capacity->toBe(50);
});

it('tells the visitor when sign-ups closed while the form was open', function (array $change) {
    $component = Livewire::test('site.event-registration', ['event' => $this->event]);
    $this->travel(10)->seconds();
    $this->event->update($change);

    $component
        ->set('full_name', 'Ama Mensah')
        ->set('email', 'ama@example.org')
        ->set('party_size', 1)
        ->set('consent', true)
        ->call('submit')
        ->assertHasErrors('closed')
        ->assertSet('submitted', false)
        ->assertSee('Sign-ups for this event have closed')
        ->assertDontSee("You're on the list", false);

    expect(EventRegistration::query()->count())->toBe(0);
    Mail::assertNothingQueued();
})->with([
    'registration disabled' => [['registration_enabled' => false]],
    'event ended' => [['starts_at' => now()->subWeek(), 'ends_at' => null]],
    'event unpublished' => [['status' => EventStatus::Draft]],
]);

it('queues the confirmation in the language of the visitor', function () {
    app()->setLocale('fr');

    signUp($this->event);

    Mail::assertQueued(EventRegistrationConfirmation::class, fn (EventRegistrationConfirmation $mail) => $mail->locale === 'fr');
});

it('writes the confirmation in French with a French date', function () {
    $this->event->update(['title' => 'Journée Volta', 'starts_at' => '2030-03-15 10:00:00']);
    $registration = $this->event->registrations()->create([
        'full_name' => 'Ama Mensah',
        'email' => 'ama@example.org',
        'party_size' => 2,
        'consented_at' => now(),
    ]);

    $mail = (new EventRegistrationConfirmation($registration))->locale('fr');

    $mail->assertSeeInHtml('Votre inscription est confirmée')
        ->assertSeeInHtml('15 mars 2030')
        ->assertSeeInHtml('Avec toute notre gratitude')
        ->assertDontSeeInHtml('March');

    app()->setLocale('fr');
    expect($mail->envelope()->subject)->toBe('Votre inscription est confirmée : Journée Volta');
});
