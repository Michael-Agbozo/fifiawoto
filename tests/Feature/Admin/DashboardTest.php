<?php

use App\Enums\EventStatus;
use App\Enums\VolunteerApplicationStatus;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Models\VolunteerApplication;

it('renders every KPI label and the activity panel', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();

    foreach ([
        'Foundation snapshot',
        'Overview',
        'Donations this month',
        'Active beneficiaries',
        'Upcoming events',
        'Active volunteers',
        'Donations this period',
        'Cumulative revenue',
        'Recent activity',
    ] as $label) {
        $response->assertSee($label);
    }
});

it('shows the role label and user name in the sidebar', function () {
    $admin = User::factory()->foundationStaff()->create([
        'name' => 'Esi Manager',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSee('Esi Manager')
        ->assertSee('Foundation Staff');
});

it('counts upcoming published events correctly', function () {
    $admin = User::factory()->superAdmin()->create();

    Event::factory()->create([
        'starts_at' => now()->addDays(10),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);
    Event::factory()->create([
        'starts_at' => now()->addDays(20),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);
    Event::factory()->draft()->create([
        'starts_at' => now()->addDays(15),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSeeInOrder(['Upcoming events', '2']);
});

it('totals donations across all events', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Donation::factory()->for($event)->create(['amount_cents' => 100_00]);
    Donation::factory()->for($event)->create(['amount_cents' => 250_00]);
    Donation::factory()->create(['amount_cents' => 50_00, 'event_id' => null]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSee('$400');
});

it('surfaces pending volunteer applications on the Active volunteers card', function () {
    $admin = User::factory()->superAdmin()->create();

    VolunteerApplication::factory()->count(3)->create([
        'status' => VolunteerApplicationStatus::New->value,
    ]);
    VolunteerApplication::factory()->create([
        'status' => VolunteerApplicationStatus::Approved->value,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertSee('Active volunteers');
    $response->assertSee('3 awaiting review');
});

it('surfaces recent activity entries from across the system', function () {
    $admin = User::factory()->superAdmin()->create();

    VolunteerApplication::factory()->create([
        'full_name' => 'Akua Helper',
    ]);
    ContactMessage::factory()->create([
        'full_name' => 'Kojo Partner',
    ]);
    Donation::factory()->create([
        'donor_name' => 'Ama Donor',
        'amount_cents' => 750_00,
    ]);
    NewsletterSubscriber::factory()->create([
        'email' => 'fan@example.test',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertSee('Akua Helper');
    $response->assertSee('Kojo Partner');
    $response->assertSee('Ama Donor');
    $response->assertSee('fan@example.test');
});

it('renders an empty state on the activity feed when nothing has happened', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSee('Nothing has happened yet.');
});

it('hides modules a coordinator cannot manage from the sidebar', function () {
    $coordinator = User::factory()->volunteerCoordinator()->create();

    $response = $this->actingAs($coordinator)->get(route('admin.dashboard'));

    $response->assertSee('Volunteers');
    $response->assertDontSee('User management');
    $response->assertDontSee('System logs');
    $response->assertDontSee('Newsletter subscribers');
});

it('only exposes user management to super admins', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSee('User management');
});
