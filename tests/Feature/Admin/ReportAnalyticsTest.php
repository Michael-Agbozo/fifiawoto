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
