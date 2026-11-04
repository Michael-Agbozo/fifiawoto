<?php

use App\Enums\UserRole;
use App\Models\Donation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('creates a donation linked to an event', function () {
    $event = Event::factory()->create();

    Livewire::test('admin.donations')
        ->call('startCreate')
        ->set('donor_name', 'Kojo Donor')
        ->set('donor_email', 'kojo@example.test')
        ->set('amount', '125.50')
        ->set('currency', 'USD')
        ->set('payment_method', 'cash')
        ->set('event_id', $event->id)
        ->set('received_at', '2026-05-26')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $d = Donation::query()->sole();
    expect($d->amount_cents)->toBe(12550)
        ->and($d->event_id)->toBe($event->id)
        ->and($d->recorded_by)->toBe(auth()->id());
});

it('validates required fields', function () {
    Livewire::test('admin.donations')
        ->call('startCreate')
        ->set('donor_name', '')
        ->set('amount', '0')
        ->set('received_at', '')
        ->call('save')
        ->assertHasErrors(['donor_name', 'amount', 'received_at']);
});

it('edits a donation amount', function () {
    $d = Donation::factory()->create(['amount_cents' => 10000]);

    Livewire::test('admin.donations')
        ->call('startEdit', $d->id)
        ->set('amount', '250.00')
        ->call('save');

    expect($d->refresh()->amount_cents)->toBe(25000);
});

it('deletes after confirmation', function () {
    $d = Donation::factory()->create();

    Livewire::test('admin.donations')
        ->call('askDelete', $d->id)
        ->call('delete', $d->id);

    expect(Donation::query()->whereKey($d->id)->exists())->toBeFalse();
});

it('filters by event', function () {
    $eventA = Event::factory()->create(['title' => 'Event A']);
    $eventB = Event::factory()->create(['title' => 'Event B']);

    Donation::factory()->for($eventA)->create(['donor_name' => 'AAA']);
    Donation::factory()->for($eventB)->create(['donor_name' => 'BBB']);

    Livewire::test('admin.donations')
        ->set('eventFilter', (string) $eventA->id)
        ->assertSee('AAA')
        ->assertDontSee('BBB');
});

it('filters by date range', function () {
    Donation::factory()->create(['donor_name' => 'In Range',  'received_at' => '2026-05-20']);
    Donation::factory()->create(['donor_name' => 'Too Early', 'received_at' => '2026-04-01']);
    Donation::factory()->create(['donor_name' => 'Too Late',  'received_at' => '2026-06-30']);

    Livewire::test('admin.donations')
        ->set('fromDate', '2026-05-01')
        ->set('toDate', '2026-05-31')
        ->assertSee('In Range')
        ->assertDontSee('Too Early')
        ->assertDontSee('Too Late');
});

it('reflects the running totals on the stat cards', function () {
    Donation::factory()->create(['amount_cents' => 50000]);
    Donation::factory()->create(['amount_cents' => 25000]);

    Livewire::test('admin.donations')
        ->assertSee('$750'); // total recorded
});

it('streams a CSV export of the filtered donations', function () {
    Donation::factory()->create([
        'donor_name' => 'Esi Csv',
        'amount_cents' => 9999,
        'received_at' => '2026-05-26',
    ]);

    Livewire::test('admin.donations')
        ->call('exportCsv')
        ->assertFileDownloaded();
});

it('includes gifts received on the "to" date of the filter', function () {
    Donation::factory()->create(['donor_name' => 'Last Day Donor', 'received_at' => '2026-05-31']);
    Donation::factory()->create(['donor_name' => 'First Day Donor', 'received_at' => '2026-05-01']);
    Donation::factory()->create(['donor_name' => 'Next Month Donor', 'received_at' => '2026-06-01']);

    // A legacy row written as a bare Y-m-d string (the old edit path) must match too.
    $legacy = Donation::factory()->create(['donor_name' => 'Legacy Format Donor']);
    DB::table('donations')->where('id', $legacy->id)->update(['received_at' => '2026-05-31']);

    Livewire::test('admin.donations')
        ->set('fromDate', '2026-05-01')
        ->set('toDate', '2026-05-31')
        ->assertSee('Last Day Donor')
        ->assertSee('First Day Donor')
        ->assertSee('Legacy Format Donor')
        ->assertDontSee('Next Month Donor');
});

it('stores an edited received_at in the same format as a newly created donation', function () {
    $created = Donation::factory()->create(['received_at' => '2026-05-10']);
    $edited = Donation::factory()->create(['received_at' => '2026-05-01']);

    Livewire::test('admin.donations')
        ->call('startEdit', $edited->id)
        ->set('received_at', '2026-05-10')
        ->call('save')
        ->assertHasNoErrors();

    $raw = fn (Donation $d) => DB::table('donations')->where('id', $d->id)->value('received_at');

    expect($raw($edited))->toBe($raw($created));
});

it('keeps the original recorder when another admin edits a donation', function () {
    $recorder = User::factory()->superAdmin()->create();
    $d = Donation::factory()->create(['recorded_by' => $recorder->id]);

    Livewire::test('admin.donations')
        ->call('startEdit', $d->id)
        ->set('amount', '42.00')
        ->call('save')
        ->assertHasNoErrors();

    expect($d->refresh()->recorded_by)->toBe($recorder->id);
});

it('normalises legacy received_at values with the data migration', function () {
    $d = Donation::factory()->create(['received_at' => '2026-05-10']);
    $expected = DB::table('donations')->where('id', $d->id)->value('received_at');
    DB::table('donations')->where('id', $d->id)->update(['received_at' => '2026-05-10']);

    (require database_path('migrations/2026_10_28_170242_normalize_donation_received_at_values.php'))->up();

    expect(DB::table('donations')->where('id', $d->id)->value('received_at'))->toBe($expected);
});

it('only accepts the base currency', function () {
    config(['app.currency' => 'GHS']);

    Livewire::test('admin.donations')
        ->call('startCreate')
        ->assertSet('currency', 'GHS')
        ->set('donor_name', 'Kojo')
        ->set('amount', '10')
        ->set('currency', 'EUR')
        ->call('save')
        ->assertHasErrors(['currency']);

    expect(Donation::query()->count())->toBe(0);
});

it('excludes non-base-currency gifts from the totals', function () {
    Donation::factory()->create(['amount_cents' => 50000, 'currency' => 'USD']);
    Donation::factory()->create(['amount_cents' => 900000, 'currency' => 'GHS']);

    Livewire::test('admin.donations')
        ->assertSee('$500')
        ->assertDontSee('$9,500');
});

it('escapes spreadsheet formulas in the CSV export', function () {
    Donation::factory()->create(['donor_name' => '=HYPERLINK("http://evil.test","x")']);

    $component = Livewire::test('admin.donations')->call('exportCsv');
    $csv = base64_decode(data_get($component->effects, 'download.content'));

    expect($csv)->toContain('"\'=HYPERLINK(')
        ->not->toContain(',"=HYPERLINK(');
});

it('forbids opening donations without donations.view', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Volunteer, 'permissions' => []]));

    Livewire::test('admin.donations')->assertForbidden();
});

it('forbids mutating and exporting donations with view-only access', function () {
    $d = Donation::factory()->create();
    $this->actingAs(User::factory()->create(['role' => UserRole::Volunteer, 'permissions' => ['donations.view']]));

    Livewire::test('admin.donations')->call('startCreate')->assertForbidden();
    Livewire::test('admin.donations')->call('startEdit', $d->id)->assertForbidden();
    Livewire::test('admin.donations')->call('askDelete', $d->id)->assertForbidden();
    Livewire::test('admin.donations')->call('delete', $d->id)->assertForbidden();
    Livewire::test('admin.donations')->call('exportCsv')->assertForbidden();

    expect(Donation::query()->whereKey($d->id)->exists())->toBeTrue();
});

it('allows exporting donations with donations.view and reports.export', function () {
    Donation::factory()->create(['donor_name' => 'Export Donor']);
    $this->actingAs(User::factory()->create(['role' => UserRole::Volunteer, 'permissions' => ['donations.view', 'reports.export']]));

    $component = Livewire::test('admin.donations')->call('exportCsv');

    expect(base64_decode(data_get($component->effects, 'download.content')))->toContain('Export Donor');
});
