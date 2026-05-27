<?php

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Models\VolunteerApplication;
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
        ->call('submit')
        ->assertHasErrors(['interests', 'availability']);
});
