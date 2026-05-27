<?php

use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use App\Models\ContactMessage;
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
        ->call('submit')
        ->assertHasErrors(['consent' => 'accepted']);
});
