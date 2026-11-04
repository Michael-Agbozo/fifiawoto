<?php

use App\Enums\AssistanceType;
use App\Enums\ContactSubject;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Models\BeneficiaryApplication;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\VolunteerApplication;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

/**
 * @return array<string, array{0: string, 1: string, 2: array<string, mixed>, 3: class-string}>
 */
dataset('public forms', fn () => [
    'contact' => ['site.contact-form', 'submit', [
        'full_name' => 'Spam Bot',
        'email' => 'bot@example.test',
        'subject' => ContactSubject::cases()[0]->value,
        'message' => 'Buy cheap things from my totally legit website today.',
        'consent' => true,
    ], ContactMessage::class],
    'volunteer' => ['site.volunteer-application-form', 'submit', [
        'full_name' => 'Spam Bot',
        'email' => 'bot@example.test',
        'phone' => '+233 200 000 000',
        'country' => 'Ghana',
        'interests' => [VolunteerInterest::cases()[0]->value],
        'availability' => VolunteerAvailability::cases()[0]->value,
        'motivation' => 'I really want to help our community thrive together.',
        'consent' => true,
    ], VolunteerApplication::class],
    'apply for support' => ['site.beneficiary-application-form', 'submit', [
        'full_name' => 'Spam Bot',
        'phone' => '+233 200 000 000',
        'country' => 'Ghana',
        'assistance_type' => AssistanceType::Medical->value,
        'situation' => 'My daughter needs surgery that our family cannot afford on our own.',
        'consent' => true,
    ], BeneficiaryApplication::class],
    'newsletter' => ['site.newsletter-signup', 'subscribe', [
        'email' => 'bot@example.test',
    ], NewsletterSubscriber::class],
]);

it('silently drops submissions that fill the honeypot', function (string $component, string $action, array $fields, string $model) {
    Livewire::test($component)
        ->set($fields)
        ->set('website', 'https://spam.example')
        ->tap(fn () => $this->travel(10)->seconds())
        ->call($action)
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect($model::query()->count())->toBe(0);
    Mail::assertNothingQueued();
})->with('public forms');

it('silently drops submissions sent faster than a human could fill the form', function (string $component, string $action, array $fields, string $model) {
    Livewire::test($component)
        ->set($fields)
        ->tap(fn () => $this->travel(1)->seconds())
        ->call($action)
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect($model::query()->count())->toBe(0);
})->with('public forms');

it('accepts a human-paced submission with an empty honeypot', function (string $component, string $action, array $fields, string $model) {
    Livewire::test($component)
        ->set($fields)
        ->tap(fn () => $this->travel(5)->seconds())
        ->call($action)
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect($model::query()->count())->toBe(1);
})->with('public forms');

it('does not let the client reset the form timestamp', function (string $component) {
    Livewire::test($component)->set('formRenderedAt', 0);
})->with([
    'site.contact-form',
    'site.volunteer-application-form',
    'site.beneficiary-application-form',
    'site.newsletter-signup',
])->throws(CannotUpdateLockedPropertyException::class);

it('renders the honeypot field off-screen on each public page', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('Leave this field empty');
})->with(['contact', 'volunteer', 'apply-for-support', 'home']);
