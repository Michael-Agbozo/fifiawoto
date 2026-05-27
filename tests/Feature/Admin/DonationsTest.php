<?php

use App\Models\Donation;
use App\Models\Event;
use App\Models\User;
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
