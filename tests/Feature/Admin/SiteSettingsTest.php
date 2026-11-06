<?php

use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Models\Beneficiary;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerHourLog;
use App\Services\ImpactMetricService;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('saves settings and shows them on the public site', function () {
    Livewire::test('admin.site-settings')
        ->set('form.contact_email', 'team@example.org')
        ->set('form.phone', '+233 24 123 4567')
        ->set('form.summary', 'A brand new summary for the footer.')
        ->set('form.facebook_url', '')
        ->call('save')
        ->assertHasNoErrors();

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('team@example.org')
        ->assertSee('tel:+233241234567')
        ->assertSee('A brand new summary for the footer.')
        ->assertDontSee('aria-label="Facebook"', false);
});

it('rejects bad values', function () {
    Livewire::test('admin.site-settings')
        ->set('form.contact_email', 'nope')
        ->set('form.instagram_url', 'javascript:alert(1)')
        ->call('save')
        ->assertHasErrors(['form.contact_email', 'form.instagram_url']);
});

it('stores a new hero photo and ignores a path sent by the client', function () {
    Storage::fake('public');

    Livewire::test('admin.site-settings')
        ->set('heroUploads.hero_image_main', UploadedFile::fake()->image('hero.jpg'))
        ->set('form.hero_image_left', '../../.env')
        ->call('save')
        ->assertHasNoErrors();

    expect(SiteSettings::get('hero_image_main'))->toStartWith('site/')
        ->and(SiteSettings::get('hero_image_left'))->toBe('images/foundation/outreach-2.webp');
    Storage::disk('public')->assertExists(SiteSettings::get('hero_image_main'));
});

it('blocks the page for roles without access', function () {
    $this->actingAs(User::factory()->create(['role' => 'foundation_staff']))
        ->get(route('admin.settings.site'))
        ->assertForbidden();
});

it('adds new records to the impact baselines', function () {
    SiteSettings::save(['baseline_children' => 100, 'baseline_communities' => 1, 'baseline_countries' => 1, 'baseline_volunteer_hours' => 50]);

    Beneficiary::factory()->create(['status' => SupportStatus::Active, 'category' => SupportCategory::ChildEducation, 'region' => 'Volta', 'country' => 'Ghana']);
    Beneficiary::factory()->create(['status' => SupportStatus::Active, 'category' => SupportCategory::Medical, 'date_of_birth' => today()->subYears(40), 'region' => 'Lomé', 'country' => 'Togo']);
    Beneficiary::factory()->create(['status' => SupportStatus::PendingReview, 'category' => SupportCategory::ChildEducation, 'region' => 'Accra', 'country' => 'Benin']);
    VolunteerHourLog::query()->create(['hours' => 2.5, 'served_on' => today()]);

    expect(app(ImpactMetricService::class)->publicStats())->toBe([
        'children' => 101,
        'communities' => 2,
        'countries' => 2,
        'volunteer_hours' => 52,
    ]);
});

it('shows live numbers on the home page and hides volunteer hours at zero', function () {
    $this->get(route('home'))->assertOk()->assertSee('data-counter="520"', false)->assertDontSee('Volunteer Hours');

    VolunteerHourLog::query()->create(['hours' => 12, 'served_on' => today()]);

    $this->get(route('home'))->assertSee('Volunteer Hours')->assertSee('data-counter="12"', false);
});

it('logs volunteer hours from the roster', function () {
    $volunteer = Volunteer::factory()->create();

    Livewire::test('admin.volunteers')
        ->call('startLogHours', $volunteer->id)
        ->set('log_hours', '3.5')
        ->call('saveHours')
        ->assertHasNoErrors();

    expect((float) VolunteerHourLog::query()->sole()->hours)->toBe(3.5);
});

it('hides the Mobile Money card until a number is saved', function () {
    $this->get(route('donate'))
        ->assertOk()
        ->assertDontSee('Mobile Money')
        ->assertDontSee('XX XXX')
        ->assertDontSee('accepts mobile money')
        ->assertSee('Bank transfer');

    Livewire::test('admin.site-settings')
        ->set('form.mobile_money_number', '+233 24 555 0100')
        ->call('save')
        ->assertHasNoErrors();

    $this->get(route('donate'))
        ->assertSee('Mobile Money')
        ->assertSee('+233 24 555 0100')
        ->assertSee('accepts mobile money');
});

it('rejects an overlong Mobile Money number', function () {
    Livewire::test('admin.site-settings')
        ->set('form.mobile_money_number', str_repeat('1', 41))
        ->call('save')
        ->assertHasErrors('form.mobile_money_number');
});
