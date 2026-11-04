<?php

use App\Enums\SponsorshipStatus;
use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MediaItem;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\PageVisit;
use App\Models\Sponsorship;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerHourLog;
use Database\Seeders\BeneficiarySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoActivitySeeder;
use Database\Seeders\EventSeeder;
use Database\Seeders\LiveDemoSeeder;
use Database\Seeders\VolunteerSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

afterEach(function () {
    putenv('DEMO_PASSWORD');
});

it('fills every feature with demo data and does not duplicate it on rerun', function () {
    $this->seed(DatabaseSeeder::class);

    $counts = fn () => [
        Beneficiary::query()->count(),
        Volunteer::query()->count(),
        Sponsorship::query()->count(),
        VolunteerHourLog::query()->count(),
        EventRegistration::query()->count(),
        NewsletterCampaign::query()->count(),
        NewsletterSubscriber::query()->count(),
        PageVisit::query()->count(),
    ];

    $firstRun = $counts();
    $this->seed(DatabaseSeeder::class);

    expect($counts())->toBe($firstRun)
        ->and(Beneficiary::query()->count())->toBe(15)
        ->and(Sponsorship::query()->count())->toBe(5)
        ->and(VolunteerHourLog::query()->count())->toBe(12)
        ->and(EventRegistration::query()->count())->toBe(18)
        ->and(NewsletterCampaign::query()->count())->toBe(2)
        ->and(PageVisit::query()->count())->toBeGreaterThan(500);
});

it('shows a pledge in each state with due dates that follow its gifts', function () {
    $this->seed(DatabaseSeeder::class);

    $dueDate = fn (string $sponsor) => Sponsorship::query()->where('sponsor_name', $sponsor)->sole()->next_due_on;

    expect($dueDate('Hannah Whitfield')->isSameDay(today()->addDays(12)))->toBeTrue()
        ->and($dueDate('Marcus Bell')->isSameDay(today()->addDays(2)))->toBeTrue()
        ->and($dueDate('Priya Nair')->isPast())->toBeTrue()
        ->and(Sponsorship::query()->where('status', SponsorshipStatus::Pending)->count())->toBe(1)
        ->and(Sponsorship::query()->where('status', SponsorshipStatus::Ended)->count())->toBe(1);
});

it('never gives demo records an address that could reach a real person', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Sponsorship::query()->where('sponsor_email', 'not like', '%@example.com')->count())->toBe(0)
        ->and(Sponsorship::query()->where('reminders_enabled', true)->count())->toBe(0)
        ->and(NewsletterSubscriber::query()->where('email', 'not like', '%@example.com')->count())->toBe(0)
        ->and(EventRegistration::query()->where('email', 'not like', '%@example.com')->count())->toBe(0)
        ->and(Volunteer::query()->where('email', 'not like', '%@example.com')->count())->toBe(0);
});

it('does not need dev-only packages, because deployed builds install without Faker', function () {
    foreach (File::files(database_path('seeders')) as $seeder) {
        expect($seeder->getContents())
            ->not->toContain('::factory(')
            ->not->toContain('fake()')
            ->not->toContain('$this->faker');
    }
});

it('requires a demo password on staging so the default never reaches a public url', function () {
    app()->instance('env', 'staging');

    expect(fn () => (new DatabaseSeeder)->run())->toThrow(RuntimeException::class, 'DEMO_PASSWORD');

    expect(User::query()->count())->toBe(0);
});

it('seeds shared reviewer logins on staging with the demo password', function () {
    app()->instance('env', 'staging');
    putenv('DEMO_PASSWORD=reviewer-secret');

    $this->seed(DatabaseSeeder::class);

    $owner = User::query()->where('email', 'superadmin@fifiawoto.test')->sole();

    expect(Hash::check('reviewer-secret', $owner->password))->toBeTrue()
        ->and(Hash::check('password', $owner->password))->toBeFalse()
        ->and($owner->force_password_change)->toBeFalse();
});

it('refuses to seed any demo records in production', function (string $seeder) {
    app()->instance('env', 'production');

    expect(fn () => (new $seeder)->run())->toThrow(RuntimeException::class);
})->with([BeneficiarySeeder::class, VolunteerSeeder::class, DemoActivitySeeder::class]);

it('hides staging pages from search engines but not production pages', function () {
    app()->instance('env', 'staging');
    $this->get(route('home'))->assertOk()->assertSee('content="noindex, nofollow"', escape: false);

    app()->instance('env', 'production');
    $this->get(route('home'))->assertOk()->assertDontSee('content="noindex, nofollow"', escape: false);
});

it('renders the public and staff pages reviewers will open once seeded', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (['home', 'events.index', 'sponsor-a-child', 'media'] as $name) {
        $this->get(route($name))->assertOk();
    }

    $owner = User::query()->where('email', 'superadmin@fifiawoto.test')->sole();
    $owner->forceFill(['force_password_change' => false])->save();

    foreach ([
        ['admin.sponsorships.index', 'Hannah Whitfield'],
        ['admin.newsletter.campaigns', 'Demo: thank you for a wonderful year'],
        ['admin.analytics.index', 'facebook'],
        ['admin.volunteers.index', 'Abla Kudjo'],
        ['admin.beneficiaries.index', 'Adjo Mensah'],
        ['admin.events.index', 'Community Outreach Program'],
    ] as [$name, $expected]) {
        $this->actingAs($owner)->get(route($name))->assertOk()->assertSee($expected);
    }
});

it('previews programmes and sponsors on the live site only when switched on, with no logins', function () {
    app()->instance('env', 'production');

    expect(fn () => (new EventSeeder)->run())->toThrow(RuntimeException::class);
    expect(Event::query()->count())->toBe(0);

    config(['demo.live_content' => true]);
    $this->artisan('db:seed', ['--class' => LiveDemoSeeder::class, '--force' => true])->assertSuccessful();
    $this->artisan('db:seed', ['--class' => LiveDemoSeeder::class, '--force' => true])->assertSuccessful();

    expect(Event::query()->count())->toBe(8)
        ->and(Beneficiary::query()->publicSponsorship()->count())->toBe(6)
        ->and(Sponsorship::query()->count())->toBe(5)
        ->and(MediaItem::query()->count())->toBe(24)
        ->and(User::query()->count())->toBe(0);

    $this->get(route('sponsor-a-child'))->assertOk()
        ->assertSee('Abena (Demo)')
        ->assertSee('include sample content');
    $this->get(route('events.index'))->assertOk()->assertSee('include sample content');
});

it('keeps the sample notice off the site when demo content is off', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('include sample content');
});

it('removes the sample content and leaves real records alone', function () {
    config(['demo.live_content' => true]);
    $this->seed(LiveDemoSeeder::class);

    $realEvent = Event::factory()->create();
    $realChild = Beneficiary::factory()->create();
    $realGift = Donation::factory()->create(['external_reference' => 'BANK-123', 'event_id' => $realEvent->id]);
    $realPhoto = MediaItem::factory()->create(['event_id' => null, 'path' => 'media/real.jpg']);

    $this->artisan('demo:remove', ['--force' => true])->assertSuccessful();

    expect(Event::query()->pluck('id')->all())->toBe([$realEvent->id])
        ->and(Beneficiary::withTrashed()->pluck('id')->all())->toBe([$realChild->id])
        ->and(Sponsorship::query()->count())->toBe(0)
        ->and(Donation::query()->pluck('id')->all())->toBe([$realGift->id])
        ->and(MediaItem::query()->pluck('id')->all())->toBe([$realPhoto->id]);

    Storage::disk('public')->assertMissing('sponsorship/demo-ama.webp');
});
