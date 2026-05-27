<?php

use App\Models\Donation;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create()));

it('renders the donations report by default with the right summary', function () {
    Donation::factory()->create([
        'donor_name' => 'Esi Report',
        'amount_cents' => 50000,
        'received_at' => now()->subDays(5)->toDateString(),
    ]);

    Livewire::test('admin.reports')
        ->assertSee('$500')
        ->assertSee('Esi Report');
});

it('streams a CSV export', function () {
    Donation::factory()->create([
        'donor_name' => 'Csv Donor',
        'received_at' => now()->toDateString(),
    ]);

    Livewire::test('admin.reports')
        ->call('downloadCsv')
        ->assertFileDownloaded();
});
