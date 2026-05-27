<?php

use App\Enums\ContactSubject;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Models\VolunteerApplication;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('public-form:volunteer:127.0.0.1');
    RateLimiter::clear('public-form:contact:127.0.0.1');
    RateLimiter::clear('public-form:newsletter:127.0.0.1');
});

it('throttles repeated volunteer applications from the same IP', function () {
    $submit = function () {
        return Livewire::test('site.volunteer-application-form')
            ->set('full_name', 'Repeat Sender')
            ->set('email', 'sender'.uniqid().'@example.com')
            ->set('phone', '+233 200 000 000')
            ->set('country', 'Ghana')
            ->set('interests', [VolunteerInterest::cases()[0]->value])
            ->set('availability', VolunteerAvailability::cases()[0]->value)
            ->set('motivation', 'I really want to help our community thrive together.')
            ->set('consent', true)
            ->call('submit');
    };

    $submit()->assertHasNoErrors();
    $submit()->assertHasNoErrors();
    $submit()->assertHasNoErrors();

    // 4th attempt should be rate-limited.
    $submit()->assertHasErrors(['rate']);

    expect(VolunteerApplication::query()->count())->toBe(3);
});

it('throttles repeated contact form submissions from the same IP', function () {
    $submit = function () {
        return Livewire::test('site.contact-form')
            ->set('full_name', 'Spammer')
            ->set('email', 'sender'.uniqid().'@example.com')
            ->set('subject', ContactSubject::cases()[0]->value)
            ->set('message', 'I would love to learn more about what you do.')
            ->set('consent', true)
            ->call('submit');
    };

    $submit()->assertHasNoErrors();
    $submit()->assertHasNoErrors();
    $submit()->assertHasNoErrors();

    $submit()->assertHasErrors(['rate']);
});

it('throttles repeated newsletter signups from the same IP', function () {
    $submit = function () {
        return Livewire::test('site.newsletter-signup')
            ->set('email', 'sub'.uniqid().'@example.com')
            ->call('subscribe');
    };

    for ($i = 0; $i < 5; $i++) {
        $submit()->assertHasNoErrors();
    }

    // 6th attempt should be rate-limited.
    $submit()->assertHasErrors(['rate']);
});
