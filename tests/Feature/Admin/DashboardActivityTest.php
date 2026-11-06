<?php

use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Models\VolunteerApplication;

beforeEach(function () {
    Donation::factory()->create(['donor_name' => 'Hidden Donor']);
    VolunteerApplication::factory()->create(['full_name' => 'Hidden Applicant', 'email' => 'applicant@example.test']);
    ContactMessage::factory()->create(['full_name' => 'Hidden Sender', 'email' => 'sender@example.test']);
    NewsletterSubscriber::factory()->create(['email' => 'subscriber@example.test', 'subscribed_at' => now()]);
});

it('shows every feed to a role that can view them all', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Hidden Donor')
        ->assertSee('Hidden Applicant')
        ->assertSee('Hidden Sender')
        ->assertSee('subscriber@example.test');
});

it('hides each feed from a role without its view permission', function () {
    $user = User::factory()->mediaManager()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('subscriber@example.test')
        ->assertSee('Hidden Sender')
        ->assertDontSee('Hidden Donor')
        ->assertDontSee('Hidden Applicant')
        ->assertDontSee('applicant@example.test');
});

it('shows a feed once the user is granted its permission', function () {
    $user = User::factory()->mediaManager()->create(['permissions' => ['donations.view']]);

    $this->actingAs($user)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Hidden Donor');
});

it('hides donor details from a volunteer coordinator, and subscribers too', function () {
    $user = User::factory()->volunteerCoordinator()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Hidden Applicant')
        ->assertDontSee('Hidden Donor')
        ->assertDontSee('subscriber@example.test');
});

it('keeps the CSV export to the same feeds', function () {
    $user = User::factory()->volunteerCoordinator()->create(['permissions' => ['reports.export']]);

    $all = $this->actingAs($user)->get(route('admin.dashboard.export'))->assertOk()->streamedContent();
    $donations = $this->get(route('admin.dashboard.export', ['tab' => 'donation']))->assertOk()->streamedContent();

    expect($all)->toContain('Hidden Applicant')
        ->not->toContain('Hidden Donor')
        ->not->toContain('subscriber@example.test')
        ->and($donations)->not->toContain('Hidden Donor')
        ->and(substr_count(trim($donations), "\n"))->toBe(0);
});
