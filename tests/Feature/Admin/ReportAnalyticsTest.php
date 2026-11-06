<?php

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\Donation;
use App\Models\Event;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use App\Services\ReportAnalyticsService;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create()));

it('returns an overview payload with KPIs and trends', function () {
    Donation::factory()->count(3)->create([
        'received_at' => now()->subDays(5),
        'amount_cents' => 5000,
    ]);
    VolunteerApplication::factory()->count(2)->create(['created_at' => now()->subDays(3)]);
    Beneficiary::factory()->count(4)->create(['created_at' => now()->subDays(2)]);
    Event::factory()->create(['starts_at' => now()->addDays(5)]);

    $data = app(ReportAnalyticsService::class)->for(
        'overview',
        now()->subDays(30)->toDateString(),
        now()->addDays(30)->toDateString(),
    );

    expect($data)
        ->toHaveKeys(['kpis', 'donation_line', 'beneficiary_categories', 'top_donors', 'top_events'])
        ->and($data['kpis']['donations']['value'])->toBeGreaterThan(0)
        ->and($data['kpis']['beneficiaries']['value'])->toBe(4)
        ->and($data['donation_line'])->toHaveCount(12);
});

it('returns donation analytics with average, unique donor count, daily series, and payment method breakdown', function () {
    Donation::factory()->create(['received_at' => now()->subDays(2), 'amount_cents' => 10000, 'payment_method' => 'card', 'donor_email' => 'a@x.com']);
    Donation::factory()->create(['received_at' => now()->subDays(2), 'amount_cents' => 20000, 'payment_method' => 'card', 'donor_email' => 'b@x.com']);
    Donation::factory()->create(['received_at' => now()->subDays(2), 'amount_cents' => 5000,  'payment_method' => 'cash', 'donor_email' => 'a@x.com']);

    $data = app(ReportAnalyticsService::class)->for(
        'donations',
        now()->subDays(30)->toDateString(),
        now()->toDateString(),
    );

    expect($data['kpis']['total']['value'])->toEqual(350)
        ->and($data['kpis']['count']['value'])->toBe(3)
        ->and($data['kpis']['avg']['value'])->toBeGreaterThan(0)
        ->and($data['kpis']['unique_donors']['value'])->toBe(2)
        ->and(collect($data['by_method'])->pluck('label')->all())->toContain('card', 'cash')
        ->and($data['daily'])->not->toBeEmpty();
});

it('returns volunteer analytics with status funnel, interests, availability, and countries', function () {
    VolunteerApplication::factory()->count(2)->create([
        'created_at' => now()->subDays(2),
        'country' => 'Ghana',
        'interests' => [VolunteerInterest::cases()[0]->value, VolunteerInterest::cases()[1]->value],
        'availability' => VolunteerAvailability::cases()[0]->value,
        'status' => VolunteerApplicationStatus::New->value,
    ]);
    VolunteerApplication::factory()->create([
        'created_at' => now()->subDays(1),
        'country' => 'Togo',
        'interests' => [VolunteerInterest::cases()[0]->value],
        'availability' => VolunteerAvailability::cases()[1]->value,
        'status' => VolunteerApplicationStatus::Approved->value,
    ]);
    Volunteer::factory()->create(['assigned_at' => now()->subDays(1)]);

    $data = app(ReportAnalyticsService::class)->for(
        'volunteers',
        now()->subDays(30)->toDateString(),
        now()->toDateString(),
    );

    expect($data['kpis']['applications']['value'])->toBe(3)
        ->and($data['kpis']['approval_rate']['value'])->toBe(round((1 / 3) * 100, 1))
        ->and(collect($data['top_countries'])->pluck('label')->all())->toContain('Ghana', 'Togo')
        ->and($data['interests'])->not->toBeEmpty()
        ->and($data['availability'])->not->toBeEmpty()
        ->and(collect($data['funnel'])->sum('count'))->toBe(3);
});

it('returns events analytics with status, top by raised, and goal leaderboard', function () {
    $event = Event::factory()->create(['starts_at' => now()->subDays(10), 'goal_cents' => 100000]);
    Donation::factory()->create(['event_id' => $event->id, 'received_at' => now()->subDays(5), 'amount_cents' => 60000]);

    $data = app(ReportAnalyticsService::class)->for(
        'events',
        now()->subDays(30)->toDateString(),
        now()->toDateString(),
    );

    expect($data['kpis']['events']['value'])->toBe(1)
        ->and($data['kpis']['raised']['value'])->toEqual(600)
        ->and($data['kpis']['achievement_pct']['value'])->toBe(60.0);
});

it('returns beneficiary analytics with category, status, country, gender, and conversion rate', function () {
    BeneficiaryApplication::factory()->count(4)->create(['created_at' => now()->subDays(5)]);
    BeneficiaryApplication::factory()->create([
        'created_at' => now()->subDays(3),
        'converted_beneficiary_id' => Beneficiary::factory()->create()->id,
    ]);
    Beneficiary::factory()->count(3)->create(['created_at' => now()->subDays(2), 'country' => 'Ghana']);

    $data = app(ReportAnalyticsService::class)->for(
        'beneficiaries',
        now()->subDays(30)->toDateString(),
        now()->toDateString(),
    );

    expect($data['kpis']['records']['value'])->toBeGreaterThanOrEqual(3)
        ->and($data['kpis']['applications']['value'])->toBe(5)
        ->and($data['kpis']['conversion_rate']['value'])->toBe(20.0)
        ->and($data['by_category'])->not->toBeEmpty()
        ->and($data['by_status'])->not->toBeEmpty();
});

it('renders the reports page with the overview tab by default', function () {
    Livewire::test('admin.reports')
        ->assertSet('category', 'overview')
        ->assertSeeText('Foundation overview');
});

it('compares against a prior window of exactly the same length', function () {
    // Current window: 2026-05-08 .. 2026-05-14 (7 days) → prior: 2026-05-01 .. 2026-05-07.
    Donation::factory()->create(['received_at' => '2026-05-10', 'amount_cents' => 20000]);
    Donation::factory()->create(['received_at' => '2026-05-01', 'amount_cents' => 10000]); // first prior day
    Donation::factory()->create(['received_at' => '2026-05-07', 'amount_cents' => 0]);
    Donation::factory()->create(['received_at' => '2026-04-30', 'amount_cents' => 99900]); // one day too early

    $data = app(ReportAnalyticsService::class)->for('donations', '2026-05-08', '2026-05-14');

    expect($data['kpis']['total']['value'])->toEqual(200)
        ->and($data['kpis']['count'])->toBe(['value' => 1, 'delta' => -50.0])
        ->and($data['kpis']['total']['delta'])->toBe(100.0);
});

it('uses a one-day prior window for a single-day report', function () {
    Donation::factory()->create(['received_at' => '2026-05-10', 'amount_cents' => 5000]);
    Donation::factory()->create(['received_at' => '2026-05-09', 'amount_cents' => 10000]);
    Donation::factory()->create(['received_at' => '2026-05-08', 'amount_cents' => 70000]);

    $data = app(ReportAnalyticsService::class)->for('donations', '2026-05-10', '2026-05-10');

    expect($data['kpis']['total']['delta'])->toBe(-50.0);
});

it('returns a null delta when the prior period is zero', function () {
    $data = app(ReportAnalyticsService::class)->for('donations', '2026-05-10', '2026-05-10');

    expect($data['kpis']['total'])->toBe(['value' => 0, 'delta' => null]);
});

it('excludes donations outside the base currency from analytics', function () {
    Donation::factory()->create(['received_at' => '2026-05-10', 'amount_cents' => 10000, 'currency' => 'USD', 'donor_name' => 'Usd Donor']);
    Donation::factory()->create(['received_at' => '2026-05-10', 'amount_cents' => 500000, 'currency' => 'GHS', 'donor_name' => 'Ghs Donor']);

    $service = app(ReportAnalyticsService::class);
    $donations = $service->for('donations', '2026-05-01', '2026-05-31');
    $overview = $service->for('overview', '2026-05-01', '2026-05-31');

    expect($donations['kpis']['total']['value'])->toEqual(100)
        ->and($donations['kpis']['count']['value'])->toBe(1)
        ->and(collect($donations['top_donors'])->pluck('donor_name')->all())->toBe(['Usd Donor'])
        ->and($overview['kpis']['donations']['value'])->toEqual(100);
});

it('charts only the days in a short range', function () {
    $service = app(ReportAnalyticsService::class);
    $today = now()->toDateString();

    expect($service->for('donations', $today, $today)['daily'])->toHaveCount(1)
        ->and($service->for('donations', now()->subDays(2)->toDateString(), $today)['daily'])->toHaveCount(3);
});

it('renders the donations chart for a one-day range', function () {
    Donation::factory()->create(['amount_cents' => 5000, 'received_at' => now()]);

    Livewire::test('admin.reports')
        ->set('category', 'donations')
        ->set('fromDate', now()->toDateString())
        ->set('toDate', now()->toDateString())
        ->assertOk()
        ->assertSee('1 day ·')
        ->assertSeeHtml('<circle cx="360"');
});
