<?php

use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use App\Enums\EventStatus;
use App\Mail\ContactFormReceived;
use App\Models\ContactMessage;
use App\Models\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('renders the contact page with the form and subject options', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Contact the Foundation')
        ->assertSee('General Inquiry')
        ->assertSee('Volunteer Information')
        ->assertSee('Donation Inquiry')
        ->assertSee('Partnership Opportunity');
});

it('persists a valid contact submission', function () {
    Livewire::test('site.contact-form')
        ->set('full_name', 'Kojo Donor')
        ->set('email', 'kojo@example.test')
        ->set('phone', '+1 555 555 5555')
        ->set('subject', ContactSubject::Partnership->value)
        ->set('message', 'We would like to explore a multi-year partnership focused on education.')
        ->set('consent', true)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee('Message received.');

    $message = ContactMessage::query()->sole();

    expect($message->email)->toBe('kojo@example.test')
        ->and($message->subject)->toBe(ContactSubject::Partnership)
        ->and($message->status)->toBe(ContactMessageStatus::New)
        ->and($message->consented_at)->not->toBeNull();
});

it('requires a valid subject choice', function () {
    Livewire::test('site.contact-form')
        ->set('full_name', 'Kojo Donor')
        ->set('email', 'kojo@example.test')
        ->set('subject', 'not-a-real-subject')
        ->set('message', 'A meaningful message at least fifteen chars.')
        ->set('consent', true)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasErrors(['subject']);

    expect(ContactMessage::query()->count())->toBe(0);
});

it('requires consent', function () {
    Livewire::test('site.contact-form')
        ->set('full_name', 'Kojo Donor')
        ->set('email', 'kojo@example.test')
        ->set('subject', ContactSubject::General->value)
        ->set('message', 'A meaningful message at least fifteen chars.')
        ->set('consent', false)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasErrors(['consent' => 'accepted']);
});

it('carries a published event from the link into the message', function () {
    Mail::fake();
    $event = Event::factory()->create(['title' => 'Volta Outreach', 'slug' => 'volta-outreach', 'status' => EventStatus::Published, 'published_at' => now()->subDay()]);

    $this->get(route('contact', ['subject' => 'donation', 'event' => 'volta-outreach']))->assertOk()->assertSee('Volta Outreach');

    Livewire::withQueryParams(['event' => $event->slug])
        ->test('site.contact-form')
        ->assertSee('Volta Outreach')
        ->set('full_name', 'Kojo Donor')
        ->set('email', 'kojo@example.test')
        ->set('subject', ContactSubject::Donation->value)
        ->set('message', 'I would like to give towards this outreach.')
        ->set('consent', true)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasNoErrors();

    expect(ContactMessage::query()->sole()->message)->toStartWith("Event: Volta Outreach\n\n");
    Mail::assertQueued(ContactFormReceived::class, fn (ContactFormReceived $mail) => str_contains($mail->messageBody, 'Volta Outreach'));
});

it('ignores an event link that is not published', function (mixed $slug) {
    Event::factory()->draft()->create(['title' => 'Hidden Draft Event', 'slug' => 'hidden-draft']);

    $this->get(route('contact', ['event' => $slug]))->assertOk()->assertDontSee('Hidden Draft Event');

    Livewire::withQueryParams(['event' => $slug])
        ->test('site.contact-form')
        ->assertSet('eventId', null);
})->with(['draft event' => 'hidden-draft', 'unknown event' => 'nope', 'malformed' => [['hidden-draft']]]);

it('shows the contact form labels in French', function () {
    app()->setLocale('fr');

    Livewire::test('site.contact-form')
        ->assertSee('Contacter la fondation')
        ->assertSee('Envoyer le message');
});
